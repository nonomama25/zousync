<?php
session_start();

function get_priorite_text($num) {
    $map = [1 => 'Basse', 2 => 'Moyenne', 3 => 'Haute'];
    return $map[$num] ?? 'Moyenne';
}

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

if (!isset($_SESSION['utilisateur'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['utilisateur']['est_admin'] == 1) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['utilisateur']['id'];

$stmt = $pdo->prepare('SELECT id_promotion FROM utilisateur WHERE id = ?');
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();

if (!$user_data || !$user_data['id_promotion']) {
    $tps = [];
    $no_promotion = true;
} else {
    $stmt = $pdo->prepare('
        SELECT tp.id_tp, tp.titre, tp.description
        FROM tp
        WHERE tp.id_promotion = ?
    ');
    $stmt->execute([$user_data['id_promotion']]);
    $tps = $stmt->fetchAll();
    $no_promotion = false;
}

$taches_par_tp = [];
foreach ($tps as $tp) {
    $stmt = $pdo->prepare('SELECT id_tache, libelle, description, priorite, ordre FROM tache WHERE id_tp = ? ORDER BY ordre');
    $stmt->execute([$tp['id_tp']]);
    $taches_par_tp[$tp['id_tp']] = $stmt->fetchAll();
}

$completed = [];
$stmt = $pdo->prepare('SELECT id_tache FROM tache_complete WHERE id_utilisateur = ?');
$stmt->execute([$user_id]);
$completed = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_completed'])) {
    $completed_ids = array_keys($_POST['completed'] ?? []);
    $stmt = $pdo->prepare('DELETE FROM tache_complete WHERE id_utilisateur = ?');
    $stmt->execute([$user_id]);
    if (!empty($completed_ids)) {
        $stmt = $pdo->prepare('INSERT INTO tache_complete (id_tache, id_utilisateur, date_complete) VALUES (?, ?, NOW())');
        foreach ($completed_ids as $id_tache) {
            $stmt->execute([$id_tache, $user_id]);
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
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
        .checkbox-label {
            display: flex;
            align-items: center;
            margin-top: 8px;
            font-weight: bold;
            color: #074383;
        }
        .checkbox-label input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.2);
        }
        .btn-submit {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
        <a href="index.php" class="nav-btn">Accueil</a>
        <a href="tp_promo.php" class="nav-btn">Travaux Pratiques</a>
    </nav>

    <main class="content">
        <form method="post">
        <div class="dashboard-container">
            <h1>Mes Travaux Pratiques en Cours</h1>
            
            <?php if ($no_promotion): ?>
                <p>Vous n'êtes assigné à aucune promotion. Contactez un administrateur pour vous assigner à une promotion.</p>
            <?php elseif (empty($tps)): ?>
                <p>Aucun TP en cours pour le moment dans votre promotion.</p>
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
                                    <div class="statut">Priorité: <?php echo htmlspecialchars(get_priorite_text($tache['priorite'])); ?> | Ordre: <?php echo htmlspecialchars($tache['ordre']); ?></div>
                                    <label class="checkbox-label"><input type="checkbox" name="completed[<?php echo $tache['id_tache']; ?>]" value="1" <?php if (in_array($tache['id_tache'], $completed)) echo 'checked'; ?>> Marquer comme terminée</label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="submit" name="update_completed" class="btn-submit">Mettre à jour les tâches terminées</button>
        </form>
    </main>
</body>
</html>