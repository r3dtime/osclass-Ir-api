<?php
  /**
   * utils.php
   *
   * @package irOClassApi
   * @author Ruslan Ismailov r3d_time@hotmail.com
   * @copyright 2019
   * @version v1.00 24/09/2019
   */


  /**
   * irb_send function.
   *
   * @access public
   * @param mixed $res
   * @param bool $status (default: false)
   * @return void
   */
  function irb_send( $res, $status = false ) {

    header( 'Content-type: application/json; charset=utf-8' );
    $obj = array( 'status' => $status, 'version' => VERSION, 'result' => $res );
    echo json_encode($obj);
    die();

  }


  /**
   * irb_checkLoginInfo function.
   *
   * @access public
   * @param mixed $email
   * @param mixed $password
   * @return mixed
   */
  function irb_checkLoginInfo($email, $password) {

    if(osc_validate_email($email)) {
			$user = User::newInstance()->findByEmail( $email );
    }
		if ( empty($user) ) {
			$user = User::newInstance()->findByUsername( $email );
    }
    return empty($user) ? false : $user;

  }

  function irb_getSettingsForMainCatTab() {
    $file = ABS_PATH . 'api/admin/cats';
    $ext = array();
    if (file_exists($file)) {
      $file = file_get_contents($file);
      $ext = $file !== '' ? json_decode($file, true) : array();
    }
    return $ext;
  }