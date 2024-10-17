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

    public const KHAAN_URL = 'https://digipay.mn';
    public const CODE_URL = KHAAN_URL.'/v3/superapp/oauth/code';
    public const TOKEN_URL = KHAAN_URL.'/v3/superapp/oauth/token';
    public const TOKEN_REFRESH_URL = KHAAN_URL.'/v3/superapp/oauth/refreshToken';
    public const CLIENT_TOKEN_URL = KHAAN_URL.'/v1/wallet/auth/token';
    public const USER_INFO_URL = KHAAN_URL.'/v3/superapp/user/info';
    public const INVOICE_SAVE_URL = KHAAN_URL.'/v3/superapp/save/invoice';
    public const INVOICE_CHECK_URL = KHAAN_URL.'/v3/superapp/check/invoice';
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function authenticate($request = NULL)
    {

        if(isset($this->config["settings"])) {
            $settings = $this->config["settings"];

            if(is_object($settings)) {
                $providers = $settings->get('providers', []);

                $client_id = $providers['Khaan']['clientId'];
                $client_secret = $providers['Khaan']['clientSecret'];

                $requestData = [
                    "clientId" => $client_id,
                    "redirectUrl" => "",
                    "scope" => "scope",
                    "userId" => "userId",
                    "response_type" => "code"
                ];

                $response = $this->post(self::CODE_URL, $requestData, [], true);
                Log::info('KhaanAdapter:response: '.print_r($response, true));

                if(isset($response["siteUrl"])) {

                    $requestData = [
                        "code" => $code,
                        "redirect_uri" => "",
                        "client_id" => $client_id,
                        "client_secret" => $client_secret
                    ];

                    $requestHeader = [
                        'Content-Type: application/x-www-form-urlencoded'
                    ];

                    $responseToken = $this->post(self::TOKEN_URL, $requestData, $requestHeader, true);
                    
                    Log::info('KhaanAdapter:token response: '.print_r($responseToken, true));

                    $requestHeader = [
                        'Authorization: Bearer '.$token
                    ];
                    $responseUser = $this->get(self::USER_INFO_URL, $requestHeader);
                    Log::info('KhaanAdapter:token response: '.print_r($responseToken, true));


                    
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
            $userProfile->identifier = $this->userData['individualId'];
            if(isset($this->userData['email'])) {
                $userProfile->email = strtolower($this->userData['email']);
            }
            else {
                $userProfile->email = 'social'.$this->userData['individualId']."@socialpay.app";
            }
            $userProfile->firstName = $this->userData['firstName'];
            $userProfile->lastName = $this->userData['lastName'];
            $userProfile->phone = $this->userData['mobileNumber'];
            $userProfile->photoURL = $this->userData['imgUrl'];
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

    private function post($url, $param, $headers, $return) {

        if(!$headers) {
            $headers = [
                'Content-Type: application/json',
            ];
        }

        $curl = curl_init($url);

        $payload = json_encode( $param );
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