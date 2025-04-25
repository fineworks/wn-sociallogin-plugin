<?php

namespace Flynsarmy\SocialLogin\SocialLoginProviders;

use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\SocialLoginProviders\SocialLoginProviderBase;
use URL;
use Log;
use Session;

class Playmobile extends SocialLoginProviderBase
{
    use \Winter\Storm\Support\Traits\Singleton;

    protected $driver = 'playmobile';

    protected $callback;
    protected $adapter;

    protected $request;

    /**
     * Initialize the singleton free from constructor parameters.
     */
    protected function init()
    {
        parent::init();

        $this->callback = URL::route('flynsarmy_sociallogin_provider_callback', ['Playmobile'], true);
    }

    public function getAdapter()
    {
        if (!$this->adapter) {
            $this->adapter = new \Flynsarmy\SocialLogin\Classes\PlaymobileAdapter([
                'callback' => $this->callback,
                'settings' => $this->settings
            ]);
        }

        return $this->adapter;
    }

    public function isEnabled()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Playmobile']['enabled']);
    }

    public function isEnabledForBackend()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Playmobile']['enabledForBackend']);
    }

    public function extendSettingsForm(Form $form)
    {
        $form->addFields([
            'providers[Playmobile][noop]' => [
                'type' => 'partial',
                'path' => '$/flynsarmy/sociallogin/partials/backend/forms/settings/_playmobile_info.htm',
                'tab' => 'Playmobile',
            ],

            'providers[Playmobile][enabled]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.enabled_on_frontend',
                'type' => 'checkbox',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_enabled_front_playmobile',
                'default' => 'true',
                'span' => 'left',
                'tab' => 'Playmobile',
            ],

            'providers[Playmobile][enabledForBackend]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.enabled_on_backend',
                'type' => 'checkbox',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_enabled_back_playmobile',
                'default' => 'false',
                'span' => 'right',
                'tab' => 'Playmobile',
            ],

            'providers[Playmobile][app_name]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.application_name',
                'type' => 'text',
                'default' => 'Social Login',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_application_name',
                'tab' => 'Playmobile',
            ],

            'providers[Playmobile][redirect]' => [
                'label' => 'Redirect URL',
                'type' => 'text',
                'tab' => 'Playmobile',
            ],

            'providers[Playmobile][cors]' => [
                'label' => 'Cors URL',
                'type' => 'text',
                'tab' => 'Playmobile',
            ],

            'providers[Playmobile][client_id]' => [
                'label' => 'App ID',
                'type' => 'text',
                'tab' => 'Playmobile',
            ],

            'providers[Playmobile][client_secret]' => [
                'label' => 'App Secret',
                'type' => 'text',
                'tab' => 'Playmobile',
            ],

        ], 'primary');
    }

    public function redirectToProvider()
    {
        // if ($this->getAdapter()->isConnected()) {
        //     return \Redirect::to($this->callback);
        // }

        // $this->getAdapter()->authenticate();
    }

    /**
     * Handles redirecting off to the login provider
     *
     * @return array ['token' => array $token, 'profile' => \Hybridauth\User\Profile]
     */
    public function handleProviderCallback()
    {
        $providers = $this->settings->get('providers', []);

        $redirectUrl = @$providers['Playmobile']['redirect'];   
        $this->getAdapter()->authenticate($this->request);
        


        $token = $this->getAdapter()->getAccessToken();
        $profile = $this->getAdapter()->getUserProfile();

        if($token) {
            Session::put('provider.id', $this->driver);
            Session::put('provider.token', $token);
            Session::put('provider.saved_at', date('Y-m-d H:i:s'));
        }

        // Don't cache anything or successive logins to different accounts
        // will keep logging in to the first account
        $this->getAdapter()->disconnect();

        return [
            'token' => $token,
            'profile' => $profile,
            'redirect' => $redirectUrl
        ];
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }
}
