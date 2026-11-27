
<?php
$headerUser = current_user();
$headerSettings = storage_read('settings', []);
$discordUrl = $headerSettings['discord_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($headerSettings['site_name'] ?? SITE_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <a class="brand" href="index.php">
        <?php if (!empty($headerSettings['site_logo'])): ?>
            <img src="<?= e($headerSettings['site_logo']) ?>" alt="Logo EUROLINK">
        <?php else: ?>
            <span class="brand-mark">E</span>
        <?php endif; ?>
        <span><?= e($headerSettings['site_name'] ?? SITE_NAME) ?></span>
    </a>

    <nav class="main-nav">
        <a href="index.php">Accueil</a>
        <a href="fonctionnalites.php">Fonctionnalités</a>
        <a href="application.php">Application</a>
        <a href="nouveautes.php">Nouveautés</a>

        <?php if ($headerUser): ?>
            <a href="profil.php">Mon profil</a>

            <?php if (has_permission('members.view')): ?>
                <a href="admin/index.php">Administration</a>
            <?php endif; ?>

            <form action="deconnexion.php" method="post" class="nav-logout">
                <?= csrf_field() ?>
                <button type="submit" class="nav-button">Déconnexion</button>
            </form>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
            <a href="inscription.php" class="nav-register">Inscription</a>
        <?php endif; ?>

        <?php if ($discordUrl && filter_var($discordUrl, FILTER_VALIDATE_URL)): ?>
            <a href="<?= e($discordUrl) ?>" target="_blank" rel="noopener noreferrer" class="discord-button">
                Rejoindre Discord
            </a>
        <?php endif; ?>
    </nav>
</header>

<main class="page-content">
