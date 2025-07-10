<?php
require_once 'config.php';
require_once 'security_check.php';

date_default_timezone_set('Europe/Istanbul');

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Geçersiz istek.'];

$prizes = [
    ['label' => '10 TL', 'type' => 'balance', 'value' => 10], ['label' => 'Pas', 'type' => 'nothing', 'value' => 0],
    ['label' => '50 TL', 'type' => 'balance', 'value' => 50], ['label' => '1 Gün<br>Premium', 'type' => 'premium', 'value' => 1],
    ['label' => '25 TL', 'type' => 'balance', 'value' => 25], ['label' => '100 TL', 'type' => 'balance', 'value' => 100],
    ['label' => 'Pas', 'type' => 'nothing', 'value' => 0], ['label' => '3 Gün<br>Premium', 'type' => 'premium', 'value' => 3],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    $last_spin_time = null;
    $sql_spin = "SELECT last_spin_time FROM wheel_spins WHERE user_id = ?";
    if($stmt_spin = $conn->prepare($sql_spin)){
        $stmt_spin->bind_param("i", $user_id);
        $stmt_spin->execute();
        $stmt_spin->bind_result($last_spin_time);
        $stmt_spin->fetch();
        $stmt_spin->close();
    }
    
    $can_spin = !$last_spin_time || (time() >= strtotime($last_spin_time) + (24 * 60 * 60));

    if ($action === 'spin') {
        if (!$can_spin) {
            $response['message'] = 'Henüz çevirme hakkınız yok.';
        } else {
            $conn->begin_transaction();
            try {
                $prize_index = rand(0, count($prizes) - 1);
                $won_prize = $prizes[$prize_index];

                if ($won_prize['type'] === 'balance') {
                    $conn->query("INSERT INTO balances (user_id, balance) VALUES ($user_id, {$won_prize['value']}) ON DUPLICATE KEY UPDATE balance = balance + {$won_prize['value']}");
                } elseif ($won_prize['type'] === 'premium') {
                    // Bu özellik için users tablosunda 'premium_until' (DATE veya DATETIME) sütunu olmalı.
                    // $conn->query("UPDATE users SET premium_until = DATE_ADD(GREATEST(NOW(), IFNULL(premium_until, NOW())), INTERVAL {$won_prize['value']} DAY) WHERE id = $user_id");
                }

                $conn->query("INSERT INTO wheel_spins (user_id, last_spin_time) VALUES ($user_id, NOW()) ON DUPLICATE KEY UPDATE last_spin_time = NOW()");

                if ($won_prize['type'] !== 'nothing') {
                    $notification_message = "Şans Çarkı'ndan " . str_replace('<br>', ' ', $won_prize['label']) . " kazandınız!";
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $stmt_notify->bind_param("is", $user_id, $notification_message);
                    $stmt_notify->execute();
                }

                $conn->commit();
                $response = ['status' => 'success', 'prize_index' => $prize_index, 'prize_label' => $won_prize['label']];
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'İşlem sırasında bir veritabanı hatası oluştu.';
            }
        }
    } 
    elseif ($action === 'skip_cooldown') {
        $skip_price = 50;
        $current_balance = 0.00;
        $sql_balance = "SELECT balance FROM balances WHERE user_id = ?";
        if($stmt_balance = $conn->prepare($sql_balance)){
            $stmt_balance->bind_param("i", $user_id);
            $stmt_balance->execute();
            $stmt_balance->bind_result($current_balance);
            if(!$stmt_balance->fetch()){ $current_balance = 0.00; }
            $stmt_balance->close();
        }

        if ($current_balance >= $skip_price) {
            $conn->begin_transaction();
            try {
                $conn->query("UPDATE balances SET balance = balance - $skip_price WHERE user_id = $user_id");
                $conn->query("INSERT INTO wheel_spins (user_id, last_spin_time) VALUES ($user_id, '1970-01-01 00:00:00') ON DUPLICATE KEY UPDATE last_spin_time = '1970-01-01 00:00:00'");
                $conn->commit();
                $response = ['status' => 'success_skip'];
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = 'İşlem sırasında bir hata oluştu.';
            }
        } else {
            $response['message'] = 'Süreyi atlamak için yeterli bakiyeniz yok!';
        }
    }
}

echo json_encode($response);
$conn->close();
exit;
