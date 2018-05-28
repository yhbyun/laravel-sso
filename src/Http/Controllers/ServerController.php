<?php

namespace Losted\SSO\Http\Controllers;

use Illuminate\Http\Request;
use Losted\SSO\Contracts\Server as SSO;

class ServerController extends \Illuminate\Routing\Controller
{
    public function endpoint(SSO $sso, Request $request)
    {
        if ($request->has('command')) {
            $command = $request->input('command', null);

            if (!$command || !method_exists($sso, $command)) {
                return response()->json(['error' => 'Unknown command'], 404);
            }

            if ($result = $sso->$command()) {
                return $result;
            }
        }
    }
}
