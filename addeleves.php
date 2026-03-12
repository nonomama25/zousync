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

$errors = [];
$id_promotion = isset($_GET['id_promotion']) ? (int)$_GET['id_promotion'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $id_promotion_form = isset($_POST['promotion']) && $_POST['promotion'] !== '' ? (int)$_POST['promotion'] : null;
    
    if ($nom === '') {
        $errors[] = 'Le nom est requis.';
    }
    if ($prenom === '') {
        $errors[] = 'Le prénom est requis.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    if ($mot_de_passe === '' || strlen($mot_de_passe) < 6) {
        $errors[] = 'Le mot de passe doit faire au moins 6 caractères.';
    }
    
    if (empty($errors)) {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Cet email est déjà utilisé.';
            } else {
                // Crypter le mot de passe
                $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                
                // Démarrer une transaction
                $pdo->beginTransaction();
                
                // Insérer l'utilisateur
                $stmt = $pdo->prepare('INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, id_promotion) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$nom, $prenom, $email, $hash, $id_promotion_form ?: null]);
                
                // Insérer aussi dans la table eleve
                $stmt = $pdo->prepare('INSERT INTO eleve (Nom, Promotion) VALUES (?, ?)');
                $stmt->execute([$nom, $id_promotion_form ?: null]);
                
                $pdo->commit();
                
                header('Location: ' . ($id_promotion_form ? 'eleves_promo.php?id_promotion=' . $id_promotion_form : 'alleleves.php'));
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
    <title>Ajouter un élève</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="menupromo.css">
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
            border-color: #28a745;
            box-shadow: 0 0 4px rgba(40, 167, 69, 0.3);
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
            background-color: #28a745;
            color: white;
        }
        .btn-submit:hover {
            background-color: #218838;
        }
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
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
        <div class="title">Ajouter un élève</div>
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
        <div class="form-container">
            <h1>Ajouter un nouvel élève</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" action="addeleves.php">
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe (min 6 caractères) *</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                
                <div class="form-group">
                    <label for="promotion">Promotion</label>
                    <select id="promotion" name="promotion">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($promotions as $p): ?>
                            <option value="<?= $p['id_promotion'] ?>" 
                                <?= ($id_promotion && $id_promotion == $p['id_promotion']) || (isset($_POST['promotion']) && $_POST['promotion'] == $p['id_promotion']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn-submit">Ajouter</button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='alleleves.php'">Annuler</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
