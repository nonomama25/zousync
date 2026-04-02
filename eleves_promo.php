<?php
session_start();

// Connexion à la base de données
$DB_HOST = '127.0.0.1';
$DB_NAME = 'tp';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erreur DB: ' . htmlspecialchars($e->getMessage()));
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'utilisateur est admin
if ($_SESSION['utilisateur']['est_admin'] != 1) {
    header('Location: login.php');
    exit;
}

// Récupérer l'id de la promotion
$id_promotion = isset($_GET['id_promotion']) ? (int)$_GET['id_promotion'] : 0;

if ($id_promotion <= 0) {
    die('Promotion invalide. <a href="menupromo.php">Retour</a>');
}

// Récupérer le nom de la promotion
$stmt = $pdo->prepare('SELECT nom FROM promotion WHERE id_promotion = ?');
$stmt->execute([$id_promotion]);
$promo = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM utilisateur WHERE id_promotion = ?');
$stmt->execute([$id_promotion]);
$eleves = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Élèves - <?= htmlspecialchars($promo) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="menupromo.css">
</head>
<body>
    <div class="topbar">
        <div class="title">Élèves — <?= htmlspecialchars($promo) ?></div>
        <div class="topbar-right">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['utilisateur']['nom']); ?></span>
            <img src="image/user-avatar.png" alt="Avatar" class="user-avatar">
        </div>
    </div>

    <div class="logo-container">
        <img src="image/Logo entreprise.png" alt="Logo" class="login-logo">
    </div>

    <nav class="sidebar" aria-label="Navigation principale">
        <a href="index.php" class="nav-btn">Accueil</a>
        <a href="menupromo.php" class="nav-btn">Promotions</a>
        <a href="alleleves.php" class="nav-btn">Eleves</a>
        <a href="tp_promo.php" class="nav-btn">Travaux Pratiques</a>
        <a href="parametre.php" class="nav-btn">Paramètres</a>
    </nav>

    <main class="content">
        <h1>Élèves de <?= htmlspecialchars($promo) ?></h1>
        <section>
            <div class="cards-grid">
                <?php if (empty($eleves)): ?>
                    <p>Aucun élève dans cette promotion.</p>
                <?php else: ?>
                    <?php foreach ($eleves as $e): ?>
                        <div class="card"><?= htmlspecialchars($e['nom']) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <a href="addeleves.php?id_promotion=<?= $id_promotion ?>" class="create-btn" title="Ajouter un élève">+</a>
</body>
</html>
