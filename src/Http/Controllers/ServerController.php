<?php

namespace losted\SSO\Http\Controllers;

use losted\SSO\Server as SSO;
use Illuminate\Http\Request;

class ServerController extends \Illuminate\Routing\Controller {

    public function endpoint(SSO $sso, Request $request) {

        if($request->has('command')) {

            $command = $request->input('command', null);

            if (!$command || !method_exists($sso, $command)) {
                return response()->json(['error' => 'Unknown command'], 404);
            }

            $result = $sso->$command();

            if($result) {
                return response()->json($result);
            }

        }

    }

}