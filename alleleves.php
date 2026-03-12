<?php
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

// Récupérer tous les élèves
$stmt = $pdo->prepare('SELECT ID, Nom FROM eleves ORDER BY Nom');
$stmt->execute();
$eleves = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les élèves</title>
    <link rel="stylesheet" href="eleve.css">
    <link rel="stylesheet" href="eleve.css">
</head>
<body>
    <div class="topbar">
        <div class="title">Tous les élèves</div>
        <div class="topbar-right">
            <span class="user-name">John Doe</span>
            <img src="image/user-avatar.png" alt="Avatar" class="user-avatar">
        </div>
    </div>

    <div class="logo-container">
        <img src="image/Logo entreprise.png" alt="Logo" class="login-logo">
    </div>

    <nav class="sidebar" aria-label="Navigation principale">
        <button class="nav-btn" onclick="window.location.href='index.php'">Accueil</button>
        <button class="nav-btn" onclick="window.location.href='menupromo.php'">Promotions</button>
        <button class="nav-btn" onclick="window.location.href='alleleves.php'">Eleves</button>
        <button class="nav-btn" onclick="window.location.href='travauxpratique.php'">Travaux Pratiques</button>
        <button class="nav-btn" onclick="window.location.href='parametre.php'">Paramètres</button>
    </nav>

    <main class="content">
        <h1>Tous les élèves</h1>
        <section>
            <div class="cards-grid">
                <?php if (empty($eleves)): ?>
                    <p>Aucun élève trouvé.</p>
                <?php else: ?>
                    <?php foreach ($eleves as $e): ?>
                        <div class="card"><?= htmlspecialchars($e['Nom']) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <button class="create-btn" title="Ajouter un élève" onclick="window.location.href='addeleves.php'">+</button>
</body>
</html>
