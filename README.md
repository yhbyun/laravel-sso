# Laravel SSO

Based on and compatible with [jasny/sso](https://github.com/jasny/sso) and [awnali/sso-laravel-5](https://github.com/awnali/SSO-laravel-5).

This [Laravel](https://laravel.com) package is a Single Sign-On solution that allow you to have only one login and share the session across multiple apps.

#### Requirements
* Laravel 5.5

## Installation

Install this library through composer:

    composer require losted/laravel-sso

## Setup

### Server

Do the basic setup:

    php artisan key:generate
    php artisan make:auth

Publish the **configuration** and **migrations** files with:

    php artisan vendor:publish

Run the **migrations**:

    php artisan migrate

Add the **SSOServiceProvider** to your _config/app.php_ file:

    \losted\SSO\SSOSserviceProvider::class,

Configuration required in your .env for the server:

    SSO_SERVER_ENDPOINT_PATH=myendpoint

### Broker

Add the **VerifySSO** middleware to the _app/Http/Kernel.php_ **web** middleware array:

    \losted\SSO\Http\Middleware\VerifySSO::class,

Publish the configuration file with:

    php artisan vendor:publish --tag=config

In __app/Http/Controllers/Auth/LoginController.php__  add these 3 methods to authenticate and logout the users with the SSO:

```php
    public function login(Request $request, SSO $broker)
    {
        $this->validateLogin($request);

        // Login on SSO SERVER
        if($sso_user = $broker->loginUser($request->get('email'), $request->get('password'))) {

            // If the class is using the ThrottlesLogins trait, we can automatically throttle
            // the login attempts for this application. We'll key this by the username and
            // the IP address of the client making these requests into this application.
            if ($this->hasTooManyLoginAttempts($request)) {
                $this->fireLockoutEvent($request);

                return $this->sendLockoutResponse($request);
            }

            // We use the email field to link users in different apps
            $user = User::where('email', $sso_user['email'])->first();

            if($user) {
                Auth::loginUsingId($user->id);
                return $this->authenticated();
            }

        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    public function authenticated() {
        return redirect($this->redirectTo);
    }

    public function logout(Request $request, SSO $sso)
    {
        $sso->logout();

        $this->guard()->logout();

        $request->session()->flush();
        $request->session()->regenerate();

        return redirect('/');
    }
```

Don't forget to "import" the used class at the top:

```php
use App\User;
use losted\SSO\Broker as SSO;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
```

Configuration required in your .env for the broker:

    SSO_SERVER_ENDPOINT=http://sso.local/myendpoint
    SSO_BROKER_ID=broker_app_name
    SSO_BROKER_SECRET=broker_secret_5a12022f22a90

**That's it!** You can use the artisan commands below to generate secret key for your brokers and save their info in their .env files. After this all you have to do is create some users in your SSO server and start log in!

### Artisan commands

There are some **artisan commands** to help you manage brokers from the server:

    php artisan sso:list-brokers
    php artisan sso:create-broker [broker_name]
    php artisan sso:remove-broker [broker_name]

Don't hesitate to send me pull request or contact me with feedback!