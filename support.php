<?php
require_once 'config.php';
require_once 'security_check.php'; 

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_ticket'])) {
    $subject = trim($_POST['subject']);
    $content = trim($_POST['message']);

    if(!empty($subject) && !empty($content)){
        $sql = "INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("iss", $user_id, $subject, $content); // $user_id'yi security_check'ten alıyoruz
            if($stmt->execute()){
                $message = "Destek talebiniz başarıyla oluşturuldu.";
                $message_type = 'success';
            } else {
                $message = "Talep oluşturulurken bir hata oluştu.";
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = "Lütfen tüm alanları doldurun.";
        $message_type = 'warning';
    }
}

// Kullanıcının taleplerini listeleme
$tickets_sql = "SELECT id, subject, status, created_at FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($tickets_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
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
    
    <?php require_once 'layout/sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-4xl font-bold mb-8">Destek Taleplerim</h1>

        <!-- Yeni Talep Oluşturma -->
        <div class="bg-gray-800 p-6 rounded-lg mb-8 shadow-lg">
            <h2 class="text-2xl font-semibold text-purple-400 mb-4">Yeni Destek Talebi Oluştur</h2>
            <?php if(!empty($message)): ?>
                <div class="p-4 mb-4 text-sm <?php 
                    if($message_type == 'success') echo 'bg-green-900/70 text-green-300 border-l-4 border-green-500';
                    elseif($message_type == 'error') echo 'bg-red-900/70 text-red-300 border-l-4 border-red-500';
                    else echo 'bg-yellow-900/70 text-yellow-300 border-l-4 border-yellow-500';
                ?> rounded-lg">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form action="support.php" method="POST">
                <input type="hidden" name="new_ticket" value="1">
                <div class="mb-4">
                    <label for="subject" class="block text-sm font-medium text-gray-300 mb-2">Konu</label>
                    <input type="text" name="subject" id="subject" class="w-full bg-gray-700 text-white p-3 rounded-md outline-none focus:ring-2 focus:ring-purple-500" placeholder="Örn: Ban İtirazı" required>
                </div>
                <div class="mb-4">
                    <label for="message" class="block text-sm font-medium text-gray-300 mb-2">Mesajınız</label>
                    <textarea name="message" id="message" rows="4" class="w-full bg-gray-700 text-white p-3 rounded-md outline-none focus:ring-2 focus:ring-purple-500" placeholder="Sorununuzu detaylı bir şekilde açıklayın..." required></textarea>
                </div>
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition-transform hover:scale-105">
                    <i class="fas fa-plus-circle mr-2"></i>Talep Oluştur
                </button>
            </form>
        </div>

        <!-- Önceki Talepler -->
        <h2 class="text-3xl font-bold mb-4 mt-12">Önceki Taleplerim</h2>
        <div class="bg-gray-800 rounded-lg shadow overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Talep ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Konu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-400">#<?php echo $row['id']; ?></td>
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
                                    <a href="ticket_view.php?id=<?php echo $row['id']; ?>" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2 px-3 rounded-md">Görüntüle</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-8 text-gray-400">Hiç destek talebiniz bulunmuyor.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
