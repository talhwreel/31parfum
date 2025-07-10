<?php
require_once 'config.php';
require_once 'security_check.php';

$conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);

$notifications_sql = "SELECT message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($notifications_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bildirimlerim</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <?php require_once 'layout/sidebar.php'; ?>
    <main class="ml-64 p-8">
        <h1 class="text-4xl font-bold mb-8">Bildirimlerim</h1>
        <div class="bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="space-y-4">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($notification = $result->fetch_assoc()): ?>
                        <div class="flex items-start p-4 bg-gray-700/50 rounded-md border-l-4 border-purple-500">
                            <i class="fas fa-bell text-purple-400 mt-1 mr-4"></i>
                            <div class="flex-grow">
                                <p class="text-gray-200"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <span class="text-xs text-gray-500"><?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?></span>
                            </div>
                            <?php if ($notification['link']): ?>
                                <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="ml-4 px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-md self-center">Git</a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-400 text-center py-8">Gösterilecek yeni bildirim yok.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php require_once 'layout/global_scripts.php'; ?>
</body>
</html>
