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

if ($_SESSION['utilisateur']['est_admin'] != 1) {
    header('Location: login.php');
    exit;
}

$id_promotion = isset($_GET['id_promotion']) ? (int)$_GET['id_promotion'] : 0;
if ($id_promotion <= 0) {
    die('Promotion invalide. <a href="tp_promo.php">Retour</a>');
}

$stmt = $pdo->prepare('SELECT nom FROM promotion WHERE id_promotion = ?');
$stmt->execute([$id_promotion]);
$promo_name = $stmt->fetchColumn();
if (!$promo_name) {
    die('Promotion introuvable. <a href="tp_promo.php">Retour</a>');
}

$stmt = $pdo->prepare('SELECT COUNT(t.id_tache) FROM tache t JOIN tp ON t.id_tp = tp.id_tp WHERE tp.id_promotion = ?');
$stmt->execute([$id_promotion]);
$total_tasks = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT id_tp, titre, description FROM tp WHERE id_promotion = ? ORDER BY date_creation DESC');
$stmt->execute([$id_promotion]);
$tps = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT u.id, u.nom, u.prenom, IFNULL((SELECT COUNT(*) FROM tache_complete tc JOIN tache ta ON tc.id_tache = ta.id_tache JOIN tp tp2 ON ta.id_tp = tp2.id_tp WHERE tc.id_utilisateur = u.id AND tp2.id_promotion = ?), 0) AS completed_count FROM utilisateur u WHERE u.id_promotion = ? AND u.est_admin = 0 ORDER BY u.nom, u.prenom');
$stmt->execute([$id_promotion, $id_promotion]);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avancement élèves - <?= htmlspecialchars($promo_name) ?></title>
    <link rel="stylesheet" href="eleve.css">
    <style>
        .progress-container {
            max-width: 980px;
            margin: 0 auto;
            background: #ffffff;
            padding: 32px;
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(15, 31, 75, 0.08);
            border: 1px solid rgba(36, 85, 151, 0.12);
        }
        .progress-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-end;
            gap: 14px;
            margin-bottom: 28px;
        }
        .progress-header h1 {
            margin: 0;
            font-size: 30px;
            color: #10316b;
        }
        .stats-block {
            background: #f6f9ff;
            color: #1a3b7c;
            padding: 18px 22px;
            border-radius: 14px;
            border: 1px solid #cdd8f1;
            min-width: 220px;
        }
        .stats-block strong {
            display: block;
            font-size: 22px;
            margin-bottom: 6px;
        }
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        .students-table th,
        .students-table td {
            padding: 16px 18px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }
        .students-table th {
            color: #0f3c81;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 14px;
        }
        .students-table tbody tr:hover {
            background: #f7faff;
        }
        .progress-bar {
            width: 100%;
            height: 14px;
            background: #e9eef9;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056d6);
            border-radius: 999px;
            transition: width 0.35s ease;
        }
        .progress-label {
            margin-top: 8px;
            font-size: 13px;
            color: #4b5a7a;
        }
        .empty-state {
            padding: 24px;
            border-radius: 16px;
            background: #f8f9ff;
            border: 1px solid #dbe2f4;
            color: #415070;
            font-size: 16px;
        }
        .back-link {
            display: inline-block;
            margin-top: 18px;
            color: #ffffff;
            background: #1d4ed8;
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
        }
        .back-link:hover {
            background: #1846b6;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="title">Avancement élèves</div>
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
        <div class="progress-container">
            <div class="progress-header">
                <div>
                    <h1>Avancement - <?= htmlspecialchars($promo_name) ?></h1>
                    <p style="color:#4f5f75; margin: 8px 0 0;">Suivi global des élèves et de leur progression sur les TP de cette promotion.</p>
                </div>
                <div class="stats-block">
                    <strong><?= count($students) ?> élève<?php echo (count($students) > 1 ? 's' : ''); ?></strong>
                    Total élèves
                </div>
                <div class="stats-block">
                    <strong><?= $total_tasks ?></strong>
                    Total tâches TP
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <h2 style="margin-bottom: 16px; color:#0f3d91;">TP en cours</h2>
                <?php if (empty($tps)): ?>
                    <p style="color:#4f5f7c; margin:0 0 0 4px;">Aucun TP actif dans cette promotion.</p>
                <?php else: ?>
                    <div style="display: grid; gap: 14px;">
                        <?php foreach ($tps as $tp): ?>
                            <div style="background:#f8fbff; padding:18px 20px; border-radius:14px; border:1px solid rgba(34,80,161,0.12);">
                                <div style="font-weight:700; color:#10316b; font-size:16px; margin-bottom:6px;"><?= htmlspecialchars($tp['titre']) ?></div>
                                <div style="color:#4f5f7c; line-height:1.5;"><?= nl2br(htmlspecialchars($tp['description'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($students)): ?>
                <div class="empty-state">
                    Aucun élève n'est encore ajouté à cette promotion.<br>
                    <a class="back-link" href="tp_promo.php">Retour à la gestion TP</a>
                </div>
            <?php else: ?>
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Progression</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student):
                            $completed = (int)$student['completed_count'];
                            $percentage = $total_tasks > 0 ? round(($completed / $total_tasks) * 100) : 0;
                            $status = $percentage === 100 ? 'Terminé' : ($percentage > 0 ? 'En cours' : 'Pas commencé');
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $percentage ?>%;"></div>
                                </div>
                                <div class="progress-label"><?= $percentage ?>% (<?= $completed ?>/<?= $total_tasks ?>)</div>
                            </td>
                            <td><?= htmlspecialchars($status); ?></td>
                            <td><a class="back-link" style="background:#10b981;" href="progression.php?id_eleve=<?= $student['id'] ?>">Voir élève</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <a class="back-link" href="tp_promo.php">Retour à la gestion TP</a>
        </div>
    </main>
</body>
</html>
