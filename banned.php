<?php
require_once 'config.php';

// Bu sayfa için session'ı burada başlatalım, çünkü config'den sonra çağrılmıyor olabilir.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Oturumda banlı kullanıcı bilgisi yoksa, kullanıcıyı auth sayfasına yönlendir.
// Banlı kullanıcının session'ı auth.php'de açılıyor.
if (!isset($_SESSION["banned_username"])) {
    header("location: auth.php");
    exit;
}

// Değişkenleri varsayılan değerleriyle tanımla
$banned_user_id = 0;
$reason = 'Belirtilmemiş';
$ban_until = null;
$admin_name = 'Sistem';
$balance = 0.00;
$unban_price = 0;
$has_enough_balance = false;

// Session'daki kullanıcı adından ID'yi bul
$sql_user_id = "SELECT id FROM users WHERE username = ?";
if($stmt_user_id = $conn->prepare($sql_user_id)){
    $stmt_user_id->bind_param("s", $_SESSION["banned_username"]);
    $stmt_user_id->execute();
    $stmt_user_id->bind_result($banned_user_id);
    $stmt_user_id->fetch();
    $stmt_user_id->close();
}

// Eğer kullanıcı ID'si bulunduysa, diğer bilgileri çek
if($banned_user_id > 0) {
    // En son aktif ban bilgilerini ve admin adını çek
    $sql_ban_info = "SELECT b.reason, b.ban_until, u.username AS admin_name 
                     FROM bans b 
                     JOIN users u ON b.banned_by = u.id 
                     WHERE b.user_id = ? AND (b.ban_until IS NULL OR b.ban_until > NOW()) 
                     ORDER BY b.id DESC LIMIT 1";

    if ($stmt_ban = $conn->prepare($sql_ban_info)) {
        $stmt_ban->bind_param("i", $banned_user_id);
        $stmt_ban->execute();
        $stmt_ban->bind_result($reason, $ban_until, $admin_name);
        $stmt_ban->fetch();
        $stmt_ban->close();
    }

    // Kullanıcının bakiyesini çek
    $sql_balance = "SELECT balance FROM balances WHERE user_id = ?";
    if($stmt_balance = $conn->prepare($sql_balance)){
        $stmt_balance->bind_param("i", $banned_user_id);
        $stmt_balance->execute();
        $stmt_balance->bind_result($balance);
        // fetch() null dönebilir, bu yüzden kontrol ekleyelim
        if (!$stmt_balance->fetch()) {
            $balance = 0.00;
        }
        $stmt_balance->close();
    }

    // Ban süresine göre fiyat belirle
    $unban_price = ($ban_until === null) ? 1000.00 : 250.00;
    $has_enough_balance = ($balance >= $unban_price);
} else {
    // Güvenlik önlemi, eğer bir şekilde banlı kullanıcı bilgisi session'da yoksa
    header("location: auth.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erişim Engellendi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @keyframes pulseRedBackground {
            0% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.3), inset 0 0 20px rgba(239, 68, 68, 0.2); }
            50% { box-shadow: 0 0 40px rgba(239, 68, 68, 0.6), inset 0 0 40px rgba(239, 68, 68, 0.4); }
            100% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.3), inset 0 0 20px rgba(239, 68, 68, 0.2); }
        }
        .ban-container { animation: pulseRedBackground 4s ease-in-out infinite; }
    </style>
</head>
<body class="bg-black text-white">
    <!-- Arka Plan Videosu -->
    <div class="absolute top-0 left-0 w-full h-full z-[-1] overflow-hidden">
        <video autoplay muted loop class="w-full h-full object-cover filter brightness-75">
            <source src="assets/ban.mp4" type="video/mp4">
            Tarayıcınız video etiketini desteklemiyor.
        </video>
    </div>
    
    <audio autoplay loop>
        <source src="assets/ban.mp3" type="audio/mpeg">
    </audio>

    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="ban-container text-center p-8 bg-black/50 backdrop-blur-md rounded-2xl border border-red-500/50 shadow-2xl max-w-2xl mx-auto">
            <h1 class="text-5xl md:text-6xl font-extrabold text-red-500 drop-shadow-[0_0_10px_rgba(255,0,0,0.8)]">BANLANDIN</h1>
            
            <div class="mt-8 text-left bg-gray-900/50 p-6 rounded-lg space-y-3">
                <p><strong class="text-red-400 w-32 inline-block">Sebep:</strong> <?php echo htmlspecialchars($reason); ?></p>
                <p><strong class="text-red-400 w-32 inline-block">Banı Atan:</strong> <?php echo htmlspecialchars($admin_name); ?></p>
                <p><strong class="text-red-400 w-32 inline-block">Ban Bitiş Tarihi:</strong> <?php echo $ban_until ? date('d.m.Y H:i', strtotime($ban_until)) : 'Süresiz'; ?></p>
            </div>

            <!-- Ücretli Ban Kaldırma Bölümü -->
            <div class="mt-8 bg-purple-900/30 p-6 rounded-lg border border-purple-500/50">
                <h2 class="text-2xl font-bold text-purple-300">İkinci Bir Şans</h2>
                <p class="text-purple-200/80 mt-2">Belirlenen ücreti ödeyerek yasağını kaldırabilir ve aramıza geri dönebilirsin.</p>
                <div class="my-4 text-3xl font-bold"><?php echo number_format($unban_price, 2); ?> TL</div>
                <p class="text-sm">Mevcut Bakiyen: <?php echo number_format($balance, 2); ?> TL</p>
                
                <?php if ($has_enough_balance): ?>
                    <form action="unban_payment.php" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $banned_user_id; ?>">
                        <input type="hidden" name="price" value="<?php echo $unban_price; ?>">
                        <button type="submit" class="mt-4 w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition-transform hover:scale-105">
                            ÖDEME YAP VE BANI KALDIR
                        </button>
                    </form>
                <?php else: ?>
                    <div class="mt-4 w-full bg-gray-700 text-gray-400 font-bold py-3 rounded-lg cursor-not-allowed">
                        Bakiye Yetersiz
                    </div>
                <?php endif; ?>
            </div>

            <!-- İzin Verilen Sayfa Butonları -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="load_balance.php" class="block w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition-transform hover:scale-105 text-center">
                    <i class="fas fa-wallet mr-2"></i>Bakiye Yükle
                </a>
                <a href="support.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-transform hover:scale-105 text-center">
                    <i class="fas fa-life-ring mr-2"></i>Destek Talebi Oluştur
                </a>
            </div>

            <a href="logout.php" class="mt-8 inline-block text-gray-400 hover:text-white underline">Oturumu Kapat</a>
        </div>
    </div>
</body>
</html>
