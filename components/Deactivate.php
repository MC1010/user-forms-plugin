<?php namespace WebBro\UserForms\Components;

use Auth;
use RainLab\User\Components\Account;
use October\Rain\Exception\ValidationException;
use Lang;
use Cms\Classes\Page;

class Deactivate extends Account
{
    public function componentDetails()
    {
        return [
            'name'        => 'webbro.userforms::lang.components.deactivate.name',
            'description' => 'webbro.userforms::lang.components.deactivate.description'
        ];
    }
    
    public function onRun()
    {
        //user is logged in
        if($user = $this->user())
        {
            $this->addJs('assets/js/login.js');
            $this->addCss('assets/css/style.css');
        }
        else
        {
            //redirect to selected page
            if ($redirect = $this->makeRedirection(true))
            {
                return $redirect;
            }
        }
    }
    
    public function defineProperties()
    {
        return [
            'redirect' => [
                'title'       => /*Redirect to*/'webbro.userforms::lang.components.deactivate.redirect_to',
                'description' => /*Suggested redirect page is the login page*/'webbro.userforms::lang.components.deactivate.redirect_to_desc',
                'type'        => 'dropdown',
                'default'     => ''
            ]
        ];
    }
    
    public function getRedirectOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }
    
    /**
     * Deactivate user
     */
    public function onDeactivate()
    {
        if (!$user = $this->user()) {
            return;
        }
        
        if (!$user->checkHashValue('password', post('password'))) {
            throw new ValidationException(['password' => Lang::get('rainlab.user::lang.account.invalid_deactivation_pass')]);
        }
        
        Auth::logout();
        $user->delete();
        
        $message = Lang::get(/*Successfully deactivated your account. Sorry to see you go!*/'rainlab.user::lang.account.success_deactivation');
        
        /*
         * Redirect
         */
        if ($redirect = $this->makeRedirection()) {
            return $redirect->with('message', $message);
        }
    }
}
