<?php 
namespace Flynsarmy\SocialLogin\Classes;

use Hybridauth\User\Profile;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Log;

class GolomtAdapter 
{
    protected $config;

    protected $token;

    protected $userData;

    protected $signature;

    //public const CHECK_URL = 'https://sp-api.golomtbank.com/api';
    public const CHECK_URL = 'https://sp-api.golomtbank.com/api/utility/miniapp/token/check?language=mn';
    public const GOLOMT_CERT_ID = 'travelsim';
    public const GOLOMT_PUBLIC_KEY = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC3OEtuke6RJ/p1mL2BOIVc0QUZrvDpKHIguZQ8rN8UuxpIZXls8yegVY5zx/W7kQbg/w0mk0r+Grwufr3PGU/k3fgflCI3jdHwXOm2K0EtMtlZBmH4bVnrRHw+y1CGdE0iGUor12rfvvu+krbPHc+ntFqx8fYvx8Gpp7ySJ/uuowIDAQAB';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function authenticate($request = NULL)
    {
        
        if($request) {
            
            $token = NULL;//$request->input('token', NULL);
            $token = $request->get('token');
            if(str_starts_with($token, '?token=')) {
                $token = substr($token, 7);
            }

            if(isset($token))  {
                $this->token = $token;

                // Prepare get user info
                $requestData = [
                    'token' => $this->token
                ];

                $jsonParams = json_encode( $requestData );  

                $this->signature = $this->encodeSignature(hash("sha256", $jsonParams));

                Log::info('GolomtAdapter:Token: '.$token);
                Log::info('GolomtAdapter:URL: '.self::CHECK_URL);
                Log::info('GolomtAdapter:Method: POST');
                Log::info('GolomtAdapter:Header: '.json_encode($this->postHeaders()));
                Log::info('GolomtAdapter:Body: '.$jsonParams);
                
                $response = $this->post(self::CHECK_URL, $requestData, $this->postHeaders(), true);
                Log::info('GolomtAdapter:response: '.print_r($response, true));

                if(isset($response['individualId'])) {
                    $this->userData = $response;
                    return true;
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
            $userProfile->email = $this->userData['email'];
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

    private function encodeSignature($data) {
        $publicKey = PublicKeyLoader::load($this::GOLOMT_PUBLIC_KEY);
        $key = $publicKey->withPadding(RSA::ENCRYPTION_PKCS1);
        return base64_encode($key->encrypt($data));
    }

    private function postHeaders() {
        return [
            'Content-Type: application/json',
            'X-Golomt-Cert-Id: '.self::GOLOMT_CERT_ID,
            'X-Golomt-Signature: '.$this->signature
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

    private function get($url) {

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}