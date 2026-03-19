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

// Vérifier si l'utilisateur est admin (rediriger si admin)
if ($_SESSION['utilisateur']['est_admin'] == 1) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['utilisateur']['id'];

// Récupérer les TP en cours pour l'élève (basé sur sa promotion)
$stmt = $pdo->prepare('
    SELECT tp.id_tp, tp.titre, tp.description
    FROM tp
    WHERE tp.id_promotion = (SELECT u.id_promotion FROM utilisateur u WHERE u.id = ?)
');
$stmt->execute([$user_id]);
$tps = $stmt->fetchAll();

// Pour chaque TP, récupérer les tâches associées
$taches_par_tp = [];
foreach ($tps as $tp) {
    $stmt = $pdo->prepare('SELECT libelle, description, priorite, ordre FROM tache WHERE id_tp = ? ORDER BY ordre');
    $stmt->execute([$tp['id_tp']]);
    $taches_par_tp[$tp['id_tp']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Élève</title>
    <link rel="stylesheet" href="eleve.css">
    <style>
        .dashboard-container {
            max-width: 800px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .tp-section {
            margin-bottom: 30px;
        }
        .tp-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .tp-description {
            color: #666;
            margin-bottom: 15px;
        }
        .tache {
            background: #f9f9f9;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border-left: 4px solid #fc8b01;
        }
        .tache-title {
            font-weight: bold;
        }
        .tache-description {
            color: #666;
        }
        .statut {
            font-size: 12px;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="title">Dashboard Élève</div>
        <div class="topbar-right">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['utilisateur']['nom']); ?></span>
            <img src="image/user-avatar.png" alt="Avatar" class="user-avatar">
        </div>
    </div>

    <div class="logo-container">
        <img src="image/Logo entreprise.png" alt="Logo" class="login-logo">
    </div>

    <nav class="sidebar" aria-label="Navigation principale">
        <button class="nav-btn" onclick="window.location.href='index.php'">Accueil</button>
        <button class="nav-btn" onclick="window.location.href='tp_promo.php'">Travaux Pratiques</button>
    </nav>

    <main class="content">
        <div class="dashboard-container">
            <h1>Mes Travaux Pratiques en Cours</h1>
            
            <?php if (empty($tps)): ?>
                <p>Aucun TP en cours pour le moment.</p>
            <?php else: ?>
                <?php foreach ($tps as $tp): ?>
                    <div class="tp-section">
                        <div class="tp-title"><?php echo htmlspecialchars($tp['titre']); ?></div>
                        <div class="tp-description"><?php echo htmlspecialchars($tp['description']); ?></div>
                        
                        <h3>Tâches associées :</h3>
                        <?php if (empty($taches_par_tp[$tp['id_tp']])): ?>
                            <p>Aucune tâche assignée.</p>
                        <?php else: ?>
                            <?php foreach ($taches_par_tp[$tp['id_tp']] as $tache): ?>
                                <div class="tache">
                                    <div class="tache-title"><?php echo htmlspecialchars($tache['libelle']); ?></div>
                                    <div class="tache-description"><?php echo htmlspecialchars($tache['description']); ?></div>
                                    <div class="statut">Priorité: <?php echo htmlspecialchars($tache['priorite']); ?> | Ordre: <?php echo htmlspecialchars($tache['ordre']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>