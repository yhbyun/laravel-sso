<?php

namespace losted\SSO;

use losted\SSO\Exceptions\Exception;
use losted\SSO\Exceptions\NotAttachedException;

class Broker
{
    protected $url;

    public $broker;

    protected $secret;

    public $token;

    protected $user_info;

    protected $cookie_lifetime;

    public function __construct($url = null, $broker = null, $secret = null, $cookie_lifetime = 3600)
    {
        $this->url = config('sso.server_endpoint', $url);
        $this->broker = config('sso.broker_id', $broker);
        $this->secret = config('sso.broker_secret', $secret);
        $this->cookie_lifetime = $cookie_lifetime;

        if (!$this->url) {
            throw new \InvalidArgumentException('SSO server URL not specified');
        }
        if (!$this->broker) {
            throw new \InvalidArgumentException('SSO broker id not specified');
        }
        if (!$this->secret) {
            throw new \InvalidArgumentException('SSO broker secret not specified');
        }
        if (isset($_COOKIE[$this->getCookieName()])) {
            $this->token = $_COOKIE[$this->getCookieName()];
        }

        $this->attach(true);
    }

    /**
     * Get the cookie name.
     */
    protected function getCookieName()
    {
        return 'sso_token_' . preg_replace('/[_\W]+/', '_', strtolower($this->broker));
    }

    /**
     * Generate session id from session key
     */
    protected function getSessionId()
    {
        if (!isset($this->token)) {
            return null;
        }

        $checksum = hash('sha256', 'session' . $this->token . $this->secret);
        return "SSO-{$this->broker}-{$this->token}-$checksum";
    }

    /**
     * Generate session token
     */
    public function generateToken()
    {
        if (isset($this->token)) {
            return;
        }

        $this->token = base_convert(md5(uniqid(rand(), true)), 16, 36);
        setcookie($this->getCookieName(), $this->token, time() + $this->cookie_lifetime, '/');
    }

    /**
     * Clears session token
     */
    public function clearToken()
    {
        setcookie($this->getCookieName(), null, 1, '/');
        $this->token = null;
    }

    /**
     * Check if we have an SSO token.
     */
    public function isAttached()
    {
        return isset($this->token);
    }

    /**
     * Get URL to attach session at SSO server.
     */
    public function getAttachUrl($params = [])
    {
        $this->generateToken();

        $data = [
            'command' => 'attach',
            'broker' => $this->broker,
            'token' => $this->token,
            'checksum' => hash('sha256', 'attach' . $this->token . $this->secret)
        ] + $_GET;

        return $this->url . '?' . http_build_query($data + $params);
    }

    /**
     * Attach our session to the user's session on the SSO server.
     */
    public function attach($returnUrl = null)
    {
        if ($this->isAttached()) {
            return;
        }

        if ($returnUrl === true) {
            $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $returnUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        $params = ['return_url' => $returnUrl];
        $url = $this->getAttachUrl($params);

        header("Location: $url", true, 307);
        exit;
    }

    /**
     * Get the request url for a command
     */
    protected function getRequestUrl($command, $params = [])
    {
        $params['command'] = $command;
        return $this->url . '?' . http_build_query($params);
    }

    /**
     * Log the client in the SSO server.
     */
    public function login($username = null, $password = null)
    {
        if (!isset($username) && isset($_POST['username'])) {
            $username = $_POST['username'];
        }
        if (!isset($password) && isset($_POST['password'])) {
            $password = $_POST['password'];
        }

        $result = $this->request('POST', 'login', compact('username', 'password'));
        $this->user_info = $result;

        return $this->user_info;
    }

    /**
     * Logout at sso server.
     */
    public function logout()
    {
        $this->request('POST', 'logout', 'logout');
    }

    /**
     * Get user information.
     */
    public function getUserInfo()
    {
        if (!isset($this->user_info)) {
            $this->user_info = $this->request('GET', 'userInfo');
        }

        return $this->user_info;
    }

    /**
     * Magic method to do arbitrary request
     */
    public function __call($fn, $args)
    {
        $sentence = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $fn));
        $parts = explode(' ', $sentence);

        $method = count($parts) > 1 && in_array(strtoupper($parts[0]), ['GET', 'DELETE'])
            ? strtoupper(array_shift($parts))
            : 'POST';
        $command = join('-', $parts);

        return $this->request($method, $command, $args);
    }

    /**
     * Execute on SSO server.
     */
    protected function request($method, $command, $data = null)
    {
        if (!$this->isAttached()) {
            throw new NotAttachedException('No token');
        }
        $url = $this->getRequestUrl($command, !$data || $method === 'POST' ? [] : $data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Bearer ' . $this->getSessionId()]);

        if ($method === 'POST' && !empty($data)) {
            $post = is_string($data) ? $data : http_build_query($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch) != 0) {
            $message = 'Server request failed: ' . curl_error($ch);
            throw new Exception($message);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        list($contentType) = explode(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));

        $data = json_decode($response, true);

        if ($httpCode == 403) {
            $this->clearToken();
            throw new NotAttachedException($data['error'] ?: $response, $httpCode);
        }
        if ($httpCode >= 400) {
            throw new Exception("Bad command: $command" ?: $response, $httpCode);
        }
        return $data;
    }

    /**
     * Login the user to the SSO
     */
    public function loginUser($username, $password)
    {
        try {
            return $this->login($username, $password);
        } catch (NotAttachedException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
