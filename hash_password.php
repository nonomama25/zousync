<?php
// Script pour générer un hash de mot de passe

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $display_hash = true;
} else {
    $display_hash = false;
    $hash = "";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générateur de Hash</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .result {
            background-color: #f0f0f0;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Générateur de Hash de Mot de Passe</h2>
        
        <form method="POST">
            <label>Entrez le mot de passe :</label>
            <input type="text" name="password" required>
            <button type="submit">Générer le Hash</button>
        </form>

        <?php if ($display_hash): ?>
            <div class="result">
                <strong>Hash généré :</strong><br>
                <code><?= htmlspecialchars($hash) ?></code>
                <p style="font-size: 12px; color: #666;">Copiez ce hash et collez-le dans phpMyAdmin pour remplacer le mot de passe en base.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
