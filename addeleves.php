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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $id_promotion = isset($_POST['id_promotion']) && $_POST['id_promotion'] !== '' ? (int)$_POST['id_promotion'] : null;

    // Validation
    if (empty($nom)) {
        $errors[] = 'Le nom est requis.';
    }
    if (empty($prenom)) {
        $errors[] = 'Le prénom est requis.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    if (empty($mot_de_passe)) {
    $errors[] = 'Le mot de passe temporaire est requis.';
}
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Cet email est déjà utilisé.';
            } else {
                $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare('INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, id_promotion, est_admin, first_login) VALUES (?, ?, ?, ?, ?, 0, 1)');
                $stmt->execute([$nom, $prenom, $email, $hash, $id_promotion]);


                $success = 'Élève ajouté avec succès. Un mot de passe temporaire lui a été attribué.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de l\'ajout : ' . htmlspecialchars($e->getMessage());
        }
    }
$promotions = $pdo->query('SELECT id_promotion, nom FROM promotion ORDER BY nom')->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un élève</title>
    <link rel="stylesheet" href="eleve.css">
    <link rel="stylesheet" href="menupromo.css">
    <style>
        .form-container {
            max-width: 600px;z 
            margin: 50px auto;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 1px solid #e0e0e0;
        }
        .form-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #555;
            font-size: 16px;
        }
        input, select {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
        }
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn-submit {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #218838, #1aa085);
            transform: translateY(-2px);
        }
        .btn-cancel {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            text-decoration: none;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
        }
        .btn-cancel:hover {
            background: linear-gradient(135deg, #bd2130, #a02622);
            transform: translateY(-2px);
        }
        .error {
            color: #721c24;
            background: #f8d7da;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 5px solid #dc3545;
            font-weight: 500;
        }
        .success {
            color: #155724;
            background: #d4edda;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 5px solid #28a745;
            font-weight: 500;
        }
        .error ul {
            margin: 0;
            padding-left: 20px;
        }
        .error li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="title">Ajouter un élève</div>
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
        <div class="form-container">
            <h1>Ajouter un nouvel élève</h1>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe *</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>

                <div class="form-group">
                    <label for="id_promotion">Promotion</label>
                    <select id="id_promotion" name="id_promotion">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($promotions as $promotion): ?>
                            <option value="<?php echo $promotion['id_promotion']; ?>" <?php echo (isset($_POST['id_promotion']) && $_POST['id_promotion'] == $promotion['id_promotion']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($promotion['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-submit">Ajouter</button>
                    <a href="alleleves.php" class="btn-cancel">Annuler</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
