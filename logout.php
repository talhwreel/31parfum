<?php
session_start();
$_SESSION = array();
session_destroy();
// Artık auth.php'ye yönlendiriyoruz
header("location: auth.php");
exit;
?>
