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

// Récupérer tous les élèves (depuis la table utilisateur)
$stmt = $pdo->prepare('
    SELECT u.id, u.nom, u.prenom, p.nom as promo_nom 
    FROM utilisateur u 
    LEFT JOIN promotion p ON u.id_promotion = p.id_promotion 
    WHERE u.est_admin = 0 
    ORDER BY u.nom
');
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
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['utilisateur']['nom']); ?></span>
            <img src="image/user-avatar.png" alt="Avatar" class="topbar-avatar">
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
        <h1>Tous les élèves</h1>
        <section>
            <div class="cards-grid">
                <?php if (empty($eleves)): ?>
                    <p>Aucun élève trouvé.</p>
                <?php else: ?>
                    <?php foreach ($eleves as $e): ?>
                        <div class="card">
                            <div class="card-info">
                                <strong><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></strong>
                                <br>
                                <small style="color: #666;"><?= htmlspecialchars($e['promo_nom'] ?? 'Sans promotion') ?></small>
                            </div>
                            <div class="card-actions">
                                <a href="modifeleve.php?id=<?= $e['id'] ?>" class="btn-edit">Modifier</a>
                                <a href="progression.php?id_eleve=<?= $e['id'] ?>" class="btn-view">Voir progression</a>
                                <form method="post" action="deleteeleve.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="btn-delete" style="border:none; cursor:pointer; background:none; padding:0;">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <a href="addeleves.php" class="create-btn" title="Ajouter un élève">+</a>
</body>
</html>
