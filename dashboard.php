<?php
require_once 'config.php';
require_once 'security_check.php';

$total_users = $conn->query("SELECT COUNT(id) FROM users")->fetch_row()[0] ?? 0;
$admin_users = $conn->query("SELECT COUNT(id) FROM users WHERE role_id = ".ROLE_ADMIN)->fetch_row()[0] ?? 0;
$yonetici_users = $conn->query("SELECT COUNT(id) FROM users WHERE role_id = ".ROLE_YONETICI)->fetch_row()[0] ?? 0;
$announcements = $conn->query("SELECT title, content, created_at FROM announcements ORDER BY created_at DESC");

$flash_message = null;
if(isset($_SESSION['flash_message'])){
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <?php require_once 'layout/sidebar.php'; ?>
    <div id="notification-container" class="fixed top-6 right-6 z-[102] w-full max-w-xs space-y-3"></div>
    <main class="ml-64 p-8">
        <h1 class="text-4xl font-bold mb-8">Ana Panel</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg flex items-center space-x-4"><i class="fas fa-users text-4xl text-purple-400"></i><div><h3 class="text-lg font-semibold text-gray-400">Toplam Kullanıcı</h3><p class="text-3xl font-bold mt-1"><?php echo $total_users; ?></p></div></div>
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg flex items-center space-x-4"><i class="fas fa-user-shield text-4xl text-yellow-400"></i><div><h3 class="text-lg font-semibold text-gray-400">Admin Sayısı</h3><p class="text-3xl font-bold mt-1"><?php echo $admin_users; ?></p></div></div>
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg flex items-center space-x-4"><i class="fas fa-user-tie text-4xl text-red-400"></i><div><h3 class="text-lg font-semibold text-gray-400">Yönetici Sayısı</h3><p class="text-3xl font-bold mt-1"><?php echo $yonetici_users; ?></p></div></div>
        </div>
        <h2 class="text-3xl font-bold mb-4 text-white">Duyurular</h2>
        <div class="space-y-4">
            <?php if($announcements && $announcements->num_rows > 0): ?>
                <?php while($row = $announcements->fetch_assoc()): ?>
                <div class="bg-gray-800/70 p-6 rounded-lg shadow-lg border-l-4 border-purple-500">
                    <h3 class="text-xl font-bold text-purple-300"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p class="text-gray-300 mt-2"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                    <p class="text-xs text-gray-500 mt-4 text-right"><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></p>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-8">Henüz bir duyuru yayınlanmamış.</p>
            <?php endif; ?>
        </div>
    </main>
    <?php require_once 'layout/global_scripts.php'; ?>
    <script>
        window.showNotification = (message, type = 'success') => {
            const container = document.getElementById('notification-container');
            const icons = { success: 'fa-check-circle', warning: 'fa-exclamation-triangle', error: 'fa-times-circle' };
            const colors = { success: 'bg-green-500/80 border-green-400', warning: 'bg-yellow-500/80 border-yellow-400', error: 'bg-red-500/80 border-red-400' };
            const notification = document.createElement('div');
            notification.className = `notification flex items-center p-4 rounded-lg shadow-lg text-white border-l-4 ${colors[type]} backdrop-blur-sm`;
            notification.innerHTML = `<i class="fas ${icons[type]} mr-3 text-xl"></i><span>${message}</span>`;
            container.appendChild(notification);
            setTimeout(() => {
                notification.classList.add('fade-out');
                notification.addEventListener('animationend', () => notification.remove());
            }, 5000);
        };
        <?php if($flash_message): ?>
        document.addEventListener('DOMContentLoaded', () => {
            showNotification('<?php echo addslashes($flash_message["text"]); ?>', '<?php echo $flash_message["type"]; ?>');
        });
        <?php endif; ?>
    </script>
</body>
</html>
