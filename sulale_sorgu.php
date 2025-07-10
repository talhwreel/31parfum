<?php
require_once 'config.php';
require_once 'sorgu_config.php';
require_once 'security_check.php';

$family_tree = null;
$error_message = '';

function getAncestors($conn, $person_id, &$tree, $level = 0) {
    if ($person_id == null || $level > 5) return;
    $sql = "SELECT * FROM people WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $person_id);
    $stmt->execute();
    $person = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($person) {
        $tree = $person;
        $tree['mother'] = [];
        $tree['father'] = [];
        getAncestors($conn, $person['mother_id'], $tree['mother'], $level + 1);
        getAncestors($conn, $person['father_id'], $tree['father'], $level + 1);
    }
}

function printTree($node, $relation) {
    if (empty($node)) return;
    $color_class = 'text-purple-300';
    if(strpos($relation, 'Baba') !== false) $color_class = 'text-blue-300';
    if(strpos($relation, 'Anne') !== false) $color_class = 'text-pink-300';

    echo '<div class="ml-6 mt-2 border-l-2 border-gray-700 pl-4">';
    echo '<p><strong class="font-semibold '.$color_class.'">'.$relation.':</strong> ' . htmlspecialchars($node['first_name']) . ' ' . htmlspecialchars($node['last_name']) . ' <span class="text-xs text-gray-500">('.$node['tc'].')</span></p>';
    printTree($node['father'], 'Babası');
    printTree($node['mother'], 'Annesi');
    echo '</div>';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tc = filter_input(INPUT_POST, 'tc', FILTER_SANITIZE_NUMBER_INT);
    if ($tc && strlen((string)$tc) == 11) {
        $sql = "SELECT id FROM people WHERE tc = ?";
        if($stmt = $sorgu_conn->prepare($sql)){
            $stmt->bind_param("s", $tc);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows > 0){
                $person_id = $result->fetch_assoc()['id'];
                getAncestors($sorgu_conn, $person_id, $family_tree);
            } else {
                $error_message = "Kayıt bulunamadı.";
            }
            $stmt->close();
        }
    } else {
        $error_message = "Geçerli bir TC girin.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sülale Sorgu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>.animate-fade-in-up { animation: fadeInUp 0.5s ease-out forwards; opacity: 0; } @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }</style>
</head>
<body class="bg-gray-900 text-white">
    <?php require_once 'layout/sidebar.php'; ?>
    <main class="ml-64 p-8">
        <h1 class="text-4xl font-bold mb-8">Sülale Sorgulama (Soyağacı)</h1>
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <form action="sulale_sorgu.php" method="POST" id="sorgu-form">
                <label for="tc" class="block text-sm font-medium text-gray-300 mb-2">TC Kimlik Numarası</label>
                <div class="flex flex-col sm:flex-row gap-4">
                    <input type="text" name="tc" id="tc" class="w-full bg-gray-700 text-white p-3 rounded-md outline-none focus:ring-2 focus:ring-purple-500" pattern="\d{11}" title="Lütfen 11 haneli TC kimlik numaranızı girin." placeholder="Sorgulanacak TC numarasını girin..." required>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg transition-transform hover:scale-105 whitespace-nowrap"><i class="fas fa-search mr-2"></i>Sorgula</button>
                </div>
            </form>
        </div>

        <?php if ($family_tree): ?>
            <div class="mt-8 bg-gray-800 p-6 rounded-lg shadow-lg animate-fade-in-up">
                <h2 class="text-2xl font-semibold text-purple-400 mb-4 border-b border-gray-700 pb-2">Soyağacı</h2>
                <?php printTree($family_tree, 'Kendisi'); ?>
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
