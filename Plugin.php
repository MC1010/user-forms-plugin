<?php namespace WebBro\UserManagement;

use System\Classes\PluginBase;
use System\Classes\SettingsManager;

class Plugin extends PluginBase
{
    public $require = ['RainLab.User'];
    
    public function pluginDetails()
    {
        return [
            'name'        => 'webbro.usermanagement::lang.plugin.name',
            'description' => 'webbro.usermanagement::lang.plugin.description',
            'author'      => 'Thomas Ralph',
            'icon'        => 'icon-user',
            'homepage'    => ''
        ];
    }
    
    public function registerComponents()
    {
        return [
            \WebBro\UserManagement\Components\Login::class       => 'login',
            \WebBro\UserManagement\Components\Register::class    => 'register',
            \WebBro\UserManagement\Components\Deactivate::class  => 'deactivate',
//             \WebBro\UserManagement\Components\Activate::class    => 'activate',
            \WebBro\UserManagement\Components\ResetPassword::class    => 'resetpassword',
        ];
    }
}
