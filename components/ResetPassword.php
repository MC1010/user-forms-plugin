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
use phpDocumentor\Reflection\Types\String_;

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
            'default'     => 0
        ];
        
        $properties['showMessage'] = [
            'title'       => /*Show message*/'webbro.userforms::lang.components.resetpassword.show_message_title',
            'description' => /*Should the message body be displayed*/'webbro.userforms::lang.components.resetpassword.show_message_desc',
            'type'        => 'checkbox',
            'default'     => 0
        ];
        
        return $properties;
    }
    
    public function onRun()
    {
        $this->addJs('assets/js/login.js');
        $this->addCss('assets/css/style.css');
        
        $this->prepareVars();
    }
    
    public function prepareVars()
    {
        $this->page['showTitles'] = $this->property('showTitles');
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
}