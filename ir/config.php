<?php
  /**
   * config.php
   *
   * @package irOClassApi
   * @author Ruslan Ismailov r3d_time@hotmail.com
   * @copyright 2019
   * @version v1.00 24/09/2019
   */

  /* initial settings */
  define('VERSION', '1.0');
  define('DEBUG', true);
  define('LANGUAGE', 'RU');
  define('ABS_PATH', dirname(
    str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']) . '/')
  )  . '/' );

  /* if debug mode enabled */
  if (DEBUG) {
    ini_set('display_startup_errors',1);
    ini_set('display_errors',1);
    error_reporting(-1);
  }

  /* include required scripts */
  require_once ABS_PATH . 'oc-load.php';

  require_once ABS_PATH . 'api/ir/lang/' . LANGUAGE . '.php';
  require_once ABS_PATH . 'api/ir/functions/utils.php';
  require_once ABS_PATH . 'api/ir/functions/user.php';
  require_once ABS_PATH . 'api/ir/classes/IrCWebLogin.php';
  require_once ABS_PATH . 'api/ir/classes/IrUserActions.php';