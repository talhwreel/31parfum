<?php
require_once '../config.php';

// Yetki kontrolü
if(!isset($_SESSION["loggedin"]) || ($_SESSION["role_id"] != ROLE_ADMIN && $_SESSION["role_id"] != ROLE_YONETICI)){
    header("location: ../dashboard.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $user_id = $_POST['user_id'];
    $reason = trim($_POST['reason']);
    $duration = $_POST['duration'];
    $banned_by = $_SESSION['id'];

    $ban_until = null;
    if($duration != 'permanent'){
        $ban_until = date('Y-m-d H:i:s', strtotime("+$duration days"));
    }

    $sql = "INSERT INTO bans (user_id, reason, banned_by, ban_until) VALUES (?, ?, ?, ?)";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("isis", $user_id, $reason, $banned_by, $ban_until);
        $stmt->execute();
        $stmt->close();
    }
}
header("location: index.php");
exit;
?>
