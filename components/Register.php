<?php namespace WebBro\UserForms\Components;

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

class Register extends Account
{
    public function componentDetails()
    {
        return [
            'name'        => 'webbro.userforms::lang.components.register.name',
            'description' => 'webbro.userforms::lang.components.register.description'
        ];
    }
    
    public function onRun()
    {
        //redirect to HTTPS checker
        if ($redirect = $this->redirectForceSecure()) {
            return $redirect;
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
            $this->addJs('assets/js/login.js'); //generic js for form effects
            $this->addCss('assets/css/style.css');
            
            $this->prepareVars();
        }
    }
    
    public function prepareVars()
    {
        parent::prepareVars();
        
        //show username field if the loginAttribute is not an email
        $this->page['showUsername'] = $this->loginAttribute() == UserSettings::LOGIN_USERNAME;
        $this->page['twoNameFields'] = $this->collectTwoNames();
        $this->page['passwordConfirm'] = $this->requirePasswordConfirm();
        $this->page['nameAttributeLabel'] = $this->nameAttributeLabel();
        
        $this->page['showTitles'] = $this->property('showTitles');
    }
    
    public function defineProperties()
    {
        $properties = parent::defineProperties();
        
        $properties['showTitles'] = [
            'title'       => /*Show titles*/'webbro.userforms::lang.components.login.show_titles_title',
            'description' => /*Should the field titles be displayed on the field*/'webbro.userforms::lang.components.login.show_titles_desc',
            'type'        => 'checkbox',
            'default'     => 0
        ];
        $properties['twoNameFields'] = [
            'title'       => /*Collect two names*/'webbro.userforms::lang.components.register.two_names_title',
            'description' => /*Checking this has the form show both 'first' and 'last' name*/'webbro.userforms::lang.components.register.two_names_desc',
            'type'        => 'checkbox',
            'default'     => 0
        ];
        $properties['passwordConfirm'] = [
            'title'       => /*Require password confirm*/'webbro.userforms::lang.components.register.password_confirm_title',
            'description' => /*Show a second password field for confirmation*/'webbro.userforms::lang.components.register.password_confirm_desc',
            'type'        => 'checkbox',
            'default'     => 0
        ];
        
        return $properties;
    }
    
    /**
     * Returns the name label as a word.
     */
    public function nameAttributeLabel()
    {
        return Lang::get($this->property('twoNameFields')
                ? 'First name'
                : 'Name'
                );
    }
    
    public function requirePasswordConfirm()
    {
        return $this->property('passwordConfirm');
    }
    
    public function collectTwoNames()
    {
        return $this->property('twoNameFields');
    }
    
    /**
     * Register the user
     */
    public function onRegister()
    {
        try {
            if (!$this->canRegister()) {
                throw new ApplicationException(Lang::get(/*Registrations are currently disabled.*/'rainlab.user::lang.account.registration_disabled'));
            }
            
            /*
             * Validate input
             */
            $data = post();
            $rules = $this->validationRules();
            
            //only copy the value if password confirm is false
            if (!array_key_exists('password_confirmation', $data) && !$this->requirePasswordConfirm()) {
                $data['password_confirmation'] = post('password');
            }
            
            $validation = $this->makeValidator($data, $rules);
            if ($validation->fails())
            {
                throw new ValidationException($validation);
            }
            
            /*
             * Register user
             */
            Event::fire('rainlab.user.beforeRegister', [&$data]);
            
            $requireActivation = UserSettings::get('require_activation', true);
            $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
            $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;
            $user = Auth::register($data, $automaticActivation);
            
            Event::fire('rainlab.user.register', [$user, $data]);
            
            $message = "Registration successful!";
            
            /*
             * Activation is by the user, send the email
             */
            if ($userActivation) {
                $this->sendActivationEmail($user);
                
                $message = Lang::get(/*An activation email has been sent to your email address.*/'rainlab.user::lang.account.activation_email_sent');
            }
            
            /*
             * Redirect to the intended page after successful sign in
             */
            if ($redirect = $this->makeRedirection(true)) {
                return $redirect->with('message', $message);
            }
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }
    
    public function validationRules()
    {
        $rules = [
            'email'    => 'required|email|between:6,255|unique:users',
            'password' => 'required|between:8,255',
            'name'     => 'required'
        ];
        
        if($this->loginAttribute() == UserSettings::LOGIN_USERNAME) {
            $rules['username'] = 'required|between:4,255|unique:users';
        }
        
        if($this->collectTwoNames())
        {
            $rules['surname'] = 'required';
        }
        
        if($this->requirePasswordConfirm())
        {
            $rules['password_confirmation'] = 'required|between:8,255|same:password';
        }
        
        return $rules;
    }
    
    public function getValidatorMessages()
    {
        return [
            'email.required' => 'Please enter your email',
            'email.unique' => 'That email is already in use',
            'password.required' => 'Please enter your password',
            'password_confirmation.required' => 'Please confirm your password',
            'password_confirmation.same' => 'Entered passwords do not match',
            'name.required' => 'Please enter your ' . strtolower($this->nameAttributeLabel()),
            'surname.required' => 'Please enter your last name',
            'username.required' => 'Please enter a username',
            'username.unique' => 'That username is already in use'
        ];
    }
    
    public function makeValidator($data, $rules)
    {
        $messages = $this->getValidatorMessages();
        return Validator::make($data, $rules, $messages);
    }
}