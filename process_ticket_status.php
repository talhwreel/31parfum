<?php
require_once 'config.php';
require_once 'security_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ticket_id'], $_POST['action'])) {
    
    $ticket_id = $_POST['ticket_id'];
    $action = $_POST['action'];

    // Güvenlik: İşlemi yapan kullanıcının bu talebe erişim yetkisi var mı kontrol et
    $sql_check = "SELECT user_id FROM support_tickets WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $ticket_id);
    $stmt_check->execute();
    $ticket = $stmt_check->get_result()->fetch_assoc();

    if (!$ticket || ($ticket['user_id'] != $_SESSION['id'] && $_SESSION['role_id'] < ROLE_ADMIN)) {
        // Yetkisiz erişim
        header("location: support.php");
        exit;
    }

    // Durumu güncelle
    $new_status = '';
    if ($action == 'close') {
        $new_status = 'closed';
    } elseif ($action == 'reopen') {
        $new_status = 'open';
    }

    if (!empty($new_status)) {
        $sql_update = "UPDATE support_tickets SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $new_status, $ticket_id);
        $stmt_update->execute();
    }

    // Kullanıcıyı talep görüntüleme sayfasına geri yönlendir
    header("location: ticket_view.php?id=" . $ticket_id);
    exit;

} else {
    // Geçersiz istek
    header("location: support.php");
    exit;
}
?>
