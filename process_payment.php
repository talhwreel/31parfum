<?php
require_once '../config.php';
require_once '../security_check.php';

if ($_SESSION["role_id"] < ROLE_ADMIN) {
    header("location: ../dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $user_id = $_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $action = $_POST['action'];
    $admin_id = $_SESSION['id'];

    $new_status = ($action == 'approve') ? 'approved' : 'rejected';

    $conn->begin_transaction();
    try {
        // 1. Talep durumunu güncelle
        $sql_update_req = "UPDATE payment_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
        $stmt_req = $conn->prepare($sql_update_req);
        $stmt_req->bind_param("sii", $new_status, $admin_id, $request_id);
        $stmt_req->execute();

        $notification_message = '';
        if ($action == 'approve') {
            // 2. Kullanıcının bakiye kaydı var mı kontrol et
            $check_balance_sql = "SELECT id FROM balances WHERE user_id = ?";
            $stmt_check = $conn->prepare($check_balance_sql);
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                // Varsa, bakiyeyi güncelle
                $sql_update_balance = "UPDATE balances SET balance = balance + ? WHERE user_id = ?";
                $stmt_balance = $conn->prepare($sql_update_balance);
                $stmt_balance->bind_param("di", $amount, $user_id);
                $stmt_balance->execute();
            } else {
                // Yoksa, yeni bakiye kaydı oluştur
                $sql_insert_balance = "INSERT INTO balances (user_id, balance) VALUES (?, ?)";
                $stmt_balance = $conn->prepare($sql_insert_balance);
                $stmt_balance->bind_param("id", $user_id, $amount);
                $stmt_balance->execute();
            }
            $notification_message = number_format($amount, 2) . " TL tutarındaki bakiye yükleme talebiniz onaylandı ve hesabınıza eklendi.";
        } else {
            $notification_message = number_format($amount, 2) . " TL tutarındaki bakiye yükleme talebiniz reddedildi.";
        }
        
        // 3. Kullanıcıya bildirim gönder
        $sql_notify = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
        $stmt_notify = $conn->prepare($sql_notify);
        $link = "/vibe-premium/balance_history.php";
        $stmt_notify->bind_param("iss", $user_id, $notification_message, $link);
        $stmt_notify->execute();
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        // Hata yönetimi
    }
}
header("location: payment_requests.php");
exit;
?>
