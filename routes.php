<?php
use Illuminate\Http\Request;

// http://home.flynsarmy.com/flynsarmy/sociallogin/Google?s=/&f=/login
Route::get(
    'flynsarmy/sociallogin/{provider}',
    [
        "as" => "flynsarmy_sociallogin_provider",
        'middleware' => ['web'],
        function ($provider_name, $action = "") {
            $success_redirect = Input::get('s', '/');
            $error_redirect = Input::get('f', '/login');
            Session::flash('flynsarmy_sociallogin_successredirect', $success_redirect);
            Session::flash('flynsarmy_sociallogin_errorredirect', $error_redirect);

            $provider_class = Flynsarmy\SocialLogin\Classes\ProviderManager::instance()
                ->resolveProvider($provider_name);

            if (!$provider_class) {
                return Redirect::to($error_redirect)->withErrors("Unknown login provider: $provider_name.");
            }

            $provider = $provider_class::instance();

            return $provider->redirectToProvider();
        }
    ]
)->where(['provider' => '[A-Z][a-zA-Z ]+']);

Route::get('flynsarmy/sociallogin/{provider}/api',
    [
        'as' => 'flynsarmy_sociallogin_provider_api',
        'middleware' => ['web'],
        function ($provider_name, Request $request) {

            $code = $request->get('code');

            $provider_class = Flynsarmy\SocialLogin\Classes\ProviderManager::instance()
                ->resolveProvider($provider_name);

            if (!$provider_class) {
                Log::info("provider: $provider_name.");
                //return Redirect::to($error_redirect)->withErrors("Unknown login provider: $provider_name.");
            }

            $provider = $provider_class::instance();

            if(isset($code) && strlen($code) > 0) {
                $adapter = $provider->getAdapter();
                if(isset($adapter)) {
                    return $adapter->authenticateApi($code);
                }
            }
        }
    ]
);

Route::get('flynsarmy/sociallogin/{provider}/userinfo',
    [
        'as' => 'flynsarmy_sociallogin_provider_userinfo',
        'middleware' => ['web'],
        function ($provider_name, Request $request) {

            $token = $request->get('token');

            $provider_class = Flynsarmy\SocialLogin\Classes\ProviderManager::instance()
                ->resolveProvider($provider_name);

            if (!$provider_class) {
                Log::info("provider: $provider_name.");
                //return Redirect::to($error_redirect)->withErrors("Unknown login provider: $provider_name.");
            }

            $provider = $provider_class::instance();

            if(isset($token) && strlen($token) > 0) {
                $adapter = $provider->getAdapter();
                if(isset($adapter)) {
                    $api_response = $adapter->getUserData($token);

                    $provider_details = [
                        'provider_id' => $provider_name,
                        'provider_token' => [
                            "access_token" => $token,
                            "token_type" => "Bearer"
                        ]
                    ];


                    $user_details = $adapter->getUserProfile();

                    if(isset($user_details->email)) {
                        $user = \Flynsarmy\SocialLogin\Classes\UserManager::instance()->find(
                            $provider_details,
                            $user_details
                        );
                    }

                    return $api_response;
                }
            }
        }   
    ]
);

Route::options('flynsarmy/sociallogin/{provider}/userinfo',
    [
        'as' => 'flynsarmy_sociallogin_provider_userinfo',
        'middleware' => ['web'],
        function ($provider_name, Request $request) {
            $provider_class = Flynsarmy\SocialLogin\Classes\ProviderManager::instance()
                ->resolveProvider($provider_name);

            if (!$provider_class) {
                Log::info("provider: $provider_name.");
                //return Redirect::to($error_redirect)->withErrors("Unknown login provider: $provider_name.");
            }

            $provider = $provider_class::instance();

            $adapter = $provider->getAdapter();
            if(isset($adapter)) {
                return $adapter->getUserDataOptions();
            }
        }
    ]
);

Route::any(
    'flynsarmy/sociallogin/{provider}/callback',
    [
        'as' => 'flynsarmy_sociallogin_provider_callback',
        'middleware' => ['web'],
        function ($provider_name, Request $request) {
            $success_redirect = Session::get('flynsarmy_sociallogin_successredirect', '/');
            $error_redirect = Session::get('flynsarmy_sociallogin_errorredirect', '/login');


            $provider_class = Flynsarmy\SocialLogin\Classes\ProviderManager::instance()
                ->resolveProvider($provider_name);

            if (!$provider_class) {
                Log::info("provider: $provider_name.");
                return Redirect::to($error_redirect)->withErrors("Unknown login provider: $provider_name.");
            }

            $provider = $provider_class::instance();

            

            try {
                // This will contain [token => ..., email => ..., ...]
                if($provider_name == 'Golomt' || $provider_name == 'Khaan') {
                    $provider->setRequest($request);
                } 
                $provider_response = $provider->handleProviderCallback($provider_name);

                

                if (!is_array($provider_response)) {
                    return Redirect::to($error_redirect);
                }
            } catch (Exception $e) {
                // Log the error
                Log::error($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());

                return Redirect::to($error_redirect)->withErrors([$e->getMessage()]);
            }

            Log::info('provider_response: '.json_encode($provider_response));
            ksort($provider_response['token']);

            $provider_details = [
                'provider_id' => $provider_name,
                'provider_token' => $provider_response['token'],
            ];
            $user_details = $provider_response['profile'];

            // Backend logins
            if ($success_redirect == Backend::url()) {
                $user = Flynsarmy\SocialLogin\Classes\UserManager::instance()
                    ->findBackendUserByEmail($user_details->email);

                if (!$user) {
                    throw new Winter\Storm\Auth\AuthException(sprintf(
                        'Administrator with email address "%s" not found.',
                        $user_details['email']
                    ));
                }

                // Support custom login handling
                $result = Event::fire('flynsarmy.sociallogin.handleBackendLogin', [
                    $provider_details, $provider_response, $user
                ], true);
                if ($result) {
                    return $result;
                }

                BackendAuth::login($user, true);

                // Load version updates
                System\Classes\UpdateManager::instance()->update();

                // Log the sign in event
                Backend\Models\AccessLog::add($user);

            // Frontend Logins
            } else {
                // Grab the user associated with this provider. Creates or attach one if need be.
                //Log::info("user_detail: ".print_r($user_details->email, true));
                if(isset($user_details->email)) {
                    $user = \Flynsarmy\SocialLogin\Classes\UserManager::instance()->find(
                        $provider_details,
                        $user_details
                    );
                }

                // Support custom login handling
                if(isset($user)) {
                    $result = Event::fire('flynsarmy.sociallogin.handleLogin', [
                        $provider_details, $provider_response, $user
                    ], true);
                    if ($result) {
                        return $result;
                    }
                }

                if(isset($user)) {
                    Auth::login($user);
                    Log::info('User logged in : '.$user->id);
                }

                if($provider_name == 'Golomt') {
                    Log::info("Redirect URL: ".print_r($provider_response['redirect'], true));
                    if(isset($provider_response['redirect'])) {
                        return Redirect::to($provider_response['redirect']);
                    }
                } 

                if($provider_name == 'Khaan') {
                    Log::info("Redirect URL: ".print_r($provider_response['redirect'], true));
                    if(isset($provider_response['redirect'])) {
                        return Redirect::to($provider_response['redirect']);
                    }
                } 
            }

            return Redirect::to($success_redirect);
        }
    ]
);
