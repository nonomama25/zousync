<?php
session_start();

function get_priorite_text($num) {
    $map = [1 => 'Basse', 2 => 'Moyenne', 3 => 'Haute'];
    return $map[$num] ?? 'Moyenne';
}

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

$id_eleve = isset($_GET['id_eleve']) ? (int)$_GET['id_eleve'] : 0;
if (!$id_eleve) {
    die('Élève non spécifié.');
}

// Récupérer les infos de l'élève
$stmt = $pdo->prepare('SELECT nom, prenom, id_promotion FROM utilisateur WHERE id = ? AND est_admin = 0');
$stmt->execute([$id_eleve]);
$eleve = $stmt->fetch();
if (!$eleve) {
    die('Élève non trouvé.');
}

// Récupérer les TP de la promotion de l'élève
$stmt = $pdo->prepare('
    SELECT tp.id_tp, tp.titre, tp.description
    FROM tp
    WHERE tp.id_promotion = ?
');
$stmt->execute([$eleve['id_promotion']]);
$tps = $stmt->fetchAll();

// Pour chaque TP, récupérer les tâches associées
$taches_par_tp = [];
foreach ($tps as $tp) {
    $stmt = $pdo->prepare('SELECT id_tache, libelle, description, priorite, ordre FROM tache WHERE id_tp = ? ORDER BY ordre');
    $stmt->execute([$tp['id_tp']]);
    $taches = $stmt->fetchAll();

    // Récupérer les tâches terminées pour cet élève
    $completed = [];
    if (!empty($taches)) {
        $ids = array_column($taches, 'id_tache');
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id_tache FROM tache_complete WHERE id_utilisateur = ? AND id_tache IN ($placeholders)");
        $stmt->execute(array_merge([$id_eleve], $ids));
        $completed = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    $taches_par_tp[$tp['id_tp']] = ['taches' => $taches, 'completed' => $completed];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progression de <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></title>
    <link rel="stylesheet" href="eleve.css">
    <style>
        .completed {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 5px solid #28a745;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        .completed::before {
            content: "✓";
            position: absolute;
            top: 10px;
            right: 10px;
            color: #28a745;
            font-size: 18px;
            font-weight: bold;
        }
        .not-completed {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left: 5px solid #dc3545;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        .not-completed::before {
            content: "✗";
            position: absolute;
            top: 10px;
            right: 10px;
            color: #dc3545;
            font-size: 18px;
            font-weight: bold;
        }
        .tache {
            margin-bottom: 15px;
            border-radius: 8px;
            padding: 15px;
            transition: transform 0.2s;
        }
        .tache:hover {
            transform: translateY(-2px);
        }
        .tp-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .tp-section h2 {
            color: #074383;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="title">Progression de <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></div>
        <div class="topbar-right">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['utilisateur']['nom']); ?></span>
            <img src="image/user-avatar.png" alt="Avatar" class="topbar-avatar">
        </div>
    </div>

    <div class="logo-container">
        <img src="image/Logo entreprise.png" alt="Logo" class="login-logo">
    </div>

    <nav class="sidebar" aria-label="Navigation principale">
        <button class="nav-btn" onclick="window.location.href='index.php'">Accueil</button>
        <button class="nav-btn" onclick="window.location.href='menupromo.php'">Promotions</button>
        <button class="nav-btn" onclick="window.location.href='alleleves.php'">Eleves</button>
        <button class="nav-btn" onclick="window.location.href='tp_promo.php'">Travaux Pratiques</button>
        <button class="nav-btn" onclick="window.location.href='parametre.php'">Paramètres</button>
    </nav>

    <main class="content">
        <h1>Progression de <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></h1>
        <?php if (empty($tps)): ?>
            <p>Aucun TP trouvé pour cette promotion.</p>
        <?php else: ?>
            <?php foreach ($tps as $tp): ?>
                <section class="tp-section">
                    <h2><?php echo htmlspecialchars($tp['titre']); ?></h2>
                    <p><?php echo htmlspecialchars($tp['description']); ?></p>
                    <div class="taches-container">
                        <?php if (empty($taches_par_tp[$tp['id_tp']]['taches'])): ?>
                            <p>Aucune tâche pour ce TP.</p>
                        <?php else: ?>
                            <?php foreach ($taches_par_tp[$tp['id_tp']]['taches'] as $tache): ?>
                                <div class="tache <?php echo in_array($tache['id_tache'], $taches_par_tp[$tp['id_tp']]['completed']) ? 'completed' : 'not-completed'; ?>">
                                    <div class="tache-title"><?php echo htmlspecialchars($tache['libelle']); ?></div>
                                    <div class="tache-description"><?php echo htmlspecialchars($tache['description']); ?></div>
                                    <div class="statut">Priorité: <?php echo htmlspecialchars(get_priorite_text($tache['priorite'])); ?> | Ordre: <?php echo htmlspecialchars($tache['ordre']); ?> | Statut: <?php echo in_array($tache['id_tache'], $taches_par_tp[$tp['id_tp']]['completed']) ? 'Terminée' : 'Non terminée'; ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>