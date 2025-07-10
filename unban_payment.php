<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $price = (float)$_POST['price'];

    // Güvenlik: Kullanıcının gerçekten banlı olup olmadığını ve bakiyesinin yetip yetmediğini sunucu tarafında tekrar kontrol et.
    $balance = 0.00;
    $sql_balance = "SELECT balance FROM balances WHERE user_id = ?";
    if($stmt_balance = $conn->prepare($sql_balance)){
        $stmt_balance->bind_param("i", $user_id);
        $stmt_balance->execute();
        $stmt_balance->bind_result($balance);
        $stmt_balance->fetch();
        $stmt_balance->close();
    }

    if ($balance >= $price) {
        // Veritabanı işlemlerini güvenli bir şekilde yapmak için transaction kullanıyoruz.
        $conn->begin_transaction();
        try {
            // 1. Bakiye Düşürme
            $sql_update_balance = "UPDATE balances SET balance = balance - ? WHERE user_id = ?";
            $stmt_update = $conn->prepare($sql_update_balance);
            $stmt_update->bind_param("di", $price, $user_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 2. Banı Kaldırma (banın bitiş tarihini geçmişe alarak etkisizleştiriyoruz)
            $sql_unban = "UPDATE bans SET ban_until = NOW() WHERE user_id = ? ORDER BY id DESC LIMIT 1";
            $stmt_unban = $conn->prepare($sql_unban);
            $stmt_unban->bind_param("i", $user_id);
            $stmt_unban->execute();
            $stmt_unban->close();

            // Her şey yolundaysa, işlemi onayla
            $conn->commit();

            // Kullanıcıyı bilgilendir ve yönlendir
            // Ban session'larını temizle
            unset($_SESSION["ban_reason"]);
            unset($_SESSION["banned_username"]);
            unset($_SESSION["ban_until"]);
            
            // Giriş sayfasına başarı mesajıyla yönlendir
            $_SESSION['flash_message'] = ['text' => 'Yasağın başarıyla kaldırıldı! Aramıza tekrar hoş geldin.', 'type' => 'success'];
            header("location: auth.php");
            exit;

        } catch (mysqli_sql_exception $exception) {
            // Bir hata olursa, tüm işlemleri geri al
            $conn->rollback();
            // Hata yönetimi...
            die("Ödeme sırasında bir hata oluştu. Lütfen tekrar deneyin.");
        }
    } else {
        // Bakiye yetersizse...
        die("Bakiye yetersiz. Bu işlem yapılamaz.");
    }
} else {
    // POST isteği değilse ana sayfaya yönlendir.
    header("location: index.php");
    exit;
}
?>
