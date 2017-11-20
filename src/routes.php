<?php

Route::match(['post', 'get'], config('sso.endpoint_path'), 'losted\SSO\Http\Controllers\ServerController@endpoint');
