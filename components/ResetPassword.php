<?php namespace WebBro\UserForms\Components;


use ApplicationException;
use Cms\Classes\Page;
use Lang;
use Mail;
use RainLab\User\Components\Account;
use RainLab\User\Components\ResetPassword as ParentReset;
use RainLab\User\Models\User as UserModel;
use ValidationException;
use Validator;
use Illuminate\Support\Facades\Redirect;

class ResetPassword extends ParentReset
{
    public function componentDetails()
    {
        return [
            'name'        => 'webbro.userforms::lang.components.resetpassword.name',
            'description' => 'webbro.userforms::lang.components.resetpassword.description'
        ];
    }
    
    public function defineProperties()
    {
        $properties = parent::defineProperties();
        
        
        $properties['redirect'] = [
            'title'       => /*Redirect to*/'webbro.userforms::lang.components.deactivate.redirect_to',
            'description' => /*Suggested redirect page is the login page*/'webbro.userforms::lang.components.deactivate.redirect_to_desc',
            'type'        => 'dropdown',
            'default'     => ''
        ];
        
        $properties['showTitles'] = [
            'title'       => /*Show titles*/'webbro.userforms::lang.components.login.show_titles_title',
            'description' => /*Should the field titles be displayed on the field*/'webbro.userforms::lang.components.login.show_titles_desc',
            'type'        => 'checkbox',
            'default'     => 0
        ];
        
        $properties['showHeader'] = [
            'title'       => /*Show header*/'webbro.userforms::lang.components.resetpassword.show_header_title',
            'description' => /*Should the message header be displayed*/'webbro.userforms::lang.components.resetpassword.show_header_desc',
            'type'        => 'checkbox',
            'default'     => 1
        ];
        
        $properties['showMessage'] = [
            'title'       => /*Show message*/'webbro.userforms::lang.components.resetpassword.show_message_title',
            'description' => /*Should the message body be displayed*/'webbro.userforms::lang.components.resetpassword.show_message_desc',
            'type'        => 'checkbox',
            'default'     => 1
        ];
        
        return $properties;
    }
    
    public function getRedirectOptions()
    {
        return [''=>'- refresh page -', '0' => '- no redirect -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }
    
    public function onRun()
    {
        $this->addJs('assets/js/forms.js');
        $this->addCss('assets/css/style.css');
        
        $this->prepareVars();
    }
    
    public function prepareVars()
    {
        $this->page['showTitles'] = $this->property('showTitles');
        $this->page['showHeader'] = $this->property('showHeader');
        $this->page['showMessage'] = $this->property('showMessage');
        $this->page['header'] = $this->messageHeader();
        $this->page['body'] = $this->messageBody();
    }
    
    /*
     * Properties
     */
    
    /**
     * Should the component message header be shown?
     * @return boolean
     */
    public function showHeader()
    {
        return $this->property('showHeader');
    }
    
    /**
     * Should the component message body be shown?
     * @return boolean
     */
    public function showMessage()
    {
        return $this->property('showMessage');
    }
    
    /**
     * Defines the message header
     * @return string
     */
    public function messageHeader()
    {
        return Lang::get('webbro.userforms::lang.components.resetpassword.message_header');
    }
    
    /**
     * Defines the message body
     * @return string
     */
    public function messageBody()
    {
        return Lang::get('webbro.userforms::lang.components.resetpassword.message_body');
    }
    
    /**
     * Defines template name for restore email
     * @return string
     */
    public function restoreMailTemplate()
    {
        return 'rainlab.user::mail.restore';
    }
    
    /*
     * AJAX
     */
    
    /**
     * Trigger the password reset email
     */
    public function onRestorePassword()
    {
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
        
        if (!$user->is_activated)
        {
            throw new ApplicationException('User has not been activated');
        }
        
        $code = implode('!', [$user->id, $user->getResetPasswordCode()]);
        
        $link = $this->makeResetUrl($code);
        
        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];
        
        Mail::send($this->restoreMailTemplate(), $data, function($message) use ($user) {
            $message->to($user->email, $user->full_name);
        });
    }
    
    /**
     * Perform the password reset
     */
    public function onResetPassword()
    {
        try 
        {
            $rules = [
                'code'     => 'required',
                'password' => 'required|between:4,255'
            ];
            
            $validation = Validator::make(post(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            
            $message = ['code' => Lang::get(/*Invalid activation code supplied.*/'rainlab.user::lang.account.invalid_activation_code')];
            
            /*
             * Break up the code parts
             */
            $parts = explode('!', post('code'));
            if (count($parts) != 2) {
                throw new ValidationException($message);
            }
            
            list($userId, $code) = $parts;
            
            if (!strlen(trim($userId)) || !strlen(trim($code)) || !$code) {
                throw new ValidationException($message);
            }
            
            if (!$user = Auth::findUserById($userId)) {
                throw new ValidationException($message);
            }
            
            if (!$user->attemptResetPassword($code, post('password'))) {
                throw new ValidationException($message);
            }
            
            $message = "Password reset complete, you may now sign in.";
            
            /*
             * Redirect
             */
            if ($redirect = $this->makeRedirection(true))
            {
                return $redirect->with('message', $message);
            }
        }
        catch (Exception $ex) 
        {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }
    
    /*
     * Helpers
     */
    
    /**
     * Redirect to the intended page after successful update, sign in or registration.
     * The URL can come from the "redirect" property or the "redirect" postback value.
     * @return mixed
     */
    protected function makeRedirection($intended = false)
    {
        $method = $intended ? 'intended' : 'to';
        
        $property = $this->property('redirect');
        
        if (!strlen($property)) {
            return;
        }
        
        $redirectUrl = $this->pageUrl($property) ?: $property;
        
        if ($redirectUrl = post('redirect', $redirectUrl)) {
            return Redirect::$method($redirectUrl);
        }
    }
}