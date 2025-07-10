<?php
require_once 'config.php';
require_once 'security_check.php';

$requests = $conn->prepare("SELECT amount, status, created_at, reviewed_at FROM payment_requests WHERE user_id = ? ORDER BY created_at DESC");
$requests->bind_param("i", $user_id);
$requests->execute();
$result = $requests->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakiye Geçmişi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <?php require_once 'layout/sidebar.php'; ?>
    <main class="ml-64 p-8">
        <h1 class="text-4xl font-bold mb-8">Bakiye Yükleme Geçmişi</h1>
        <div class="bg-gray-800 rounded-lg shadow overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Miktar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Talep Tarihi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">İşlem Tarihi</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-green-400"><?php echo number_format($row['amount'], 2); ?> TL</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $status_map = ['approved' => 'Onaylandı', 'rejected' => 'Reddedildi', 'pending' => 'Beklemede'];
                                        $status_class_map = ['approved' => 'bg-green-200 text-green-800', 'rejected' => 'bg-red-200 text-red-800', 'pending' => 'bg-yellow-200 text-yellow-800'];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class_map[$row['status']] ?? ''; ?>"><?php echo $status_map[$row['status']] ?? 'Bilinmiyor'; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-400"><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-400"><?php echo $row['reviewed_at'] ? date('d.m.Y H:i', strtotime($row['reviewed_at'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-8 text-gray-400">Daha önce bakiye yükleme talebinde bulunmadınız.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php require_once 'layout/global_scripts.php'; ?>
</body>
</html>
