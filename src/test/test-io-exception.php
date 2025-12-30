<?php
require_once ('../code/CTI/DriverManager.php');

//セッションの開始
try {
  $session = cti_get_session('ctip://localhost:18099/',
	  array('user' => 'user',
	  'password' => 'kappa'));
} catch (Exception $e) {
  exit(1);
}

//セッションの終了
$session->close();
exit(0);
?>