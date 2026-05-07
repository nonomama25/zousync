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

// Gérer l'ajout d'une nouvelle promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promotion'])) {
    $nom = trim($_POST['nom'] ?? '');
    if ($nom === '') {
        $error = 'Le nom de la promotion est requis.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO promotion (nom) VALUES (?)');
            $stmt->execute([$nom]);
            $success = 'Promotion ajoutée avec succès.';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Récupérer toutes les promotions
try {
    $promotions = $pdo->query('SELECT id_promotion, nom FROM promotion ORDER BY nom')->fetchAll();
} catch (PDOException $e) {
    die('Erreur lors de la récupération des promotions: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Promotion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="menupromo.css">
</head>
<body>
    <div class="topbar">
        <div class="title">Dashboard Promotion</div>
        <div class="topbar-right">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['utilisateur']['nom']); ?></span>
            <img src="image/user-avatar.png" alt="topbar-avatar">
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
        <h1>Promotions</h1>

        <section class="section-add">
            <h2>Nouvelle promotion</h2>
            <form method="post" class="add-promo-form">
                <div class="input-group">
                    <label for="nom" class="form-label">Nom de la promotion</label>
                    <div class="input-wrapper">
                        <input type="text" id="nom" name="nom" required class="form-input" placeholder="Entrez le nom de la promotion">
                        <button type="submit" name="add_promotion" class="btn-add-promo">
                            <span class="btn-text">Ajouter</span>
                            <span class="btn-icon">+</span>
                        </button>
                    </div>
                </div>
            </form>

            <?php if (isset($error)): ?>
                <div class="message error-message">
                    <span class="message-icon">⚠️</span>
                    <span class="message-text"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="message success-message">
                    <span class="message-icon">✅</span>
                    <span class="message-text"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
        </section>

        <section class="section-list">
            <h2>Liste des promotions</h2>
            <div class="cards-grid">
                <?php if (empty($promotions)): ?>
                    <p>Aucune promotion trouvée. Ajoutez-en une ci-dessus.</p>
                <?php else: ?>
                    <?php foreach ($promotions as $p): ?>
                        <div class="card">
                            <a href="eleves_promo.php?id_promotion=<?= htmlspecialchars($p['id_promotion']) ?>">
                                <?= htmlspecialchars($p['nom']) ?>
                            </a>
                            <a href="confirm_delete.php?type=promotion&id=<?= htmlspecialchars($p['id_promotion']) ?>" class="btn-delete">×</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>