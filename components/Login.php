<?php namespace WebBro\UserForms\Components;

use ApplicationException;
use Auth;
use Cms\Classes\Page;
use Event;
use Exception;
use Flash;
use Lang;
use RainLab\User\Components\Account;
use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Models\User as UserModel;
use Redirect;
use Request;
use ValidationException;
use Validator;

class Login extends Account
{
    /**
     * {@inheritDoc}
     * @see \RainLab\User\Components\Account::componentDetails()
     */
    public function componentDetails()
    {
        return [
            'name'        => 'webbro.userforms::lang.components.login.name',
            'description' => 'webbro.userforms::lang.components.login.description'
        ];
    }
    
    /**
     * {@inheritDoc}
     * @see \RainLab\User\Components\Account::onRun()
     */
    public function onRun()
    {
        //redirect to HTTPS checker
        if($redirect = $this->redirectForceSecure()) 
        {
            return $redirect;
        }
        
        //activation code is present
        if($code = $this->activationCode()) {
            $this->onActivate($code); //run activation process
        }
        
        //user is logged in
        if($user = $this->user())
        {
            //redirect to selected page
            if ($redirect = $this->makeRedirection(true)) 
            {
                return $redirect;
            }
        }
        else 
        {
            //load required scripts
            $this->addJs('assets/js/forms.js');
            $this->addCss('assets/css/style.css');
            
            $this->prepareVars();
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \RainLab\User\Components\Account::defineProperties()
     */
    public function defineProperties()
    {
        $properties = parent::defineProperties();
        
        $properties['showTitles'] = [
            'title'       => 'webbro.userforms::lang.components.login.show_titles_title',
            'description' => 'webbro.userforms::lang.components.login.show_titles_desc',
            'type'        => 'checkbox',
            'default'     => 0
        ];
        
        return $properties;
    }
    
    /**
     * {@inheritDoc}
     * @see \RainLab\User\Components\Account::prepareVars()
     */
    public function prepareVars()
    {
        parent::prepareVars();
        
        $this->page['showTitles'] = $this->showTitles();
    }
    
    /*
     * Properties
     */
    
    /**
     * Should the field titles be shown
     * @return boolean
     */
    protected function showTitles()
    {
        return $this->property('showTitles');
    }
    
    /*
     * AJAX
     */
    
    /**
     * {@inheritDoc}
     * @see \RainLab\User\Components\Account::onSignin()
     */
    public function onSignin()
    {
        try 
        {
            /*
             * Validate input
             */
            $data = post();
            
            //get defined validation rules for form
            $rules = $this->validationRules();
            
            if (!array_key_exists('login', $data)) 
            {
                $data['login'] = post('username', post('email'));
            }
            
            //create validator
            $validation = $this->makeValidator($data, $rules);
            if ($validation->fails()) 
            {
                throw new ValidationException($validation);
            }
            
            /*
             * Authenticate user
             */
            $credentials = [
                'login'    => array_get($data, 'login'),
                'password' => array_get($data, 'password')
            ];
            
            Event::fire('rainlab.user.beforeAuthenticate', [$this, $credentials]);
            
            $user = Auth::authenticate($credentials, true);
            if ($user->isBanned()) 
            {
                Auth::logout();
                throw new AuthException('rainlab.user::lang.account.banned');
            }
            
            /*
             * Redirect
             */
            if ($redirect = $this->makeRedirection(true)) 
            {
                return $redirect;
            }
        } 
        catch (Exception $ex) 
        {
            /*
             * Generic error messages are automatically generated by the system
             * when 'debug' mode is disabled.
             * 
             * 'Debug' mode causes confusing error messages to appear on the 
             * screen..... This IS normal.
             */
            if (Request::ajax()) 
            {
                throw $ex;
            }
            else 
            {
                Flash::error($ex->getMessage());
            }
        }
    }
    
    /**
     * Activate the user
     * @param  string $code Activation code
     */
    public function onActivate($code = null)
    {
        try {
            $code = post('code', $code);
            
            $message = ['code' => Lang::get(/*Invalid activation code supplied.*/'rainlab.user::lang.account.invalid_activation_code') . '<a class="d-block" href="javascript:;" data-request="onActivationForm">Send the verification email again</a>'];
            
            //break up the code parts
            $parts = explode('!', $code);
            if (count($parts) != 2) {
                throw new ValidationException($message);
            }
            
            list($userId, $code) = $parts;
            
            if (!strlen(trim($userId)) || !strlen(trim($code))) {
                throw new ValidationException($message);
            }
            
            if (!$user = Auth::findUserById($userId)) {
                throw new ValidationException($message);
            }
            
            if (!$user->attemptActivation($code)) {
                throw new ValidationException($message);
            }
            
            $message = Lang::get(/*Successfully activated your account.*/'rainlab.user::lang.account.success_activation');
            
            /*
             * Redirect
             */
            return Redirect::refresh()->with('message', $message);
            
        }
        catch (Exception $ex) {
            //refresh the page and show error
            return Redirect::refresh()->with('error', $ex->getMessage());
        }
    }
    
    public function onActivationForm()
    {
        return [
            '#partialLoginForm' => $this->renderPartial('login::activation_form')
        ];
    }
    
    /**
     * Trigger a subsequent activation email
     */
    public function onSendActivationEmail()
    {
        try {
            $rules = [
                'email' => 'required|email|between:6,255'
            ];
            
            $validation = Validator::make(post(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            
            $user = UserModel::findByEmail(post('email'));
            if (!$user || $user->is_guest) {
                throw new ApplicationException(Lang::get(/*A user was not found with the given credentials.*/'rainlab.user::lang.account.invalid_user'));
            }
            
            if ($user->is_activated) {
                throw new ApplicationException(Lang::get(/*Your account is already activated!*/'rainlab.user::lang.account.already_active'));
            }
            
            $this->sendActivationEmail($user);
            
            $message = Lang::get(/*An activation email has been sent to your email address.*/'rainlab.user::lang.account.activation_email_sent');
            
            return Redirect::refresh()->with('message', $message);
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }
    
    /*
     * Helpers
     */
    
    public function makeValidator($data, $rules)
    {
        $messages = $this->getValidatorMessages();
        return Validator::make($data, $rules, $messages);
    }
    
    public function getValidatorMessages()
    {
        return [
            'login.required' => 'Please enter your ' . $this->loginAttribute(),
            'password.required' => 'Please enter your password'
        ];
    }
    
    public function validationRules()
    {
        return [
            'login' => $this->loginAttribute() == UserSettings::LOGIN_USERNAME
                            ? 'required|between:2,255'
                            : 'required|email|between:6,255',
            'password' => 'required|between:4,255'
        ];
    }
}