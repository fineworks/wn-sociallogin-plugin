<?php

namespace Flynsarmy\SocialLogin;

use App;
use Backend;
use Event;
use URL;
use Illuminate\Foundation\AliasLoader;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use Winter\User\Models\User;
use Winter\User\Controllers\Users as UsersController;
use Backend\Widgets\Form;
use Flynsarmy\SocialLogin\Classes\ProviderManager;

/**
 * SocialLogin Plugin Information File
 *
 * http://www.mrcasual.com/on/coding/laravel4-package-management-with-composer/
 * https://cartalyst.com/manual/sentry-social
 *
 */
class Plugin extends PluginBase
{
    // Make this plugin run on updates page
    public $elevated = true;

    public $require = ['Winter.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'flynsarmy.sociallogin::lang.plugin.name',
            'description' => 'flynsarmy.sociallogin::lang.plugin.desc',
            'author'      => 'Flynsarmy',
            'icon'        => 'icon-users'
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'flynsarmy.sociallogin::lang.settings.menu_label',
                'description' => 'flynsarmy.sociallogin::lang.settings.menu_description',
                'category'    => SettingsManager::CATEGORY_USERS,
                'icon'        => 'icon-users',
                'class'       => 'Flynsarmy\SocialLogin\Models\Settings',
                'order'       => 600,
                'permissions' => ['Winter.users.access_settings'],
            ]
        ];
    }

    public function registerComponents()
    {
        return [
            'Flynsarmy\SocialLogin\Components\SocialLogin' => 'sociallogin',
        ];
    }

    public function boot()
    {
        User::extend(function ($model) {
            $model->hasMany['flynsarmy_sociallogin_providers'] = ['Flynsarmy\SocialLogin\Models\Provider'];
        }); 

        // Add 'Social Logins' column to users list
        UsersController::extendListColumns(function ($widget, $model) {
            if (!$model instanceof \Winter\User\Models\User) {
                return;
            }

            $widget->addColumns([
                'flynsarmy_sociallogin_providers' => [
                    'label'      => 'Social Logins',
                    'type'       => 'partial',
                    'path'       => '~/plugins/flynsarmy/sociallogin/models/provider/_provider_column.htm',
                    'align'      => 'center',
                    'searchable' => false
                ],
                'phone' => [
                    'label'      => 'Phone',
                    'type'       => 'text',
                    'searchable' => true,
                    'invisible'  => false,
                ]
            ]);
        });

        UsersController::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof \Winter\User\Models\User) {
                return;
            }

            if ($context == 'update' || $context == 'create') {
                $form->addFields([
                    'phone' => [
                        'label'   => 'Phone',
                        'type'    => 'text',
                    ],
                ]);
            }
        });

        // Generate Social Login settings form
        Event::listen('backend.form.extendFields', function (Form $form) {
            if (!$form->getController() instanceof \System\Controllers\Settings) {
                return;
            }
            if (!$form->model instanceof \Flynsarmy\SocialLogin\Models\Settings) {
                return;
            }

            foreach (ProviderManager::instance()->listProviders() as $class => $details) {
                $classObj = $class::instance();
                $classObj->extendSettingsForm($form);
            }
        });

        // Add 'Social Providers' field to edit users form
        Event::listen('backend.form.extendFields', function ($widget) {
            if (!$widget->getController() instanceof \Winter\User\Controllers\Users) {
                return;
            }
            if (!$widget->model instanceof \Winter\User\Models\User) {
                return;
            }
            if (!in_array($widget->getContext(), ['update', 'preview'])) {
                return;
            }

            $widget->addFields([
                'flynsarmy_sociallogin_providers' => [
                    'label'   => 'flynsarmy.sociallogin::lang.user.social_providers',
                    'type'    => 'Flynsarmy\SocialLogin\FormWidgets\LoginProviders',
                ],
            ], 'secondary');

            $widget->addFields([
                'phone' => [
                    'label'   => 'Phone',
                    'type'    => 'text',
                ],
            ]);
        });

        // Add backend login provider integration
        Event::listen('backend.auth.extendSigninView', function () {
            $providers = ProviderManager::instance()->listProviders();

            $social_login_links = [];
            foreach ($providers as $provider_class => $provider_details) {
                if ($provider_class::instance()->isEnabledForBackend()) {
                    $social_login_links[$provider_details['alias']] =
                        URL::route(
                            'flynsarmy_sociallogin_provider',
                            [$provider_details['alias']]
                        ) . '?s=' . Backend::url() . '&f=' . Backend::url('backend/auth/signin');
                }
            }

            if (!count($social_login_links)) {
                return;
            }

            require __DIR__ . '/partials/backend/_login.htm';
        });
    }

    // @phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function register_flynsarmy_sociallogin_providers()
    {
        return [
            '\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Google' => [
                'label' => 'Google',
                'alias' => 'Google',
                'description' => 'Log in with Google'
            ],
            '\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Twitter' => [
                'label' => 'Twitter',
                'alias' => 'Twitter',
                'description' => 'Log in with Twitter'
            ],
            '\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Facebook' => [
                'label' => 'Facebook',
                'alias' => 'Facebook',
                'description' => 'Log in with Facebook'
            ],
            '\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Golomt' => [
                'label' => 'Golomt',
                'alias' => 'Golomt',
                'description' => 'Log in with Golomt'
            ],
            '\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Khaan' => [
                'label' => 'Khaan',
                'alias' => 'Khaan',
                'description' => 'Log in with Khaan'
            ],
            '\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Monpay' => [
                'label' => 'Monpay',
                'alias' => 'Monpay',
                'description' => 'Log in with Monpay'
            ],
            '\\Flynsarmy\\SocialLogin\\SocialLoginProviders\\Playmobile' => [
                'label' => 'Playmobile',
                'alias' => 'Playmobile',
                'description' => 'Log in with Playmobile'
            ],
        ];
    }
}
