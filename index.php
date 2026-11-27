
<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$settings = storage_read('settings', []);
$content = storage_read('content', []);
$user = current_user();

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-content">
        <span class="eyebrow">BIENVENUE SUR</span>
        <h1><?= e($settings['site_name'] ?? SITE_NAME) ?></h1>
        <p><?= e($settings['site_description'] ?? 'Bienvenue sur EUROLINK V5') ?></p>

        <?php if (!$user): ?>
            <div class="hero-actions">
                <a class="button" href="inscription.php">Créer un compte</a>
                <a class="button secondary" href="connexion.php">Connexion</a>
            </div>
        <?php else: ?>
            <p>Bienvenue, <?= e($user['display_name']) ?> !</p>
            <a class="button" href="profil.php">Mon profil</a>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <h2>Découvrir EUROLINK</h2>
    <p>Retrouve les informations, les nouveautés et les fonctionnalités de notre plateforme.</p>

    <div class="cards">
        <article class="card">
            <h3>Fonctionnalités</h3>
            <p>Découvre les différentes fonctionnalités du site.</p>
            <a href="fonctionnalites.php">En savoir plus</a>
        </article>

        <article class="card">
            <h3>Application</h3>
            <p>Retrouve les informations concernant notre application.</p>
            <a href="application.php">Découvrir</a>
        </article>

        <article class="card">
            <h3>Nouveautés</h3>
            <p>Consulte les dernières actualités du projet.</p>
            <a href="nouveautes.php">Voir les nouveautés</a>
        </article>
    </div>
</section>

<section class="section">
    <h2>Dernières actualités</h2>

    <div class="cards">
    <?php
    $news = array_reverse($content['news'] ?? []);
    $news = array_slice($news, 0, 3);
    ?>

    <?php if (!$news): ?>
        <article class="card">
            <h3>Aucune actualité pour le moment</h3>
            <p>Les prochaines actualités seront publiées ici.</p>
        </article>
    <?php else: ?>
        <?php foreach ($news as $item): ?>
            <article class="card">
                <h3><?= e($item['title'] ?? '') ?></h3>
                <p><?= nl2br(e($item['description'] ?? '')) ?></p>
                <small><?= e($item['created_at'] ?? '') ?></small>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
