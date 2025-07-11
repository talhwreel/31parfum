<?php
require_once 'config.php';
 
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
} else {
    // Artık auth.php'ye yönlendiriyoruz
    header("location: auth.php");
    exit;
}
?>
