<?php 
namespace Flynsarmy\SocialLogin\Classes;

use Hybridauth\User\Profile;

use Log;
use Fineworks\Khaanapi\Classes\KhaanProvider;

class KhaanAdapter 
{
    protected $config;

    protected $token;

    protected $userData;

    protected $signature;
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function authenticate($request = NULL)
    {
        if($request) {
            $code = $request->get('code');

            if(class_exists(KhaanProvider::class)) {
                $khProvider = new KhaanProvider;

                $access_token = $khProvider->getToken($code);

                if($access_token) {
                    $this->token = $access_token;

                    $userData = $khProvider->getUserInfo($access_token);

                    if(isset($userData)) {
                        $this->userData = $userData;
                        return true;
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

}