<?php
require_once __DIR__ . '/../vendor/autoload.php';

//セッションの開始
try {
  $session = cti_get_session('ctip://localhost:8099/',
	  array('user' => 'oppe',
	  'password' => 'kepe'));
} catch (Exception $e) {
  echo $e->getMessage()."\n";
  exit(1);
}

//セッションの終了
$session->close();
exit(0);
?>