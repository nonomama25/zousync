<?php
session_start();

function get_priorite_text($num) {
    $map = [1 => 'Basse', 2 => 'Moyenne', 3 => 'Haute'];
    return $map[$num] ?? 'Moyenne';
}

function get_priorite_num($text) {
    $map = ['basse' => 1, 'moyenne' => 2, 'haute' => 3];
    return $map[strtolower($text)] ?? 2;
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
            $id_tp = $pdo->lastInsertId();

            // Insérer les tâches si présentes
            if (isset($_POST['taches']) && is_array($_POST['taches'])) {
                foreach ($_POST['taches'] as $tache) {
                    $libelle = trim($tache['libelle'] ?? '');
                    $description_tache = trim($tache['description'] ?? '');
                    $priorite = get_priorite_num($tache['priorite'] ?? 'moyenne');
                    $ordre = isset($tache['ordre']) ? (int)$tache['ordre'] : 1;

                    if ($libelle !== '') {
                        $stmt = $pdo->prepare('INSERT INTO tache (libelle, description, priorite, ordre, id_tp) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$libelle, $description_tache, $priorite, $ordre, $id_tp]);
                    }
                }
            }

            $success = 'TP et tâches ajoutés avec succès.';
        } catch (PDOException $e) {
            $errors[] = 'Erreur: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Ajouter une tâche à un TP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tache'])) {
    $libelle = trim($_POST['libelle'] ?? '');
    $description = trim($_POST['desc_tache'] ?? '');
    $priorite = get_priorite_num(trim($_POST['priorite'] ?? ''));
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
        .content h1,
        .content h2 {
            color: #074383;
        }
        .form-container {
            max-width: 840px;
            background: linear-gradient(135deg, #fdfbfb 0%, #f5f7fa 100%);
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.08);
            margin-bottom: 45px;
            border: 1px solid rgba(0,0,0,0.08);
        }
        .form-container h2 {
            text-align: center;
            color: #222;
            margin-bottom: 30px;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: #3a3a3a;
            font-size: 15px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #d8d8d8;
            border-radius: 12px;
            font-size: 15px;
            background: #fff;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4b81ff;
            box-shadow: 0 0 18px rgba(75,129,255,0.18);
        }
        .form-group textarea {
            height: 95px;
            resize: vertical;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }
        .half {
            flex: 1;
            min-width: 175px;
        }
        .tache-group {
            background: #ffffff;
            padding: 22px;
            border-radius: 14px;
            margin-bottom: 18px;
            border: 1px solid #e2e8f0;
            position: relative;
        }
        .btn-add-task,
        .btn-action,
        .btn-progress,
        .btn-remove,
        .btn-submit,
        .btn-add {
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
        }
        .btn-add-task,
        .btn-action,
        .btn-progress,
        .btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            color: #fff;
            background: #4b81ff;
            box-shadow: 0 12px 30px rgba(75,129,255,0.18);
            text-decoration: none;
        }
        .btn-add-task:hover,
        .btn-action:hover,
        .btn-progress:hover,
        .btn-submit:hover {
            transform: translateY(-1px);
            background: #1a63e8;
        }
        .btn-remove {
            background: #ff5a5f;
            color: white;
            font-size: 14px;
            padding: 10px 16px;
            position: absolute;
            top: 18px;
            right: 18px;
        }
        .btn-remove:hover {
            background: #d83b45;
        }
        .btn-add {
            background-color: #fc8b01;
            color: white;
            padding: 10px 16px;
        }
        .btn-add:hover {
            background-color: #e57a00;
        }
        .form-actions {
            text-align: center;
            margin-top: 30px;
        }
        .btn-submit {
            padding: 16px 34px;
            font-size: 18px;
            letter-spacing: 0.02em;
            box-shadow: 0 18px 36px rgba(0,0,0,0.14);
        }
        .promo-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            margin-top: 30px;
        }
        .promo-card {
            background: #ffffff;
            padding: 28px 24px;
            border-radius: 18px;
            border: 1px solid rgba(34,80,161,0.1);
            box-shadow: 0 18px 40px rgba(27,72,163,0.05);
        }
        .promo-card .promo-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 10px;
            color: #10316b;
        }
        .promo-card .promo-meta {
            font-size: 14px;
            color: #4f5f7c;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .promo-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .error, .success {
            padding: 16px 18px;
            border-radius: 12px;
            margin-bottom: 26px;
            font-weight: 600;
        }
        .error {
            color: #842029;
            background: #f8d7da;
            border-left: 5px solid #d9534f;
        }
        .success {
            color: #0f5132;
            background: #d1e7dd;
            border-left: 5px solid #198754;
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
        <a href="index.php" class="nav-btn">Accueil</a>
        <a href="menupromo.php" class="nav-btn">Promotions</a>
        <a href="alleleves.php" class="nav-btn">Eleves</a>
        <a href="tp_promo.php" class="nav-btn">Travaux Pratiques</a>
        <a href="parametre.php" class="nav-btn">Paramètres</a>
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

        <?php if (isset($_GET['success'])): ?>
            <div class="success">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Ajouter un nouveau TP</h2>
            <form method="post" action="tp_promo.php" id="tp-form">
                <div class="form-group">
                    <label for="id_promotion">Promotion *</label>
                    <select id="id_promotion" name="id_promotion" required>
                        <option value="">-- Sélectionner une promotion --</option>
                        <?php foreach ($promotions as $p): ?>
                            <option value="<?= $p['id_promotion'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="titre">Titre du TP *</label>
                    <input type="text" id="titre" name="titre" required>
                </div>
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div id="taches-container">
                    <h3>Tâches associées</h3>
                    <div class="tache-group" data-index="0">
                        <button type="button" class="btn-remove" style="display:none;">Supprimer</button>
                        <div class="form-group">
                            <label class="task-label-libelle">Libellé de la tâche 1 *</label>
                            <input type="text" name="taches[0][libelle]" required>
                        </div>
                        <div class="form-group">
                            <label class="task-label-description">Description de la tâche 1</label>
                            <textarea name="taches[0][description]"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group half">
                                <label>Priorité</label>
                                <select name="taches[0][priorite]">
                                    <option value="1">Basse</option>
                                    <option value="2">Moyenne</option>
                                    <option value="3">Haute</option>
                                </select>
                            </div>
                            <div class="form-group half">
                                <label>Ordre</label>
                                <input type="number" name="taches[0][ordre]" value="1" min="1">
                            </div>
                        </div>
                    </div>
                    <div class="tache-group" data-index="1">
                        <button type="button" class="btn-remove" style="display:none;">Supprimer</button>
                        <div class="form-group">
                            <label class="task-label-libelle">Libellé de la tâche 2</label>
                            <input type="text" name="taches[1][libelle]">
                        </div>
                        <div class="form-group">
                            <label class="task-label-description">Description de la tâche 2</label>
                            <textarea name="taches[1][description]"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group half">
                                <label>Priorité</label>
                                <select name="taches[1][priorite]">
                                    <option value="1">Basse</option>
                                    <option value="2">Moyenne</option>
                                    <option value="3">Haute</option>
                                </select>
                            </div>
                            <div class="form-group half">
                                <label>Ordre</label>
                                <input type="number" name="taches[1][ordre]" value="2" min="1">
                            </div>
                        </div>
                    </div>
                    <div class="tache-group" data-index="2">
                        <button type="button" class="btn-remove" style="display:none;">Supprimer</button>
                        <div class="form-group">
                            <label class="task-label-libelle">Libellé de la tâche 3</label>
                            <input type="text" name="taches[2][libelle]">
                        </div>
                        <div class="form-group">
                            <label class="task-label-description">Description de la tâche 3</label>
                            <textarea name="taches[2][description]"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group half">
                                <label>Priorité</label>
                                <select name="taches[2][priorite]">
                                    <option value="1">Basse</option>
                                    <option value="2">Moyenne</option>
                                    <option value="3">Haute</option>
                                </select>
                            </div>
                            <div class="form-group half">
                                <label>Ordre</label>
                                <input type="number" name="taches[2][ordre]" value="3" min="1">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="add_tp" class="btn-submit">Ajouter TP</button>
                </div>
            </form>
        </div>

<<<<<<< HEAD
        <h2>TP par Promotion</h2>
        <?php foreach ($promotions as $promo): ?>
            <div class="promo-section">
                <div class="promo-title"><?php echo htmlspecialchars($promo['nom']); ?></div>
                <?php if (empty($tps_par_promo[$promo['id_promotion']])): ?>
                    <p>Aucun TP pour cette promotion.</p>
                <?php else: ?>
                    <?php foreach ($tps_par_promo[$promo['id_promotion']] as $tp): ?>
                        <div class="tp-item">
                            <div class="tp-title"><?php echo htmlspecialchars($tp['titre']); ?> <a href="confirm_delete.php?type=tp&id=<?php echo $tp['id_tp']; ?>" class="btn-delete" style="float:right; font-size:14px;">Supprimer TP</a></div>
                            <div class="tp-desc"><?php echo htmlspecialchars($tp['description']); ?></div>
                            
                            <h4>Tâches :</h4>
                            <?php if (empty($tp['taches'])): ?>
                                <p>Aucune tâche.</p>
                            <?php else: ?>
                                <?php foreach ($tp['taches'] as $tache): ?>
                                    <div class="tache-item">
                                        <strong><?php echo htmlspecialchars($tache['libelle']); ?></strong> (Priorité: <?php echo htmlspecialchars(get_priorite_text($tache['priorite'])); ?>, Ordre: <?php echo htmlspecialchars($tache['ordre']); ?>) <a href="confirm_delete.php?type=tache&id=<?php echo $tache['id_tache']; ?>" class="btn-delete" style="float:right; font-size:12px;">×</a><br>
                                        <?php echo htmlspecialchars($tache['description']); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <h4>Ajouter une tâche :</h4>
                            <form method="post" action="tp_promo.php" style="margin-top: 10px;">
                                <input type="hidden" name="id_tp" value="<?php echo $tp['id_tp']; ?>">
                                <input type="text" name="libelle" placeholder="Libellé *" required style="width: 40%; padding: 5px;">
                                <select name="priorite" style="width: 20%; padding: 5px;">
                                    <option value="1">Basse</option>
                                    <option value="2">Moyenne</option>
                                    <option value="3">Haute</option>
                                </select>
                                <input type="number" name="ordre" placeholder="Ordre" style="width: 20%; padding: 5px;">
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
=======
        <h2>Promotions</h2>
        <div class="promo-grid">
            <?php foreach ($promotions as $promo): ?>
                <div class="promo-card">
                    <div class="promo-title"><?php echo htmlspecialchars($promo['nom']); ?></div>
                    <div class="promo-meta"><?php echo count($tps_par_promo[$promo['id_promotion']]) ?> TP en cours</div>
                    <div class="promo-actions">
                        <a href="promo_progress.php?id_promotion=<?php echo $promo['id_promotion']; ?>" class="btn-progress">Voir le suivi</a>
                        <a href="eleves_promo.php?id_promotion=<?php echo $promo['id_promotion']; ?>" class="btn-action">Voir élèves</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
>>>>>>> c82fc4817a2f7975b02d31e6c53e42590f9ace2c
    </main>
</body>
</html>