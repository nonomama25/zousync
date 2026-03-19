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
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $mdp = $_POST['password'];
    $code = $_POST['code'];

    if ($code !== '19122007') {
        $message = "<div class='alert alert-danger text-center'>Code incorrect</div>";
    } else {
        // Hash le mot de passe
        $hashed_mdp = password_hash($mdp, PASSWORD_DEFAULT);

        // Insérer dans la base
        $sql = "INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, est_admin) VALUES (:nom, :prenom, :email, :mdp, 1)";
        $stmt = $route->prepare($sql);
        try {
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':mdp' => $hashed_mdp
            ]);
            $message = "<div class='alert alert-success text-center'>Compte admin créé avec succès</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger text-center'>Erreur lors de la création : " . $e->getMessage() . "</div>";
        }
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
    <title>Créer Compte Admin</title>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <img src="image/Logo entreprise.png" alt="Logo" class="login-logo">
        <h2>Créer un compte admin</h2>
    </div>

    <!-- Message PHP -->
    <?= $message ?>

    <!-- Formulaire -->
    <form method="POST" action="">
        <div class="mb-3">
            <label for="nom" class="form-label"><i class="fas fa-user"></i> Nom</label>
            <input type="text" class="form-control input-custom" id="nom" name="nom" placeholder="Entrez votre nom" required>
        </div>

        <div class="mb-3">
            <label for="prenom" class="form-label"><i class="fas fa-user"></i> Prénom</label>
            <input type="text" class="form-control input-custom" id="prenom" name="prenom" placeholder="Entrez votre prénom" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
            <input type="email" class="form-control input-custom" id="email" name="email" placeholder="Entrez votre email" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label"><i class="fas fa-lock"></i> Mot de passe</label>
            <input type="password" class="form-control input-custom" id="password" name="password" placeholder="Entrez votre mot de passe" required>
        </div>

        <div class="mb-3">
            <label for="code" class="form-label"><i class="fas fa-key"></i> Code</label>
            <input type="text" class="form-control input-custom" id="code" name="code" placeholder="Entrez le code" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-custom">Créer le compte</button>
    </form>

    <div class="mt-3 text-center">
        <a href="login.php" class="btn btn-secondary">Retour à la connexion</a>
    </div>
</div>

</body>
</html>