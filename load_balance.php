<?php
require_once 'config.php';
require_once 'security_check.php';

$message = '';
$message_type = '';

$has_pending_request = false;
$sql_check = "SELECT id FROM payment_requests WHERE user_id = ? AND status = 'pending'";
if($stmt_check = $conn->prepare($sql_check)){
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if($stmt_check->num_rows > 0) $has_pending_request = true;
    $stmt_check->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$has_pending_request) {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    if ($amount && $amount > 0) {
        $sql_insert = "INSERT INTO payment_requests (user_id, amount) VALUES (?, ?)";
        if($stmt_insert = $conn->prepare($sql_insert)){
            $stmt_insert->bind_param("id", $user_id, $amount);
            if($stmt_insert->execute()){
                $_SESSION['flash_message'] = ['text' => 'Ödeme onayınız beklemeye alındı.', 'type' => 'success'];
                header("location: dashboard.php");
                exit;
            }
        }
    } else {
        $message = "Lütfen geçerli bir miktar girin.";
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bakiye Yükle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <?php require_once 'layout/sidebar.php'; ?>
    <main class="ml-64 p-8">
        <h1 class="text-4xl font-bold mb-8">Bakiye Yükle</h1>
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-semibold text-purple-400 mb-4">Ödeme Bilgileri</h2>
                <div class="space-y-4 text-gray-300">
                    <p>Lütfen aşağıdaki hesap bilgilerine ödemeyi yaptıktan sonra yandaki formu doldurarak bildirimde bulunun.</p>
                    <div>
                        <h3 class="font-bold text-lg text-white">Banka Havalesi / EFT</h3>
                        <p class="mt-2"><strong>Alıcı:</strong> Vibe Premium A.Ş.</p>
                        <p><strong>IBAN:</strong> TR00 0000 0000 0000 0000 0000 00</p>
                        <p class="mt-2"><strong>Açıklama Kısmına Yazılacak:</strong> <span class="font-mono bg-gray-700 px-2 py-1 rounded"><?php echo htmlspecialchars($username ?? $_SESSION['username']); ?></span></p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-semibold text-purple-400 mb-4">Ödeme Bildirimi</h2>
                <?php if ($has_pending_request): ?>
                    <div class="bg-yellow-900/50 border border-yellow-500 text-yellow-300 p-4 rounded-lg text-center">
                        <p class="font-semibold">Zaten beklemede olan bir ödeme talebiniz var.</p>
                        <p class="text-sm mt-1">Lütfen talebiniz sonuçlanana kadar bekleyiniz.</p>
                    </div>
                <?php else: ?>
                    <?php if(!empty($message)): ?>
                        <div class="p-4 mb-4 text-sm <?php echo $message_type == 'error' ? 'bg-red-900 text-red-300' : 'bg-yellow-900 text-yellow-300'; ?> rounded-lg"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form action="load_balance.php" method="POST">
                        <div class="mb-4">
                            <label for="amount" class="block text-sm font-medium text-gray-300 mb-2">Yatırdığınız Miktar (TL)</label>
                            <input type="number" step="0.01" name="amount" id="amount" class="w-full bg-gray-700 text-white p-3 rounded-md outline-none focus:ring-2 focus:ring-purple-500" placeholder="Örn: 150.50" required>
                        </div>
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition-transform hover:scale-105"><i class="fas fa-paper-plane mr-2"></i>Ödemeyi Yaptım, Onay Gönder</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php require_once 'layout/global_scripts.php'; ?>
</body>
</html>
