<?php namespace WebBro\UserManagement\Components;

use Auth;
use Cms\Classes\Page;
use Event;
use Exception;
use Flash;
use Lang;
use RainLab\User\Components\Account;
use RainLab\User\Models\Settings as UserSettings;
use Redirect;
use Request;
use ValidationException;
use Validator;
use Illuminate\Support\Facades\Log;

class Activate extends Account
{
    public function componentDetails()
    {
        return [
            'name'        => 'webbro.usermanagement::lang.components.activate.name',
            'description' => 'webbro.usermanagement::lang.components.activate.description'
        ];
    }
    
    public function onRun()
    {
        /*
         * Redirect to HTTPS checker
         */
        if ($redirect = $this->redirectForceSecure()) {
            return $redirect;
        }
        
        /*
         * Activation code supplied
         */
        if ($code = $this->activationCode()) {
            $this->onActivate($code);
        }
        else
        {
            return $this->makeRedirection();
        }
    }
    
    public function defineProperties()
    {
        return [
            'redirect' => [
                'title'       => /*Redirect to*/'webbro.usermanagement::lang.components.deactivate.redirect_to',
                'description' => /*Suggested redirect page is the login page*/'webbro.usermanagement::lang.components.deactivate.redirect_to_desc',
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
     * Activate the user
     * @param  string $code Activation code
     */
    public function onActivate($code = null)
    {
        try {
            $code = post('code', $code);
            
            $errorFields = ['code' => Lang::get(/*Invalid activation code supplied.*/'rainlab.user::lang.account.invalid_activation_code')];
            
            /*
             * Break up the code parts
             */
            $parts = explode('!', $code);
            if (count($parts) != 2) {
                throw new ValidationException($errorFields);
            }
            
            list($userId, $code) = $parts;
            
            if (!strlen(trim($userId)) || !strlen(trim($code))) {
                throw new ValidationException($errorFields);
            }
            
            if (!$user = Auth::findUserById($userId)) {
                throw new ValidationException($errorFields);
            }
            
            if (!$user->attemptActivation($code)) {
                throw new ValidationException($errorFields);
            }
            
            $message = Lang::get(/*Successfully activated your account.*/'rainlab.user::lang.account.success_activation');
            
            /*
             * Redirect
             */
            if ($redirect = $this->makeRedirection()) {
                return $redirect->with('message', $message);
            }
            
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }
}