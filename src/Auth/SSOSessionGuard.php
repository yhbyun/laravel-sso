<?php

namespace Losted\SSO\Auth;

use Losted\SSO\Broker;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Auth\UserProvider;
use Symfony\Component\HttpFoundation\Request;
use Losted\SSO\Exceptions\Exception as SSOExcption;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class SSOSessionGuard extends SessionGuard implements Guard
{
    /**
     * @var \Losted\SSO\Broker
     */
    protected $broker;

    /**
     * The user provider implementation.
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $provider;

    protected $debug = false;

    /**
     * Create a new authentication guard.
     *
     * @param  string  $name
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return void
     */
    public function __construct($name, UserProvider $provider, Session $session, Request $request = null)
    {
        $this->debug('******* SESSION GUARD******');
        parent::__construct($name, $provider, $session, $request);

        $this->broker = new Broker(config('sso.server_endpoint'), config('sso.broker_id'), config('sso.broker_secret'));
        $this->broker->attach(true);

        $this->provider = app('auth')->createUserProvider('users');
    }

    /**
     * This logs us in when we have logged in somewhere else
     * It also logs us out when we have logged out somewhere else
     * e.g. on app1, log in to app2, refresh app1 and you're logged in
     *
     * @return AuthenticatableContract
     */
    public function user()
    {
        $this->debug('USER');
        try {
            $this->debug('TRY');
            $details = $this->broker->getUserInfo();
        } catch (SSOExcption $e) {
            $this->debug('SSOExcption!');
            $this->user = null;

            return null;
        }

        if (empty($details)) {
            $this->debug('NO DETAILS!?');

            return null;
        }

        $this->debug('FETCHING USER...', (array) $details);
        // if we made it this far, we're good
        $this->user = $this->getUserModel($details);
        if (is_null($this->user)) {
            $this->debug('WTF MAN?');
            return parent::user();
        }

        $this->debug('RETURNING....', $this->user->toArray());
        return $this->user;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        $this->debug('ATTEMPTING...?');
        try {
            $this->broker->login($credentials[$this->username()], $credentials['password']);
            $this->debug('broker->login worked');

            $details = $this->broker->getUserInfo();
            $this->debug('broker->getUserInfo worked', $details);

            // get the user model....
            $user = $this->getUserModel($details);
            $this->debug('this->getUserModel worked');
            $this->debug('ATTEMPT FOR USER...??' . (is_null($user) ? 'NO USER!' : 'GOT USER'));
            $this->login($user, $remember);

            return true;
        } catch (\Exception | SsoException $e) {
            $this->debug('EXCEPTION!: ' . get_class($e), $e->getMessage());

            return false;
        }
    }

    public function logout()
    {
        parent::logout();
        $this->broker->logout();
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    protected function username()
    {
        return config('sso.username_field', 'email');
    }

    /**
     * This is only called *after* the user's credentials have passed testing
     * e.g.after the username & password were validated by the SSO Server
     *
     * @param $details
     * @return User
     */
    protected function getUserModel($details)
    {
        $user = null;
        if (config('sso.refer_user_model', true)) {
            $user = $this->provider->retrieveByCredentials([$this->username() => $details[$this->username()]]);
        }

        if (is_null($user)) {
            $user = $this->createModel();

            foreach ($details as $key => $value) {
                $user->{$key} = $value;
            }
            // $user->save();
        }

        return $user;
    }

    protected function createModel()
    {
        if ($model = config('auth.providers.users.model')) {
            $class = '\\' . ltrim($model, '\\');

            return new $class;
        }

        throw new \Excption('auth.providers.users.model config not found');
    }

    /**
     * Write a message to the log.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    protected function debug($message, ...$context)
    {
        if ($this->debug) {
            \Log::debug($message, $context);
        }
    }

    /**
     * Override not to upate user table
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function cycleRememberToken(AuthenticatableContract $user)
    {
    }
}
