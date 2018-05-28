<?php

Route::match(['post', 'get'], config('sso.endpoint_path'), 'Losted\SSO\Http\Controllers\ServerController@endpoint');
