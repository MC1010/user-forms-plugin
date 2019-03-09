<?php namespace WebBro\UserForms;

use System\Classes\PluginBase;
use System\Classes\SettingsManager;

class Plugin extends PluginBase
{
    public $require = ['RainLab.User'];
    
    public function pluginDetails()
    {
        return [
            'name'        => 'webbro.userforms::lang.plugin.name',
            'description' => 'webbro.userforms::lang.plugin.description',
            'author'      => 'Thomas Ralph',
            'icon'        => 'icon-user',
            'homepage'    => ''
        ];
    }
    
    public function registerComponents()
    {
        return [
            \WebBro\UserForms\Components\Login::class       => 'login',
            \WebBro\UserForms\Components\Register::class    => 'register',
            \WebBro\UserForms\Components\Deactivate::class  => 'deactivate',
//             \WebBro\UserManagement\Components\Activate::class    => 'activate',
            \WebBro\UserForms\Components\ResetPassword::class    => 'resetpassword',
        ];
    }
}
