<?php
// Bu dosya, config.php ve security_check.php'den sonra çağrılacak.

$unread_notifications_count = 0;
if (isset($conn) && isset($_SESSION['id'])) {
    $sql_notifications = "SELECT COUNT(id) FROM notifications WHERE user_id = ? AND is_read = 0";
    if($stmt_notifications = $conn->prepare($sql_notifications)){
        $stmt_notifications->bind_param("i", $_SESSION['id']);
        $stmt_notifications->execute();
        $stmt_notifications->bind_result($unread_notifications_count);
        $stmt_notifications->fetch();
        $stmt_notifications->close();
    }
}

$user_balance = 0.00;
if (isset($conn) && isset($_SESSION['id'])) {
    $sql_balance = "SELECT balance FROM balances WHERE user_id = ?";
    if($stmt_balance = $conn->prepare($sql_balance)){
        $stmt_balance->bind_param("i", $_SESSION['id']);
        $stmt_balance->execute();
        $stmt_balance->bind_result($user_balance);
        if(!$stmt_balance->fetch()) { $user_balance = 0.00; }
        $stmt_balance->close();
    }
}

$is_banned_sidebar = isset($is_banned) ? $is_banned : false;
?>
<div class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white flex flex-col shadow-2xl">
    <div class="flex items-center justify-center h-20 border-b border-gray-700">
        <a href="/vibe-premium/dashboard.php" class="text-2xl font-bold text-purple-400">Vibe Premium</a>
    </div>

    <nav class="flex-grow p-4 space-y-2">
        <?php if (!$is_banned_sidebar): ?>
            <a href="/vibe-premium/dashboard.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-home w-6 text-center"></i><span class="ml-3">Ana Sayfa</span></a>
            <a href="/vibe-premium/wheel.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-star w-6 text-center text-yellow-400"></i><span class="ml-3">Şans Çarkı</span></a>
            
            <hr class="border-gray-700 my-2">
            <div class="px-4 pt-2 pb-1 text-xs font-semibold text-gray-500 uppercase">Sorgu Paneli</div>
            <a href="/vibe-premium/tc_sorgu.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-id-card w-6 text-center"></i><span class="ml-3">TC Sorgu</span></a>
            <a href="/vibe-premium/aile_sorgu.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-users w-6 text-center"></i><span class="ml-3">Aile Sorgu</span></a>
            <a href="/vibe-premium/sulale_sorgu.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-sitemap w-6 text-center"></i><span class="ml-3">Sülale Sorgu</span></a>
            <hr class="border-gray-700 my-2">
            
            <a href="/vibe-premium/notifications.php" class="flex items-center justify-between px-4 py-3 text-gray-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors">
                <div class="flex items-center"><i class="fas fa-bell w-6 text-center"></i><span class="ml-3">Bildirimler</span></div>
                <?php if ($unread_notifications_count > 0): ?>
                    <span class="bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"><?php echo $unread_notifications_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="/vibe-premium/balance_history.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-history w-6 text-center"></i><span class="ml-3">Bakiye Geçmişi</span></a>
        <?php endif; ?>

        <a href="/vibe-premium/load_balance.php" class="flex items-center px-4 py-3 text-green-400 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-wallet w-6 text-center"></i><span class="ml-3">Bakiye Yükle</span></a>
        <a href="/vibe-premium/support.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-life-ring w-6 text-center"></i><span class="ml-3">Destek Talebi</span></a>

        <?php if (isset($_SESSION['role_id']) && ($_SESSION['role_id'] >= ROLE_ADMIN) && !$is_banned_sidebar): ?>
            <hr class="border-gray-700 my-4">
            <a href="/vibe-premium/admin/payment_requests.php" class="flex items-center px-4 py-3 text-yellow-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-check-double w-6 text-center"></i><span class="ml-3">Ödeme Onay</span></a>
            <a href="/vibe-premium/admin/support_tickets.php" class="flex items-center px-4 py-3 text-yellow-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-headset w-6 text-center"></i><span class="ml-3">Destek Talepleri</span></a>
            <a href="/vibe-premium/admin/index.php" class="flex items-center px-4 py-3 text-yellow-300 hover:bg-purple-600 hover:text-white rounded-md transition-colors"><i class="fas fa-users-cog w-6 text-center"></i><span class="ml-3">Kullanıcı Yönetimi</span></a>
        <?php endif; ?>
    </nav>
    <div class="p-4 border-t border-gray-700">
        <div class="text-sm text-green-400">Bakiye</div>
        <div class="font-semibold text-lg"><?php echo number_format($user_balance, 2); ?> TL</div>
        <a href="/vibe-premium/logout.php" class="flex items-center justify-center w-full mt-4 bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg transition-colors"><i class="fas fa-sign-out-alt mr-2"></i><span>Çıkış Yap</span></a>
    </div>
</div>
