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
    
    //
    // AJAX
    //
    
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
        
        Mail::send('rainlab.user::mail.restore', $data, function($message) use ($user) {
            $message->to($user->email, $user->full_name);
        });
    }
}