<?php

namespace losted\SSO;

use App\User;

use losted\SSO\Models\Broker;
use losted\SSO\Exceptions\Exception;
use losted\SSO\Exceptions\AuthenticationException;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class Server
{

    /**
     * @var string
     */
    protected $options = ['session_ttl' => 36000];

    /**
     * @var mixed
     */
    protected $broker_id;

    /**
     * @var string
     */
    protected $return_type;

    public function __construct(array $options = [])
    {
        $this->options = $options + $this->options;
    }

    /**
     * Start the session for broker requests to the SSO server
     */
    public function startBrokerSession()
    {
        if (isset($this->broker_id)) {
            return;
        }

        $session_id = $this->getBrokerSessionId();

        if ($session_id === false) {
            return $this->fail("Broker didn't send a session key", 400);
        }

        $linked_id = Cache::get($session_id);

        if (!$linked_id) {
            return $this->fail("The broker session id isn't attached to a user session", 403);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            if ($linked_id !== session_id()) throw new \Exception("Session has already started", 400);
            return;
        }

        session_id($linked_id);
        session_start();

        $this->broker_id = $this->validateBrokerSessionId($session_id);
    }

    /**
     * Get session ID from header Authorization or from $_GET/$_POST
     */
    protected function getBrokerSessionId()
    {
        $headers = getallheaders();

        if (isset($headers['Authorization']) &&  strpos($headers['Authorization'], 'Bearer') === 0) {
            $headers['Authorization'] = substr($headers['Authorization'], 7);
            return $headers['Authorization'];
        }
        if (isset($_GET['access_token'])) {
            return $_GET['access_token'];
        }
        if (isset($_POST['access_token'])) {
            return $_POST['access_token'];
        }
        if (isset($_GET['sso_session'])) {
            return $_GET['sso_session'];
        }

        return false;
    }

    /**
     * Validate the broker session id
     */
    protected function validateBrokerSessionId($sid)
    {
        $matches = null;

        if (!preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $this->getBrokerSessionId(), $matches)) {
            return $this->fail("Invalid session id");
        }

        $broker_id = $matches[1];
        $token = $matches[2];

        if ($this->generateSessionId($broker_id, $token) != $sid) {
            return $this->fail("Checksum failed: Client IP address may have changed", 403);
        }

        return $broker_id;
    }

    /**
     * Start the session when a user visits the SSO server
     */
    protected function startUserSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    /**
     * Generate session id from session token
     */
    protected function generateSessionId($broker_id, $token)
    {
        $broker = $this->get_broker_info($broker_id);

        if (!isset($broker)) return null;

        return "SSO-{$broker_id}-{$token}-" . hash('sha256', 'session' . $token . $broker['secret']);
    }

    /**
     * Generate session id from session token
     */
    protected function generateAttachChecksum($broker_id, $token)
    {
        $broker = $this->get_broker_info($broker_id);

        if (!isset($broker)) return null;

        return hash('sha256', 'attach' . $token . $broker['secret']);
    }

    /**
     * Detect the type for the HTTP response.
     * Should only be done for an `attach` requestà.
     */
    protected function detectReturnType()
    {
        if (!empty($_GET['return_url'])) {
            $this->return_type = 'redirect';
        } elseif (!empty($_GET['callback'])) {
            $this->return_type = 'jsonp';
        } elseif (strpos($_SERVER['HTTP_ACCEPT'], 'image/') !== false) {
            $this->return_type = 'image';
        } elseif (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            $this->return_type = 'json';
        }
    }

    /**
     * Attach a user session to a broker session
     */
    public function attach()
    {
        $this->detectReturnType();

        if (empty($_REQUEST['broker'])) return $this->fail("No broker specified", 400);
        if (empty($_REQUEST['token'])) return $this->fail("No token specified", 400);

        if (!$this->return_type) return $this->fail("No return url specified", 400);

        $checksum = $this->generateAttachChecksum($_REQUEST['broker'], $_REQUEST['token']);

        if (empty($_REQUEST['checksum']) || $checksum != $_REQUEST['checksum']) {
            return $this->fail("Invalid checksum", 400);
        }

        $this->startUserSession();
        $sid = $this->generateSessionId($_REQUEST['broker'], $_REQUEST['token']);

        Cache::put($sid, $this->get_session_data('id'), $this->options['session_ttl']);

        $this->output_attach_success();
    }

    /**
     * Output on a successful attach
     */
    protected function output_attach_success()
    {
        if ($this->return_type === 'image') {
            $this->output_image();
        }

        if ($this->return_type === 'json') {
            header('Content-type: application/json; charset=UTF-8');
            echo json_encode(['success' => 'attached']);
        }

        if ($this->return_type === 'jsonp') {
            $data = json_encode(['success' => 'attached']);
            echo $_REQUEST['callback'] . "($data, 200);";
        }

        if ($this->return_type === 'redirect') {
            $url = $_REQUEST['return_url'];
            header("Location: $url", true, 307);
        }
    }

    /**
     * Output a 1x1 transparent image
     */
    protected function output_image()
    {
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
    }


    /**
     * Authenticate
     */
    public function login()
    {
        $this->startBrokerSession();

        if (empty($_POST['username'])) $this->fail("No username specified", 400);
        if (empty($_POST['password'])) $this->fail("No password specified", 400);

        try {
            $this->authenticate($_POST['username'], $_POST['password']);
        } catch(AuthenticationException $e) {
            return $this->fail($e->getMessage(), 401);
        } catch(\Exception $e) {
            return $this->fail($e->getMessage(), 400);
        }

        $this->setSessionData('sso_user', $_POST['username']);
        $this->userInfo();

    }

    /**
     * Log out
     */
    public function logout()
    {
        $this->startBrokerSession();
        $this->setSessionData('sso_user', null);

        header('Content-type: application/json; charset=UTF-8');
        http_response_code(204);
    }

    /**
     * Ouput user information as json.
     */
    public function userInfo()
    {
        $this->startBrokerSession();
        $user = null;

        $username = $this->get_session_data('sso_user');

        if ($username) {
            $user = $this->getUserInfo($username);
            if (!$user) {
                return $this->fail("User not found", 500);
            }

            header('Content-type: application/json; charset=UTF-8');
            echo json_encode($user);
        }

        return;
    }

    /**
     * Set session data
     */
    protected function setSessionData($key, $value)
    {
        if (!isset($value)) {
            unset($_SESSION[$key]);
            return;
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Get session data
     */
    protected function get_session_data($key)
    {
        if ($key === 'id') return session_id();

        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }


    /**
     * An error occured.
     */
    protected function fail($message, $http_status = 500)
    {
        if (!empty($this->options['fail_exception'])) {
            throw new Exception($message, $http_status);
        }

        if ($http_status === 500) trigger_error($message, E_USER_WARNING);

        if ($this->return_type === 'jsonp') {
            echo $_REQUEST['callback'] . "(" . json_encode(['error' => $message]) . ", $http_status);";
            exit();
        }

        if ($this->return_type === 'redirect') {
            $url = $_REQUEST['return_url'] . '?sso_error=' . $message;
            header("Location: $url", true, 307);
            exit();
        }

        http_response_code($http_status);
        header('Content-type: application/json; charset=UTF-8');

        echo json_encode(['error' => $message]);

        exit();
    }

    /**
     * Get the API secret of a broker and other info if needed
     */
    protected function get_broker_info($broker_id)
    {
        $broker = Broker::where('broker_id', $broker_id)->first();

        if($broker) {
            return [
                'secret' => $broker->broker_secret
            ];
        }

        return null;
    }

    /**
     * Authenticate using user credentials
     */
    protected function authenticate($username, $password)
    {
        if (!isset($username)) {
            throw new AuthenticationException("Username isn't set.");
        }

        if (!isset($password)) {
            throw new AuthenticationException("Password isn't set.");
        }

        if(Auth::attempt(['email' => $username, 'password' => $password])) {
            return;
        }

        throw new AuthenticationException("User not found.");

    }

    /**
     * Get the user information
     */
    public function getUserInfo($email)
    {
        return User::where('email', $email)->firstOrFail();
    }

    /**
     * Get user by ID
     */
    public function getUserById($id)
    {
        return User::findOrFail($id);
    }

}