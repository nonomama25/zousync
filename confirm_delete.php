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

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if (!$type || !$id) {
    header('Location: index.php');
    exit;
}

$item_name = '';
$back_url = '';

if ($type === 'promotion') {
    $stmt = $pdo->prepare('SELECT nom FROM promotion WHERE id_promotion = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item) {
        $item_name = 'la promotion "' . htmlspecialchars($item['nom']) . '"';
        $back_url = 'menupromo.php';
    }
} elseif ($type === 'eleve') {
    $stmt = $pdo->prepare('SELECT nom, prenom FROM utilisateur WHERE id = ? AND est_admin = 0');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item) {
        $item_name = 'l\'élève "' . htmlspecialchars($item['nom'] . ' ' . $item['prenom']) . '"';
        $back_url = 'alleleves.php';
    }
} elseif ($type === 'tp') {
    $stmt = $pdo->prepare('SELECT titre FROM tp WHERE id_tp = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item) {
        $item_name = 'le TP "' . htmlspecialchars($item['titre']) . '"';
        $back_url = 'tp_promo.php';
    }
} elseif ($type === 'tache') {
    $stmt = $pdo->prepare('SELECT libelle FROM tache WHERE id_tache = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item) {
        $item_name = 'la tâche "' . htmlspecialchars($item['libelle']) . '"';
        $back_url = 'tp_promo.php';
    }
}

if (!$item_name) {
    header('Location: index.php');
    exit;
}

// Gérer la confirmation de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($type === 'promotion') {
        $stmt = $pdo->prepare('DELETE FROM promotion WHERE id_promotion = ?');
        $stmt->execute([$id]);
    } elseif ($type === 'eleve') {
        $stmt = $pdo->prepare('DELETE FROM utilisateur WHERE id = ? AND est_admin = 0');
        $stmt->execute([$id]);
    } elseif ($type === 'tp') {
        $stmt = $pdo->prepare('DELETE FROM tp WHERE id_tp = ?');
        $stmt->execute([$id]);
    } elseif ($type === 'tache') {
        $stmt = $pdo->prepare('DELETE FROM tache WHERE id_tache = ?');
        $stmt->execute([$id]);
    }
    header('Location: ' . $back_url . '?success=Suppression effectuée avec succès.');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer la suppression</title>
    <link rel="stylesheet" href="menupromo.css">
</head>
<body>
    <div class="topbar">
        <div class="title">Confirmer la suppression</div>
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
        <h1>Confirmer la suppression</h1>
        <p>Êtes-vous sûr de vouloir supprimer <?php echo $item_name; ?> ? Cette action est irréversible.</p>
        <form method="post">
            <button type="submit" name="confirm_delete" class="btn-delete-confirm">Oui, supprimer</button>
            <a href="<?php echo $back_url; ?>" class="btn-cancel">Annuler</a>
        </form>
    </main>
</body>
</html>