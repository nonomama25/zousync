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

// Récupérer toutes les promotions
$promotions = $pdo->query('SELECT id_promotion, nom FROM promotion ORDER BY nom')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Menu Promotion</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="menupromo.css">
    </head>
    <body>
        <div class="topbar">
            <div class="title">Dashboard Promotion</div>
            <div class="topbar-right">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['utilisateur']['nom']); ?></span>

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
            
            <h1>Promotions</h1>
            <section>
                <div class="cards-grid">
                    <?php if (empty($promotions)): ?>
                        <p>Aucune promotion trouvée.</p>
                    <?php else: ?>
                        <?php foreach ($promotions as $p): ?>
                            <a class="card" href="eleves_promo.php?id_promotion=<?= htmlspecialchars($p['id_promotion']) ?>"><?= htmlspecialchars($p['nom']) ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
            
        </main>
        <!--<table class = "table">
        <thead>
            <tr>
                <th>Promotions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            //foreach ($promotions as $ligne){
            ?>
            <tr>
                <td><?php
                 //echo ($ligne["nom"]);

                ?> </td>
                
                <td><?php //echo ($ligne["nom"]); ?></td>
                <td><?php //echo ($ligne["nombre_eleves"]); ?></td>
                <td><button type="button" class="btn btn-danger btn-delete" data-id="<?php //echo $ligne["id"]; ?>" data-title="<?php //echo htmlspecialchars($ligne['nom'], ENT_QUOTES); ?>">Supprimer</button></td>
                <td><a href="change.php?id=<?php //echo $ligne["id"]; ?>"><button type="button" class="btn btn-warning">Modifier</button></a></td>
            </tr>
            <?php 
            //}
            ?>
        </tbody>
    </table> -->

        <button class="create-btn" title="Créer" onclick="window.location.href='addpromo.php'">+</button>
    </body>
</html>