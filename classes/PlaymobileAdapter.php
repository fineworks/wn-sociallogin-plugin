<?php 
namespace Flynsarmy\SocialLogin\Classes;

use Hybridauth\User\Profile;

use Log;
use Response;

class PlaymobileAdapter 
{
    // const MONPAY_URL = 'https://wallet.monpay.mn';
    // const TOKEN_URL = self::MONPAY_URL.'/v2/oauth/token';
    // const USER_INFO_URL = self::MONPAY_URL.'/v2/api/oauth/userinfo';

    // const INVOICE_SAVE_URL = self::MONPAY_URL.'/v3/superapp/save/invoice';
    // const INVOICE_CHECK_URL = self::MONPAY_URL.'/v3/superapp/check/invoice';

    protected $config;

    protected $token;

    protected $userData;

    protected $signature;
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function authenticateApi($code = NULL)
    {
        $providers = $this->config['settings']->get('providers', []);

        $redirectUrl = @$providers['Monpay']['redirect'];
        $corsUrl = @$providers['Monpay']['cors'];

        $clientId = @$providers['Monpay']['client_id'];
        $clientSecret = @$providers['Monpay']['client_secret'];

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];
        $respHeaders = [
            'Content-Type' => 'application/json'
        ];
        $respHeaders['Access-Control-Allow-Origin'] = $corsUrl;
        $respHeaders['Access-Control-Allow-Methods'] = "GET, POST, OPTIONS, PUT, DELETE";
        $respHeaders['Access-Control-Allow-Headers'] = "*"; 

        if($code) {
            $tokenResponse = $this->post(self::TOKEN_URL, [
                                    'grant_type' => 'authorization_code',
                                    'client_id' => $clientId,
                                    'client_secret' => $clientSecret,
                                    'code' => $code,
                                    'redirect_uri' => $redirectUrl
                                ], $headers, true, false);
            Log::info([
                                    'grant_type' => 'authorization_code',
                                    'client_id' => $clientId,
                                    'client_secret' => $clientSecret,
                                    'code' => $code,
                                    'redirect_uri' => $redirectUrl
                                ]);
            if(isset($tokenResponse['access_token'])) {
                $this->token = $tokenResponse['access_token'];
            }

            Log::info($tokenResponse);

            return Response::json($tokenResponse, 200, $respHeaders);
        }

        return Response::json(['code' => 404, 'error' => 'code is required'], 200, $respHeaders);
    }

    public function getUserData($token)
    {
        $providers = $this->config['settings']->get('providers', []);
        $corsUrl = @$providers['Monpay']['cors'];

        $respHeaders = [
            'Content-Type' => 'application/json'
        ];
        $respHeaders['Access-Control-Allow-Origin'] = $corsUrl;
        $respHeaders['Access-Control-Allow-Methods'] = "GET, POST, OPTIONS, PUT, DELETE";
        $respHeaders['Access-Control-Allow-Headers'] = "*"; 

        $this->token = $token;

        $headers = [
            'Authorization: Bearer '.$this->token,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        Log::info($headers);
        $this->userData = $this->get(self::USER_INFO_URL, $headers);

        Log::info($this->userData);

        return Response::json($this->userData, 200, $respHeaders);
    }

    public function getUserDataOptions() {
        $respHeaders = [
            'Content-Type' => 'application/json'
        ];
        $respHeaders['Access-Control-Allow-Origin'] = "*";
        $respHeaders['Access-Control-Allow-Methods'] = "GET, POST, OPTIONS, PUT, DELETE";
        $respHeaders['Access-Control-Allow-Headers'] = "*"; 

        return Response::json(["status" => "OK"], 200, $respHeaders);
    }

    public function disconnect()
    {

    }

    public function getUserProfile()
    {
        $userProfile = new Profile();

        if($this->userData && isset($this->userData["result"])) {
            $userProfile->identifier = $this->userData["result"]['userId'];
            if(isset($this->userData["result"]['userEmail'])) {
                $userProfile->email = strtolower($this->userData["result"]['userEmail']);
            }
            else {
                $userProfile->email = 'phone_'.$this->userData["result"]['userId']."@playmobile.app";
            }
            $userProfile->firstName = $this->userData["result"]['userFirstname'];
            $userProfile->lastName = $this->userData["result"]['userLastname'];
            $userProfile->phone = isset($this->userData["result"]['userPhone']) ? $this->userData["result"]['userPhone'] : '';
            $userProfile->photoURL = '';
        }

        return $userProfile;
    }

    public function getAccessToken()
    {
        return [
            "access_token" => $this->token
        ];
    }


    private function post($url, $param, $headers = NULL, $return = true, $tojson = true) {

        if(!$headers) {
            $headers = [
                'Content-Type: application/json',
            ];
        }

        $curl = curl_init($url);

        $payload = $param;
        if($tojson) {
            $payload = json_encode($param);
        }
        else {
            $payload = http_build_query($param);
        }

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        if($return) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        }
        

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    private function get($url, $headers = NULL) {

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        if($headers) { 
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}
