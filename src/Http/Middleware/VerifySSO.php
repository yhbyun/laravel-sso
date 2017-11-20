<?php

namespace losted\SSO\Http\Middleware;

use Closure;

class VerifySSO
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        $broker = new \losted\SSO\Broker(config('sso.server_endpoint'), config('sso.client_id'), config("sso.client_secret"));
        $broker->loginCurrentUser();

        return $next($request);
    }
}
