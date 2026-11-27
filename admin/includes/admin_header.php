<?php
if (!defined('EUROLINK_ADMIN')) {
    http_response_code(403);
    exit('Accès interdit.');
}

$user = current_user();
$siteName = site_setting('site_name', SITE_NAME);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration - <?= e($siteName) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="../index.php"><?= e($siteName) ?></a>
        <nav class="main-nav">
            <a href="../index.php">Voir le site</a>
            <a href="../profil.php">Mon profil</a>
            <a href="../deconnexion.php">Déconnexion</a>
        </nav>
    </div>
</header>

<main class="container page-content">
    <h1>Administration</h1>
    <p>
        Connecté en tant que
        <strong><?= e($user['display_name'] ?? $user['username'] ?? 'Administrateur') ?></strong>
    </p>

    <nav class="admin-nav">
        <a href="index.php">Tableau de bord</a>
        <a href="membres.php">Membres</a>
        <a href="parametres.php">Paramètres</a>
        <a href="apparence.php">Apparence</a>
        <a href="applications.php">Application</a>
        <a href="roles.php">Rôles</a>
        <a href="contenu.php">Contenu</a>
        <a href="sauvegardes.php">Sauvegardes</a>
        <a href="journaux.php">Journaux</a>
    </nav>
