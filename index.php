<?php
require_once '../config.php';

// Yetki kontrolü
if(!isset($_SESSION["loggedin"]) || ($_SESSION["role_id"] != ROLE_ADMIN && $_SESSION["role_id"] != ROLE_YONETICI)){
    header("location: ../dashboard.php");
    exit;
}

// Tüm kullanıcıları ve ban durumlarını çek
$sql = "SELECT u.id, u.username, u.email, r.role_name, b.id as ban_id, b.reason 
        FROM users u 
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN (
            SELECT *, ROW_NUMBER() OVER(PARTITION BY user_id ORDER BY id DESC) as rn
            FROM bans WHERE ban_until IS NULL OR ban_until > NOW()
        ) b ON u.id = b.user_id AND b.rn = 1";
$users = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <nav class="bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <a href="../dashboard.php" class="flex items-center text-2xl font-bold text-purple-400">Vibe Premium</a>
                <a href="../logout.php" class="flex items-center text-gray-300 hover:bg-gray-700 px-3 py-2 rounded-md">Çıkış Yap</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-8">
        <h1 class="text-3xl font-bold mb-6">Kullanıcı Yönetimi</h1>
        <div class="bg-gray-800 rounded-lg shadow overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Kullanıcı Adı</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">E-posta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    <?php while($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['role_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if($user['ban_id']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-500 text-red-100">
                                    Banlı (<?php echo htmlspecialchars($user['reason']); ?>)
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-500 text-green-100">Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if($user['ban_id']): ?>
                                <form action="unban.php" method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="text-green-400 hover:text-green-300">Ban Kaldır</button>
                                </form>
                            <?php else: ?>
                                <form action="ban.php" method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="text" name="reason" placeholder="Ban Sebebi" class="bg-gray-700 rounded p-1 text-xs" required>
                                    <select name="duration" class="bg-gray-700 rounded p-1 text-xs">
                                        <option value="1">1 Gün</option>
                                        <option value="7">7 Gün</option>
                                        <option value="30">30 Gün</option>
                                        <option value="permanent">Süresiz</option>
                                    </select>
                                    <button type="submit" class="text-red-400 hover:text-red-300 ml-2">Banla</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
