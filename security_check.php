<?php
// Bu dosya, config.php'den sonra her sayfanın başına eklenecek.

// Oturumun başladığından emin ol.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kimlik tespiti: Kullanıcı ya tam giriş yapmıştır ya da banlandığı için özel bir session'ı vardır.
$user_id = null;
$username = null;

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Tam giriş yapmış kullanıcı
    $user_id = $_SESSION['id'];
} elseif (isset($_SESSION['banned_username'])) {
    // Banlı kullanıcı (henüz tam giriş yapmamış)
    $username = $_SESSION['banned_username'];
    // Kullanıcı adından ID'yi bulmamız gerekiyor
    $sql_get_id = "SELECT id FROM users WHERE username = ?";
    if (isset($conn) && $stmt_get_id = $conn->prepare($sql_get_id)) {
        $stmt_get_id->bind_param("s", $username);
        $stmt_get_id->execute();
        $stmt_get_id->bind_result($user_id);
        $stmt_get_id->fetch();
        $stmt_get_id->close();
    }
}

// Eğer hiçbir şekilde kimlik bilgisi yoksa, auth'a yönlendir.
if ($user_id === null) {
    header("location: /vibe-premium/auth.php");
    exit;
}

// Ban kontrolü
$is_banned = false;
if (isset($conn)) {
    $ban_sql = "SELECT id FROM bans WHERE user_id = ? AND (ban_until IS NULL OR ban_until > NOW())";
    if($ban_stmt = $conn->prepare($ban_sql)){
        $ban_stmt->bind_param("i", $user_id);
        $ban_stmt->execute();
        $ban_stmt->store_result();
        if($ban_stmt->num_rows > 0){
            $is_banned = true;
        }
        $ban_stmt->close();
    }
}

// YENİ MANTIK SIRALAMASI
if ($is_banned) {
    // KULLANICI BANLI İSE:
    // İzin verilen sayfaların listesi
    $allowed_pages_for_banned = [
        '/vibe-premium/banned.php',
        '/vibe-premium/load_balance.php',
        '/vibe-premium/support.php',
        '/vibe-premium/ticket_view.php',
        '/vibe-premium/process_ticket_status.php',
        '/vibe-premium/unban_payment.php',
        '/vibe-premium/logout.php'
    ];

    // Mevcut sayfa izin verilenler listesinde değilse, ban sayfasına yönlendir.
    if (!in_array($_SERVER['PHP_SELF'], $allowed_pages_for_banned)) {
        header("location: /vibe-premium/banned.php");
        exit;
    }
} else {
    // KULLANICI BANLI DEĞİLSE:
    // Tam giriş yapmış olması GEREKİR.
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        // Eğer giriş yapmamışsa, bir sorun var demektir, auth'a yolla.
        header("location: /vibe-premium/auth.php");
        exit;
    }
}
?>
