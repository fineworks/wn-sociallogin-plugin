<?php 
namespace Flynsarmy\SocialLogin\Classes;

use Hybridauth\User\Profile;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Log;

class KhaanAdapter 
{
    protected $config;

    protected $token;

    protected $userData;

    protected $signature;

    public const KHAAN_URL = 'https://doob.world:7005';
    public const CODE_URL = self::KHAAN_URL.'/v3/superapp/oauth/code';
    public const TOKEN_URL = self::KHAAN_URL.'/v3/superapp/oauth/token';
    public const TOKEN_REFRESH_URL = self::KHAAN_URL.'/v3/superapp/oauth/refreshToken';
    public const CLIENT_TOKEN_URL = self::KHAAN_URL.'/v1/wallet/auth/token';
    public const USER_INFO_URL = self::KHAAN_URL.'/v3/superapp/user/info';
    public const INVOICE_SAVE_URL = self::KHAAN_URL.'/v3/superapp/save/invoice';
    public const INVOICE_CHECK_URL = self::KHAAN_URL.'/v3/superapp/check/invoice';
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function authenticate($request = NULL)
    {
        if($request) {
            $code = $request->get('code');

            if(isset($this->config["settings"])) {
                $settings = $this->config["settings"];
    
                if(is_object($settings)) {
                    $providers = $settings->get('providers', []);
    
                    $client_id = $providers['Khaan']['clientId'];
                    $client_secret = $providers['Khaan']['clientSecret'];
                    $redirect_url = $providers['Khaan']['redirect'];
                    
                    $requestData = [
                        "code" => $code,
                        "redirect_uri" => $redirect_url,
                        "client_id" => $client_id,
                        "client_secret" => $client_secret
                    ];

                    $requestHeader = [
                        'Content-Type: application/x-www-form-urlencoded'
                    ];

                    Log::info('KhaanAdapter:token request Header: '.print_r($requestHeader, true));
                    Log::info('KhaanAdapter:token request Data: '.print_r($requestData, true));

                    $responseToken = $this->post(self::TOKEN_URL, $requestData, $requestHeader, true, false);
                    
                    Log::info('KhaanAdapter:token response: '.print_r($responseToken, true));

                    if(isset($responseToken["access_token"])) {
                        $this->token = $responseToken["access_token"];

                        $requestHeader = [
                            'Authorization: Bearer '.$this->token
                        ];

                        $responseUser = $this->get(self::USER_INFO_URL, $requestHeader);
                        Log::info('KhaanAdapter: User response: '.print_r($responseUser, true));
    
        
                        if(isset($responseUser)) {
                            $this->userData = $responseUser;
                            return true;
                        }
                    }
                }
            }
            
        }

        
        



        return true;
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
                $userProfile->email = 'digipay_'.(isset($this->userData['firstNameEn']) ? $this->userData['firstNameEn'] : '').'_'.(isset($this->userData['lastNameEn']) ? $this->userData['lastNameEn'] : '')."@digipay.app";
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


    private function postHeaders() {
        return [
            'Content-Type: application/json'
        ];
    }

    private function post($url, $param, $headers, $return, $tojson = true) {

        if(!$headers) {
            $headers = [
                'Content-Type: application/json',
            ];
        }

        $curl = curl_init($url);

        $payload = $param;
        if($tojson) {
            $payload = json_encode( $param );
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