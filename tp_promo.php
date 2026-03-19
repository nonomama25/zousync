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

$errors = [];
$success = '';

// Ajouter un nouveau TP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tp'])) {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $id_promotion = isset($_POST['id_promotion']) && $_POST['id_promotion'] !== '' ? (int)$_POST['id_promotion'] : null;

    if ($titre === '') {
        $errors[] = 'Le titre du TP est requis.';
    }
    if ($description === '') {
        $errors[] = 'La description du TP est requise.';
    }
    if (!$id_promotion) {
        $errors[] = 'Une promotion doit être sélectionnée.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO tp (titre, description, date_creation, id_promotion) VALUES (?, ?, NOW(), ?)');
            $stmt->execute([$titre, $description, $id_promotion]);
            $success = 'TP ajouté avec succès.';
        } catch (PDOException $e) {
            $errors[] = 'Erreur: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Ajouter une tâche à un TP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tache'])) {
    $libelle = trim($_POST['libelle'] ?? '');
    $description = trim($_POST['desc_tache'] ?? '');
    $priorite = trim($_POST['priorite'] ?? '');
    $ordre = isset($_POST['ordre']) ? (int)$_POST['ordre'] : 0;
    $id_tp = isset($_POST['id_tp']) ? (int)$_POST['id_tp'] : null;

    if ($libelle === '') {
        $errors[] = 'Le libellé de la tâche est requis.';
    }
    if (!$id_tp) {
        $errors[] = 'TP invalide.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO tache (libelle, priorite, ordre, description, id_tp) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$libelle, $priorite, $ordre, $description, $id_tp]);
            $success = 'Tâche ajoutée avec succès.';
        } catch (PDOException $e) {
            $errors[] = 'Erreur: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Récupérer toutes les promotions
$promotions = $pdo->query('SELECT id_promotion, nom FROM promotion ORDER BY nom')->fetchAll();

// Récupérer tous les TP avec leurs tâches, groupés par promotion
$tps_par_promo = [];
foreach ($promotions as $promo) {
    $stmt = $pdo->prepare('SELECT id_tp, titre, description FROM tp WHERE id_promotion = ? ORDER BY date_creation DESC');
    $stmt->execute([$promo['id_promotion']]);
    $tps = $stmt->fetchAll();

    foreach ($tps as &$tp) {
        $stmt = $pdo->prepare('SELECT id_tache, libelle, priorite, ordre, description FROM tache WHERE id_tp = ? ORDER BY ordre');
        $stmt->execute([$tp['id_tp']]);
        $tp['taches'] = $stmt->fetchAll();
    }

    $tps_par_promo[$promo['id_promotion']] = $tps;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion TP par Promotion</title>
    <link rel="stylesheet" href="eleve.css">
    <style>
        .form-container {
            max-width: 600px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .promo-section {
            margin-bottom: 40px;
        }
        .promo-title {
            font-size: 20px;
            font-weight: bold;
            color: #074383;
            margin-bottom: 15px;
        }
        .tp-item {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border-left: 4px solid #fc8b01;
        }
        .tp-title {
            font-weight: bold;
            color: #333;
        }
        .tp-desc {
            color: #666;
            margin: 5px 0;
        }
        .tache-item {
            background: #fff;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 3px solid #28a745;
        }
        .btn-add {
            background-color: #fc8b01;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-add:hover {
            background-color: #e67e00;
        }
        .error, .success {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .success {
            color: #155724;
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="title">Gestion TP par Promotion</div>
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
        <button class="nav-btn" onclick="window.location.href='menupromo.php'">Promotions</button>
        <button class="nav-btn" onclick="window.location.href='alleleves.php'">Eleves</button>
        <button class="nav-btn" onclick="window.location.href='travauxpratique.php'">Travaux Pratiques</button>
        <button class="nav-btn" onclick="window.location.href='parametre.php'">Paramètres</button>
    </nav>

    <main class="content">
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Ajouter un nouveau TP</h2>
            <form method="post" action="tp_promo.php">
                <div style="margin-bottom: 15px;">
                    <label for="id_promotion">Promotion *</label>
                    <select id="id_promotion" name="id_promotion" required style="width: 100%; padding: 8px; margin-top: 5px;">
                        <option value="">-- Sélectionner une promotion --</option>
                        <?php foreach ($promotions as $p): ?>
                            <option value="<?= $p['id_promotion'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="titre">Titre du TP *</label>
                    <input type="text" id="titre" name="titre" required style="width: 100%; padding: 8px; margin-top: 5px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required style="width: 100%; padding: 8px; margin-top: 5px; height: 80px;"></textarea>
                </div>
                <button type="submit" name="add_tp" class="btn-add">Ajouter TP</button>
            </form>
        </div>

        <h2>TP par Promotion</h2>
        <?php foreach ($promotions as $promo): ?>
            <div class="promo-section">
                <div class="promo-title"><?php echo htmlspecialchars($promo['nom']); ?></div>
                <?php if (empty($tps_par_promo[$promo['id_promotion']])): ?>
                    <p>Aucun TP pour cette promotion.</p>
                <?php else: ?>
                    <?php foreach ($tps_par_promo[$promo['id_promotion']] as $tp): ?>
                        <div class="tp-item">
                            <div class="tp-title"><?php echo htmlspecialchars($tp['titre']); ?></div>
                            <div class="tp-desc"><?php echo htmlspecialchars($tp['description']); ?></div>
                            
                            <h4>Tâches :</h4>
                            <?php if (empty($tp['taches'])): ?>
                                <p>Aucune tâche.</p>
                            <?php else: ?>
                                <?php foreach ($tp['taches'] as $tache): ?>
                                    <div class="tache-item">
                                        <strong><?php echo htmlspecialchars($tache['libelle']); ?></strong> (Priorité: <?php echo htmlspecialchars($tache['priorite']); ?>, Ordre: <?php echo htmlspecialchars($tache['ordre']); ?>)<br>
                                        <?php echo htmlspecialchars($tache['description']); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <h4>Ajouter une tâche :</h4>
                            <form method="post" action="tp_promo.php" style="margin-top: 10px;">
                                <input type="hidden" name="id_tp" value="<?php echo $tp['id_tp']; ?>">
                                <input type="text" name="libelle" placeholder="Libellé *" required style="width: 60%; padding: 5px;">
                                <input type="text" name="priorite" placeholder="Priorité" style="width: 15%; padding: 5px;">
                                <input type="number" name="ordre" placeholder="Ordre" style="width: 15%; padding: 5px;">
                                <br>
                                <textarea name="desc_tache" placeholder="Description" style="width: 100%; padding: 5px; margin-top: 5px; height: 50px;"></textarea>
                                <br>
                                <button type="submit" name="add_tache" class="btn-add" style="margin-top: 5px;">Ajouter Tâche</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>
</body>
</html>