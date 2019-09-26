<?php

  session_start();

  include_once 'ir/config.php';

  $func = 'ir_' . (isset($_GET['req']) ? $_GET['req'] : 'ir');
  if (isset($_GET['req']) && function_exists($func)) {
    $func();
  } else {
    irb_send('');
  }
