<?php return [
    'plugin' => [
        'name' => 'User Forms',
        'description' => 'Extends default user configuration to allow for easy customization of user forms.'
    ],
    'components' => [
        'login' => [
            'name' => 'Login',
            'description' => 'User login form',
            'show_titles_title' => 'Show titles',
            'show_titles_desc' => 'Should the field titles be displayed on the field'
        ],
        'register' => [
            'name' => 'Register',
            'description' => 'User registration form',
            'two_names_title' => 'Collect two names',
            'two_names_desc' => 'Checking this has the form show both \'first\' and \'last\' name',
            'password_confirm_title' => 'Require password confirm',
            'password_confirm_desc' => 'Show a second password field for confirmation'
        ],
        'deactivate' => [
            'name' => 'Deactivate',
            'description' => 'Provides a form for deactivating a user account',
            'redirect_to' => 'Redirect to',
            'redirect_to_desc' => 'Suggested redirect page is the login page'
        ],
        'activate' => [
            'name' => 'Activate',
            'description' => 'Provides for activating a user account'
        ],
        'resetpassword' => [
            'name' => 'Reset Password',
            'description' => 'Form for recovering and reseting a user\'s password'
        ]
    ]
];