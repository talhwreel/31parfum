<?php
require_once '../config.php';
require_once '../security_check.php'; // Yetki ve ban kontrolü

// Sadece Admin ve Yönetici erişebilir
if ($_SESSION["role_id"] < ROLE_ADMIN) {
    header("location: ../dashboard.php");
    exit;
}

// Bekleyen ödeme taleplerini kullanıcı adıyla birlikte çek
$sql = "SELECT pr.id, pr.user_id, pr.amount, pr.created_at, u.username 
        FROM payment_requests pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.status = 'pending' 
        ORDER BY pr.created_at ASC";
$requests = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Onay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <?php require_once '../layout/sidebar.php'; ?>
    <main class="ml-64 p-8">
        <h1 class="text-4xl font-bold mb-8">Bekleyen Ödeme Talepleri</h1>
        <div class="bg-gray-800 rounded-lg shadow overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Kullanıcı</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Miktar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Talep Tarihi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    <?php if ($requests && $requests->num_rows > 0): ?>
                        <?php while($row = $requests->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold"><?php echo htmlspecialchars($row['username']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-green-400"><?php echo number_format($row['amount'], 2); ?> TL</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-400"><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form action="process_payment.php" method="POST" class="inline">
                                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                        <input type="hidden" name="amount" value="<?php echo $row['amount']; ?>">
                                        <button type="submit" name="action" value="approve" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-3 rounded-md transition-transform hover:scale-105">Onayla</button>
                                        <button type="submit" name="action" value="reject" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2 px-3 rounded-md ml-2 transition-transform hover:scale-105">Reddet</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-8 text-gray-400">Bekleyen ödeme talebi bulunmuyor.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
