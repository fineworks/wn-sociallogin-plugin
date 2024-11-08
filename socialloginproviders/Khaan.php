<?php

namespace Flynsarmy\SocialLogin\SocialLoginProviders;

use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\SocialLoginProviders\SocialLoginProviderBase;
use URL;
use Log;

class Khaan extends SocialLoginProviderBase
{
    use \Winter\Storm\Support\Traits\Singleton;

    protected $driver = 'khaan';

    protected $callback;
    protected $adapter;

    protected $request;

    /**
     * Initialize the singleton free from constructor parameters.
     */
    protected function init()
    {
        parent::init();

        $this->callback = URL::route('flynsarmy_sociallogin_provider_callback', ['Khaan'], true);
    }

    public function getAdapter()
    {
        if (!$this->adapter) {
            $this->adapter = new \Flynsarmy\SocialLogin\Classes\KhaanAdapter([
                'callback' => $this->callback,
                'settings' => $this->settings
            ]);
        }

        return $this->adapter;
    }

    public function isEnabled()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Khaan']['enabled']);
    }

    public function isEnabledForBackend()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Khaan']['enabledForBackend']);
    }

    public function extendSettingsForm(Form $form)
    {
        $form->addFields([
            'providers[Khaan][noop]' => [
                'type' => 'partial',
                'path' => '$/flynsarmy/sociallogin/partials/backend/forms/settings/_khaan_info.htm',
                'tab' => 'Khaan',
            ],

            'providers[Khaan][enabled]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.enabled_on_frontend',
                'type' => 'checkbox',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_enabled_front_khaan',
                'default' => 'true',
                'span' => 'left',
                'tab' => 'Khaan',
            ],

            'providers[Khaan][enabledForBackend]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.enabled_on_backend',
                'type' => 'checkbox',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_enabled_back_khaan',
                'default' => 'false',
                'span' => 'right',
                'tab' => 'Khaan',
            ],

            'providers[Khaan][app_name]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.application_name',
                'type' => 'text',
                'default' => 'Social Login',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_application_name',
                'tab' => 'Khaan',
            ],

            'providers[Khaan][redirect]' => [
                'label' => 'Redirect URL',
                'type' => 'text',
                'tab' => 'Khaan',
            ],

            'providers[Khaan][apiurl]' => [
                'label' => 'Api URL',
                'type' => 'text',
                'tab' => 'Khaan',
            ],

            'providers[Khaan][clientId]' => [
                'label' => 'Client ID',
                'type' => 'text',
                'tab' => 'Khaan',
            ],

            'providers[Khaan][clientSecret]' => [
                'label' => 'Client Secret',
                'type' => 'text',
                'tab' => 'Khaan',
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

        $redirectUrl = @$providers['Khaan']['redirect'];   
        $this->getAdapter()->authenticate($this->request);
        


        $token = $this->getAdapter()->getAccessToken();
        $profile = $this->getAdapter()->getUserProfile();

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
