<?php
require_once '../config.php';

// Yetki kontrolü
if(!isset($_SESSION["loggedin"]) || ($_SESSION["role_id"] != ROLE_ADMIN && $_SESSION["role_id"] != ROLE_YONETICI)){
    header("location: ../dashboard.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $user_id = $_POST['user_id'];
    // En son ban kaydını güncellemek daha iyi bir pratiktir, şimdilik basitçe siliyoruz.
    // Gerçek bir projede ban geçmişi tutulabilir.
    $sql = "DELETE FROM bans WHERE user_id = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
header("location: index.php");
exit;
?>
