
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$content = storage_read('content', []);
$news = array_reverse($content['news'] ?? []);

require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <h1>Les nouveautés</h1>
    <p>Retrouve ici les dernières actualités du projet.</p>

    <div class="cards">
        <?php if (!$news): ?>
            <article class="card">
                <h2>Aucune nouveauté pour le moment</h2>
                <p>Les prochaines actualités seront publiées ici.</p>
            </article>
        <?php else: ?>
            <?php foreach ($news as $item): ?>
                <article class="card">
                    <h2><?= e($item['title'] ?? '') ?></h2>
                    <p><?= nl2br(e($item['description'] ?? '')) ?></p>
                    <small>Publié le <?= e($item['created_at'] ?? '') ?></small>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
