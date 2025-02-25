<?php

namespace Flynsarmy\SocialLogin\SocialLoginProviders;

use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\SocialLoginProviders\SocialLoginProviderBase;
use URL;
use Log;
use Session;

class Monpay extends SocialLoginProviderBase
{
    use \Winter\Storm\Support\Traits\Singleton;

    protected $driver = 'monpay';

    protected $callback;
    protected $adapter;

    protected $request;

    /**
     * Initialize the singleton free from constructor parameters.
     */
    protected function init()
    {
        parent::init();

        $this->callback = URL::route('flynsarmy_sociallogin_provider_callback', ['Monpay'], true);
    }

    public function getAdapter()
    {
        if (!$this->adapter) {
            $this->adapter = new \Flynsarmy\SocialLogin\Classes\MonpayAdapter([
                'callback' => $this->callback,
                'settings' => $this->settings
            ]);
        }

        return $this->adapter;
    }

    public function isEnabled()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Monpay']['enabled']);
    }

    public function isEnabledForBackend()
    {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Monpay']['enabledForBackend']);
    }

    public function extendSettingsForm(Form $form)
    {
        $form->addFields([
            'providers[Monpay][noop]' => [
                'type' => 'partial',
                'path' => '$/flynsarmy/sociallogin/partials/backend/forms/settings/_monpay_info.htm',
                'tab' => 'Monpay',
            ],

            'providers[Monpay][enabled]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.enabled_on_frontend',
                'type' => 'checkbox',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_enabled_front_monpay',
                'default' => 'true',
                'span' => 'left',
                'tab' => 'Monpay',
            ],

            'providers[Monpay][enabledForBackend]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.enabled_on_backend',
                'type' => 'checkbox',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_enabled_back_monpay',
                'default' => 'false',
                'span' => 'right',
                'tab' => 'Monpay',
            ],

            'providers[Monpay][app_name]' => [
                'label' => 'flynsarmy.sociallogin::lang.settings.application_name',
                'type' => 'text',
                'default' => 'Social Login',
                'comment' => 'flynsarmy.sociallogin::lang.settings.comment_application_name',
                'tab' => 'Monpay',
            ],

            'providers[Monpay][redirect]' => [
                'label' => 'Redirect URL',
                'type' => 'text',
                'tab' => 'Monpay',
            ],

            'providers[Monpay][cors]' => [
                'label' => 'Cors URL',
                'type' => 'text',
                'tab' => 'Monpay',
            ],

            'providers[Monpay][client_id]' => [
                'label' => 'App ID',
                'type' => 'text',
                'tab' => 'Monpay',
            ],

            'providers[Monpay][client_secret]' => [
                'label' => 'App Secret',
                'type' => 'text',
                'tab' => 'Monpay',
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

        $redirectUrl = @$providers['Monpay']['redirect'];   
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
