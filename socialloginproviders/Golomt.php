<?php

namespace Flynsarmy\SocialLogin\SocialLoginProviders;

use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\SocialLoginProviders\SocialLoginProviderBase;
use URL;
use Log;

class Golomt extends SocialLoginProviderBase
{
    use \Winter\Storm\Support\Traits\Singleton;

    protected $driver = 'golomt';

    protected $callback;
    protected $adapter;

    protected $request;

    /**
     * Initialize the singleton free from constructor parameters.
     */
    protected function init()
    {
        parent::init();

        $this->callback = URL::route('flynsarmy_sociallogin_provider_callback', ['Golomt'], true);
    }

    public function getAdapter()
    {
        if (!$this->adapter) {
            $this->adapter = new \Flynsarmy\SocialLogin\Classes\GolomtAdapter([
                'callback' => $this->callback
            ]);
        }

        return $this->adapter;
    }

    public function isEnabled()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Golomt']['enabled']);
    }

    public function isEnabledForBackend()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Golomt']['enabledForBackend']);
    }

    public function extendSettingsForm(Form $form)
    {
        $form->addFields([
            'providers[Golomt][noop]' => [
                'type' => 'partial',
                'path' => '$/flynsarmy/sociallogin/partials/backend/forms/settings/_golomt_info.htm',
                'tab' => 'Golomt',
            ],

            'providers[Golomt][enabled]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.enabled_on_frontend',
                'type' => 'checkbox',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_enabled_front_golomt',
                'default' => 'true',
                'span' => 'left',
                'tab' => 'Golomt',
            ],

            'providers[Golomt][enabledForBackend]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.enabled_on_backend',
                'type' => 'checkbox',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_enabled_back_golomt',
                'default' => 'false',
                'span' => 'right',
                'tab' => 'Golomt',
            ],

            'providers[Golomt][app_name]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.application_name',
                'type' => 'text',
                'default' => 'Social Login',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_application_name',
                'tab' => 'Golomt',
            ],

            'providers[Golomt][redirect]' => [
                'label' => 'Redirect URL',
                'type' => 'text',
                'tab' => 'Golomt',
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

        $redirectUrl = @$providers['Golomt']['redirect'];   
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
