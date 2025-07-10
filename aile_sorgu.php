<?php
require_once 'config.php';
require_once 'sorgu_config.php';
require_once 'security_check.php';

$person = null;
$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tc = filter_input(INPUT_POST, 'tc', FILTER_SANITIZE_NUMBER_INT);
    if ($tc && strlen((string)$tc) == 11) {
        $sql = "SELECT p.*, f.tc as father_tc, f.first_name as father_name, f.last_name as father_lastname, m.tc as mother_tc, m.first_name as mother_name, m.last_name as mother_lastname
                FROM people p
                LEFT JOIN people f ON p.father_id = f.id
                LEFT JOIN people m ON p.mother_id = m.id
                WHERE p.tc = ?";
        if($stmt = $sorgu_conn->prepare($sql)){
            $stmt->bind_param("s", $tc);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows > 0){
                $person = $result->fetch_assoc();
            } else {
                $error_message = "Bu TC kimlik numarasına sahip bir kayıt bulunamadı.";
            }
            $stmt->close();
        }
    } else {
        $error_message = "Lütfen 11 haneli, geçerli bir TC kimlik numarası girin.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aile Sorgu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>.animate-fade-in-up { animation: fadeInUp 0.5s ease-out forwards; opacity: 0; } @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }</style>
</head>
<body class="bg-gray-900 text-white">
    <?php require_once 'layout/sidebar.php'; ?>
    <main class="ml-64 p-8">
        <h1 class="text-4xl font-bold mb-8">Aile Sorgulama</h1>
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <form action="aile_sorgu.php" method="POST" id="sorgu-form">
                <label for="tc" class="block text-sm font-medium text-gray-300 mb-2">TC Kimlik Numarası</label>
                <div class="flex flex-col sm:flex-row gap-4">
                    <input type="text" name="tc" id="tc" class="w-full bg-gray-700 text-white p-3 rounded-md outline-none focus:ring-2 focus:ring-purple-500" pattern="\d{11}" title="Lütfen 11 haneli TC kimlik numaranızı girin." placeholder="Sorgulanacak TC numarasını girin..." required>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg transition-transform hover:scale-105 whitespace-nowrap"><i class="fas fa-search mr-2"></i>Sorgula</button>
                </div>
            </form>
        </div>
        
        <?php if ($person): ?>
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in-up">
                <!-- Kişinin Kendisi -->
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg text-center border-t-4 border-purple-500">
                    <h3 class="text-xl font-bold text-purple-400 mb-2">Kendisi</h3>
                    <p class="text-lg font-semibold text-white"><?php echo $person['first_name'] . ' ' . $person['last_name']; ?></p>
                    <p class="text-sm text-gray-400 mt-1"><?php echo $person['tc']; ?></p>
                </div>
                <!-- Baba -->
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg text-center border-t-4 border-blue-500">
                    <h3 class="text-xl font-bold text-blue-400 mb-2">Babası</h3>
                    <p class="text-lg font-semibold text-white"><?php echo $person['father_name'] ? $person['father_name'] . ' ' . $person['father_lastname'] : 'Bilinmiyor'; ?></p>
                    <p class="text-sm text-gray-400 mt-1"><?php echo $person['father_tc'] ?? '-'; ?></p>
                </div>
                <!-- Anne -->
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg text-center border-t-4 border-pink-500">
                    <h3 class="text-xl font-bold text-pink-400 mb-2">Annesi</h3>
                    <p class="text-lg font-semibold text-white"><?php echo $person['mother_name'] ? $person['mother_name'] . ' ' . $person['mother_lastname'] : 'Bilinmiyor'; ?></p>
                    <p class="text-sm text-gray-400 mt-1"><?php echo $person['mother_tc'] ?? '-'; ?></p>
                </div>
            </div>
        <?php elseif (!empty($error_message)): ?>
            <div class="mt-8 bg-red-900/50 text-red-300 p-4 rounded-lg text-center animate-fade-in-up"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </main>
    <?php require_once 'layout/global_scripts.php'; ?>
    <script>
        document.getElementById('sorgu-form')?.addEventListener('submit', function() {
            showLoading('Sorgulanıyor...');
        });
    </script>
</body>
</html>
