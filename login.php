<?php
session_start();
// --- Connexion à la base ---
try {
    $route = new PDO(
        'mysql:host=localhost;dbname=tp;charset=utf8',
        'root',   // utilisateur MySQL (Laragon par défaut : 'root')
        ''        // mot de passe MySQL (Laragon par défaut : vide)
    );
    $route->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// --- Vérification du formulaire ---
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $prenom = $_POST['identifier'];
    $mdp = $_POST['password'];

    $sql = "SELECT * FROM utilisateur WHERE email = :email";
    $stmt = $route->prepare($sql);
    $stmt->execute([
        ':email' => $prenom
    ]);

    $user = $stmt->fetch();

    // password_verify compare le mot de passe en clair avec celui crypté dans la base
    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        $_SESSION['utilisateur'] = $user; // Sauvegarde de la session de l'utilisateur
        
        if ($user["est_admin"] == 1) {
            header("Location: menupromo.php");
        } else {
            header("Location: dashboard_eleve.php");
        }
        exit;
    } else {
        $message = "<div class='alert alert-danger text-center'>Email ou mot de passe incorrect</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <title>LOGIN</title>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <img src="image/Logo entreprise.png" alt="Logo" class="login-logo">
        <h2>Connexion</h2>
    </div>

    <!-- Message PHP -->
    <?= $message ?>

    <!-- Formulaire -->
    <form method="POST" action="">
        <div class="mb-3">
            <label for="identifier" class="form-label"><i class="fas fa-user"></i> Email</label>
            <input type="text" class="form-control input-custom" id="identifier" name="identifier" placeholder="Entrez votre email" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label"><i class="fas fa-lock"></i> Mot de passe</label>
            <input type="password" class="form-control input-custom" id="password" name="password" placeholder="Entrez votre mot de passe" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-custom">Se connecter</button>

    </form>

    <div class="mt-3 text-center">
        <a href="create_admin.php" class="btn btn-secondary">Créer un compte admin</a>
    </div>
</div>


</body>
</html>