<?php

namespace Losted\SSO\Contracts;

use Losted\SSO\Models\Broker;

interface Server
{
    public function __construct(array $options = []);

    /**
     * Start the session for broker requests to the SSO server
     */
    public function startBrokerSession();

    /**
     * Attach a user session to a broker session
     */
    public function attach();

    /**
     * Authenticate
     */
    public function login();

    /**
     * Log out
     */
    public function logout();

    /**
     * Ouput user information as json.
     */
    public function userInfo();

    /**
     * Get the user information
     */
    public function getUserInfo($username);
}
