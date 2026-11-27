
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$content = storage_read('content', []);
$features = $content['features'] ?? [];

require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <h1>Les fonctionnalités</h1>
    <p>Découvre les fonctionnalités proposées par EUROLINK V5.</p>

    <div class="cards">
        <?php if (!$features): ?>
            <article class="card">
                <h2>Une plateforme en évolution</h2>
                <p>Les fonctionnalités seront présentées ici au fur et à mesure de leur publication.</p>
            </article>
        <?php else: ?>
            <?php foreach ($features as $feature): ?>
                <article class="card">
                    <h2><?= e($feature['title'] ?? '') ?></h2>
                    <p><?= nl2br(e($feature['description'] ?? '')) ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
