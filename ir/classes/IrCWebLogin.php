<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

/*
 * Copyright 2014 Osclass
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
  * IrCWebLogin.php
  * modified for api needs
  * @package irOClassApi
  * @author Ruslan Ismailov r3d_time@hotmail.com
  * @copyright 2019
  * @version v1.00 24/09/2019
  */

  class IrCWebLogin extends BaseModel {
    function __construct() {
      parent::__construct();
      if( !osc_users_enabled() ) {
        irb_send(_m('Users not enabled'));
      }
      list($name, $token) = osc_csrfguard_generate_token();
      $_POST['CSRFName'] = $name;
      $_POST['CSRFToken'] = $token;
    }

    //Business Layer...
    function doModel() {
      switch( $this->action ) {
        case('login_post'):     //post execution for the login
          if(!osc_users_enabled()) {
            irb_send(_m('Users not enabled'));
          }

					// e-mail or/and password is/are empty or incorrect
					$wrongCredentials = false;
					$email = trim(Params::getParam('email'));
					$password = Params::getParam('password', false, false);
					if ( $email == '' ) {
						irb_send(_m('Please provide an email address'));
						$wrongCredentials = true;
					}
					if ( $password == '' ) {
						irb_send(_m('Empty passwords are not allowed. Please provide a password'));
						$wrongCredentials = true;
					}
          if(osc_validate_email($email)) {
						$user = User::newInstance()->findByEmail( $email );
          }
					if ( empty($user) ) {
						$user = User::newInstance()->findByUsername( $email );
          }
					if ( empty($user) ) {
  					irb_send(_m("The user doesn't exist"));
					}
					if ( ! osc_verify_password($password, (isset($user['s_password'])?$user['s_password']:'') )) {
						irb_send(_m('The password is incorrect'));
					} else {
            if (@$user['s_password']!='') {
              if (preg_match('|\$2y\$([0-9]{2})\$|', $user['s_password'], $cost)) {
                if ($cost[1] != BCRYPT_COST) {
                   User::newInstance()->update(
                   array( 's_password' => osc_hash_password($password))
                   ,array( 'pk_i_id' => $user['pk_i_id'] ) );
                }
              } else {
                User::newInstance()->update(
                  array( 's_password' => osc_hash_password($password)),
                  array( 'pk_i_id' => $user['pk_i_id'] ) );
              }
            }
          }
					// e-mail or/and IP is/are banned
					$banned = osc_is_banned($email); // int 0: not banned or unknown, 1: email is banned, 2: IP is banned, 3: both email & IP are banned
					if($banned & 1) {
						irb_send(_m('Your current email is not allowed'));
					}
					if($banned & 2) {
  					irb_send(_m('Your current IP is not allowed'));
					}
          osc_run_hook('before_login');

					$url_redirect = osc_get_http_referer();

          require_once LIB_PATH . 'osclass/UserActions.php';
					$uActions = new UserActions(false);
					$logged = $uActions->bootstrap_login($user['pk_i_id']);

					if($logged==0) {
						irb_send(_m("The user doesn't exist"));
					} else if($logged==1) {
						if((time()-strtotime($user['dt_access_date']))>1200) { // EACH 20 MINUTES
							irb_send(_m(sprintf(_m('The user has not been validated yet. Would you like to re-send your <a href="%s">activation?</a>'), osc_user_resend_activation_link($user['pk_i_id'], $user['s_email']))));
						} else {
							irb_send(_m('The user has not been validated yet'));
						}
					} else if($logged==2) {
						irb_send(_m('The user has been suspended'));
					} else if($logged==3) {
						osc_run_hook("after_login", $user, $url_redirect);
            irb_send(array(
              'name' => $user['s_name'],
              'phone' => $user['s_phone_mobile'],
              'company' => $user['b_company'],
            ), true);
					} else {
						irb_send(_m('This should never happen'));
					}

					irb_send(array(
					  'name' => $user['s_name'],
					  'phone' => $user['s_phone_mobile'],
            'company' => $user['b_company'],
          ), true);
				break;
        case('resend'):
          $id = Params::getParam('id');
          $email = Params::getParam('email');
          $user = User::newInstance()->findByPrimaryKey($id);
          if($id=='' || $email=='' || !isset($user) || $user['b_active']==1 ||
            $email!=$user['s_email']) {
              osc_add_flash_error_message(_m('Incorrect link'));
              irb_send(_m('Incorrect link'));
          }
          if((time()-strtotime($user['dt_access_date']))>1200) { // EACH 20 MINUTES
              if(osc_notify_new_user()) {
                  osc_run_hook('hook_email_admin_new_user', $user);
              }
              if(osc_user_validation_enabled()) {
                  osc_run_hook('hook_email_user_validation', $user, $user);
              }
              User::newInstance()->update(
                array('dt_access_date' => date('Y-m-d H:i:s')),
                array('pk_i_id'  => $user['pk_i_id'])
              );
              osc_add_flash_ok_message(_m('Validation email re-sent'));
              irb_send(_m('Validation email re-sent'), true);
          } else {
              irb_send(_m('We have just sent you an email to validate your account, you will have to wait a few minutes to resend it again'), true);
          }

        break;
        case('recover'):        //form to recover the password (in this case we have the form in /gui/)
          $this->doView( 'user-recover.php' );
        break;
        case('recover_post'):   //post execution to recover the password
          require_once LIB_PATH . 'osclass/UserActions.php';
          // e-mail is incorrect
          if( !osc_validate_email(Params::getParam('s_email')) ) {
            irb_send(_m('Invalid email address'), false);
          }
          $userActions = new IrUserActions(false);
          $success = $userActions->recover_password();
          switch ($success) {
            case(0): // recover ok
              irb_send(_m('We have sent you an email with the instructions to reset your password'), true);
            break;
            case(1): // e-mail does not exist
               irb_send(_m('We were not able to identify you given the information provided'));
            break;
            case(2): // recaptcha wrong
              irb_send(_m('The recaptcha code is wrong'));
            break;
          }
        break;
        case('forgot_post'):
          osc_csrf_check();
          if( (Params::getParam('new_password', false, false) == '') ||
            (Params::getParam('new_password2', false, false) == '') ) {
            irb_send(_m('Password cannot be blank'));
          }

          $user = User::newInstance()->findByIdPasswordSecret(
            Params::getParam('userId'), Params::getParam('code'));
          if($user['b_enabled'] == 1) {
              if( Params::getParam('new_password', false, false) ==
                Params::getParam('new_password2', false, false)) {
                User::newInstance()->update(
                  array('s_pass_code' => osc_genRandomPassword(50)
                      , 's_pass_date' => date('Y-m-d H:i:s', 0)
                      , 's_pass_ip' => Params::getServerParam('REMOTE_ADDR')
                      , 's_password' => osc_hash_password(Params::getParam('new_password', false, false))
                  ), array('pk_i_id' => $user['pk_i_id'])
                );
                irb_send(_m('The password has been changed'), true);
              } else {
                irb_send(_m("Error, the password don't match"));
              }
          } else {
            irb_send(_m('Sorry, the link is not valid'));
          }

        break;
      }
    }

    //hopefully generic...
    function doView($file)
    {
        osc_run_hook("before_html");
        osc_current_web_theme_path($file);
        osc_run_hook("after_html");
    }
  }

?>
