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

// Vérifier si l'utilisateur est admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['est_admin']) || $_SESSION['est_admin'] != 1) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    try {
        // D'abord on récupère le nom pour la table redondante
        $stmt = $pdo->prepare('SELECT nom FROM utilisateur WHERE id = ?');
        $stmt->execute([$id]);
        $eleve = $stmt->fetch();
        
        if ($eleve) {
            // Supprimer de utilisateur
            $stmtDel = $pdo->prepare('DELETE FROM utilisateur WHERE id = ? AND est_admin = 0');
            $stmtDel->execute([$id]);
            
            // Tenter de supprimer de la table "eleve" s'il y est
            try {
                $stmtEleve = $pdo->prepare('DELETE FROM eleve WHERE Nom = ?');
                $stmtEleve->execute([$eleve['nom']]);
            } catch (Exception $e) {
                // Ignore fallback failure
            }
        }
    } catch (PDOException $e) {
        // Erreur de suppression (peut-être des clés étrangères)
    }
}

header('Location: alleleves.php');
exit;
