<?php
require_once 'config.php';
require_once 'security_check.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("location: support.php");
    exit;
}
$ticket_id = $_GET['id'];

// Talep bilgilerini ve sahibini çek
$sql = "SELECT st.*, u.username FROM support_tickets st JOIN users u ON st.user_id = u.id WHERE st.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

// Güvenlik: Kullanıcı talebin sahibi veya admin değilse, erişimi engelle
if (!$ticket || ($ticket['user_id'] != $_SESSION['id'] && $_SESSION['role_id'] < ROLE_ADMIN)) {
    header("location: support.php");
    exit;
}

// Cevapları çek
$sql_replies = "SELECT tr.*, u.username, u.role_id FROM ticket_replies tr JOIN users u ON tr.user_id = u.id WHERE tr.ticket_id = ? ORDER BY tr.created_at ASC";
$stmt_replies = $conn->prepare($sql_replies);
$stmt_replies->bind_param("i", $ticket_id);
$stmt_replies->execute();
$replies = $stmt_replies->get_result();

// ... (Yeni cevap gönderme ve durum değiştirme kodları burada kalacak) ...
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talep #<?php echo $ticket_id; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <?php require_once 'layout/sidebar.php'; ?>
    <main class="ml-64 p-8">
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-bold mb-2">Konu: <?php echo htmlspecialchars($ticket['subject']); ?></h1>
                <p class="text-gray-400">Talep Sahibi: <span class="font-semibold text-purple-300"><?php echo htmlspecialchars($ticket['username']); ?></span></p>
            </div>
            <div>
                <form action="process_ticket_status.php" method="POST">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                    <?php if ($ticket['status'] != 'closed'): ?>
                        <button type="submit" name="action" value="close" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-transform hover:scale-105">
                            <i class="fas fa-lock mr-2"></i>Talebi Kapat
                        </button>
                    <?php else: ?>
                        <button type="submit" name="action" value="reopen" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-transform hover:scale-105">
                            <i class="fas fa-lock-open mr-2"></i>Talebi Yeniden Aç
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="space-y-6 bg-gray-800/50 p-6 rounded-lg">
            <!-- İlk Talep Mesajı -->
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-600 flex items-center justify-center font-bold text-lg"><?php echo strtoupper(substr($ticket['username'], 0, 1)); ?></div>
                <div class="bg-gray-700 rounded-lg p-4 w-full">
                    <p class="text-gray-200"><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                    <div class="text-xs text-gray-500 text-right mt-2"><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></div>
                </div>
            </div>

            <!-- Cevaplar -->
            <?php while($reply = $replies->fetch_assoc()): ?>
                <?php $is_admin_reply = $reply['role_id'] >= ROLE_ADMIN; ?>
                <div class="flex items-start gap-4 <?php echo $is_admin_reply ? 'flex-row-reverse' : ''; ?>">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full <?php echo $is_admin_reply ? 'bg-yellow-500' : 'bg-gray-600'; ?> flex items-center justify-center font-bold text-lg"><?php echo strtoupper(substr($reply['username'], 0, 1)); ?></div>
                    <div class="bg-<?php echo $is_admin_reply ? 'purple-800' : 'gray-700'; ?> rounded-lg p-4 max-w-3xl">
                        <div class="font-bold mb-1"><?php echo htmlspecialchars($reply['username']); ?> <?php echo $is_admin_reply ? '<span class="text-xs text-yellow-300">(Yetkili)</span>' : ''; ?></div>
                        <p class="text-gray-200"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                        <div class="text-xs text-gray-400 text-right mt-2"><?php echo date('d.m.Y H:i', strtotime($reply['created_at'])); ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Cevap Yazma Formu -->
        <?php if ($ticket['status'] != 'closed'): ?>
            <hr class="border-gray-700 my-8">
            <form action="ticket_view.php?id=<?php echo $ticket_id; ?>" method="POST">
                <h2 class="text-2xl font-bold mb-4">Cevap Yaz</h2>
                <textarea name="reply_message" rows="5" class="w-full bg-gray-700 text-white p-3 rounded-md outline-none focus:ring-2 focus:ring-purple-500" placeholder="Mesajınızı buraya yazın..."></textarea>
                <button type="submit" class="mt-4 bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg transition-transform hover:scale-105">Gönder</button>
            </form>
        <?php else: ?>
            <div class="mt-8 p-4 bg-gray-800 rounded-lg text-center text-gray-400">
                Bu talep kapatılmıştır. Yeni bir cevap gönderemezsiniz.
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
