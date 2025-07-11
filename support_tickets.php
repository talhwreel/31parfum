<?php
require_once '../config.php';
require_once '../security_check.php';

if ($_SESSION["role_id"] < ROLE_ADMIN) {
    header("location: ../dashboard.php");
    exit;
}

$sql = "SELECT st.id, st.subject, st.status, st.created_at, u.username 
        FROM support_tickets st
        JOIN users u ON st.user_id = u.id 
        WHERE st.status != 'closed' 
        ORDER BY st.created_at ASC";
$tickets = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Talepleri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <?php require_once '../layout/sidebar.php'; ?>
    <main class="ml-64 p-8">
        <h1 class="text-4xl font-bold mb-8">Aktif Destek Talepleri</h1>
        <div class="bg-gray-800 rounded-lg shadow overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Talep ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Kullanıcı</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Konu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    <?php if ($tickets && $tickets->num_rows > 0): ?>
                        <?php while($row = $tickets->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-400">#<?php echo $row['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold"><?php echo htmlspecialchars($row['username']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-purple-300"><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $status_map = ['open' => 'Açık', 'answered' => 'Cevaplandı', 'closed' => 'Kapandı'];
                                        $status_class_map = ['open' => 'bg-blue-200 text-blue-800', 'answered' => 'bg-yellow-200 text-yellow-800', 'closed' => 'bg-gray-200 text-gray-800'];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class_map[$row['status']] ?? ''; ?>">
                                        <?php echo $status_map[$row['status']] ?? 'Bilinmiyor'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="../ticket_view.php?id=<?php echo $row['id']; ?>" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2 px-3 rounded-md transition-transform hover:scale-105">Görüntüle & Cevapla</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-8 text-gray-400">Aktif destek talebi bulunmuyor.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
