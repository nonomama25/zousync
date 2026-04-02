<?php
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['utilisateur'];

// Si l'utilisateur a déjà changé son mot de passe → redirection normale
if ($user['first_login'] == 0) {
    if ($user["est_admin"] == 1) {
        header("Location: menupromo.php");
    } else {
        header("Location: dashboard_eleve.php");
    }
    exit;
}

// Connexion BDD
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=tp;charset=utf8',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

$message = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $message = "<div class='alert alert-danger text-center'>Le mot de passe doit faire au moins 6 caractères.</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-danger text-center'>Les mots de passe ne correspondent pas.</div>";
    } else {
        // Hash du nouveau mot de passe
        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Mise à jour en base
        $stmt = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ?, first_login = 0 WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        // Mise à jour de la session
        $_SESSION['utilisateur']['first_login'] = 0;

        // Redirection
        if ($user["est_admin"] == 1) {
            header("Location: menupromo.php");
        } else {
            header("Location: dashboard_eleve.php");
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>Changer votre mot de passe</title>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <img src="image/Logo entreprise.png" alt="Logo" class="login-logo">
        <h2>Changer votre mot de passe</h2>
    </div>

    <?= $message ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-lock"></i> Nouveau mot de passe</label>
            <input type="password" class="form-control input-custom" name="new_password" placeholder="Entrez un nouveau mot de passe" required>
        </div>

        <div class="mb-3">
            <label class="form-label"><i class="fas fa-lock"></i> Confirmer le mot de passe</label>
            <input type="password" class="form-control input-custom" name="confirm_password" placeholder="Confirmez le mot de passe" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-custom">Valider</button>
    </form>
</div>

</body>
</html>
