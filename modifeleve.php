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
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    header('Location: alleleves.php');
    exit;
}

// Récupérer l'élève
$stmt = $pdo->prepare('SELECT * FROM utilisateur WHERE id = ? AND est_admin = 0');
$stmt->execute([$id]);
$eleve = $stmt->fetch();

if (!$eleve) {
    die('Élève introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $id_promotion_form = isset($_POST['promotion']) && $_POST['promotion'] !== '' ? (int)$_POST['promotion'] : null;
    
    if ($nom === '') {
        $errors[] = 'Le nom est requis.';
    }
    if ($prenom === '') {
        $errors[] = 'Le prénom est requis.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE utilisateur SET nom = ?, prenom = ?, id_promotion = ? WHERE id = ?');
            $stmt->execute([$nom, $prenom, $id_promotion_form, $id]);
            
            // Tenter de modifier aussi la table redondante "eleve" par nom si elle existe
            try {
                $stmtEleve = $pdo->prepare('UPDATE eleve SET Nom = ?, Promotion = ? WHERE Nom = ?');
                $stmtEleve->execute([$nom, $id_promotion_form, $eleve['nom']]);
            } catch(Exception $e) {
                // Ignore fallback failure
            }
            
            header('Location: alleleves.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erreur: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Récupérer toutes les promotions
$promotions = $pdo->query('SELECT id_promotion, nom FROM promotion ORDER BY nom')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un élève</title>
    <link rel="stylesheet" href="eleve.css">
    <style>
        .form-container {
            max-width: 500px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 4px rgba(0, 123, 255, 0.3);
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-submit {
            background-color: #074383;
            color: white;
        }
        .btn-submit:hover {
            background-color: #0f4e91;
        }
        .btn-cancel {
            background-color: #fc8b01;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #fc8b01;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="title">Modifier un élève</div>
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
        <button class="nav-btn" onclick="window.location.href='travauxpratique.php'">Travaux Pratiques</button>
        <button class="nav-btn" onclick="window.location.href='parametre.php'">Paramètres</button>
    </nav>

    <main class="content">
        <div class="form-container">
            <h1>Modifier les infos de l'élève</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" action="modifeleve.php?id=<?= $id ?>">
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? $eleve['nom']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? $eleve['prenom']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="promotion">Promotion</label>
                    <select id="promotion" name="promotion">
                        <option value="">-- Aucune --</option>
                        <?php 
                        $current_promo = isset($_POST['promotion']) ? $_POST['promotion'] : $eleve['id_promotion'];
                        foreach ($promotions as $p): 
                        ?>
                            <option value="<?= $p['id_promotion'] ?>" <?= $current_promo == $p['id_promotion'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn-submit">Sauvegarder</button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='alleleves.php'">Annuler</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
