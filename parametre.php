<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres</title>
    <link rel="stylesheet" href="parametre.css">
    <link rel="stylesheet" href="parametre.css">
</head>
<body>
    <div class="topbar">
        <div class="title">Paramètres</div>
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
        <h1>Paramètres</h1>
        <section>
            <div class="cards-grid">
                <div class="card">Paramètre 1</div>
                <div class="card">Paramètre 2</div>
                <div class="card">Paramètre 3</div>
            </div>
        </section>
    </main>

    <button class="create-btn" title="Créer">+</button>
</body>
</html>
