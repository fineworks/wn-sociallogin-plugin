<?php 
namespace Flynsarmy\SocialLogin\Classes;

use Hybridauth\User\Profile;

use Log;

class MonpayAdapter 
{
    const MONPAY_URL = 'https://z-wallet.monpay.mn/';
    const TOKEN_URL = self::MONPAY_URL.'v2/oauth/token';
    const TOKEN_REFRESH_URL = self::MONPAY_URL.'/v3/superapp/oauth/refreshToken';
    const CLIENT_TOKEN_URL = self::MONPAY_URL.'/v1/wallet/auth/token';
    const USER_INFO_URL = self::MONPAY_URL.'/v3/superapp/user/info';
    const INVOICE_SAVE_URL = self::MONPAY_URL.'/v3/superapp/save/invoice';
    const INVOICE_CHECK_URL = self::MONPAY_URL.'/v3/superapp/check/invoice';

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

            if(isset($tokenResponse['access_token'])) {
                $this->token = $tokenResponse['access_token'];
            }

            return response()->json($tokenResponse)->withHeaders($respHeaders);
        }

        return response()->json(['code' => 404, 'error' => 'code is required'])->withHeaders($respHeaders);
    }

    public function disconnect()
    {

    }

    public function getUserProfile()
    {
        $userProfile = new Profile();

        if($this->userData) {
            $userProfile->identifier = $this->userData['email'];
            if(isset($this->userData['email'])) {
                $userProfile->email = strtolower($this->userData['email']);
            }
            else {
                $userProfile->email = 'monpay_'.(isset($this->userData['firstNameEn']) ? $this->userData['firstNameEn'] : '').'_'.(isset($this->userData['lastNameEn']) ? $this->userData['lastNameEn'] : '')."@digipay.app";
            }
            $userProfile->firstName = $this->userData['firstName'];
            $userProfile->lastName = $this->userData['lastName'];
            $userProfile->phone = $this->userData['phone'];
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