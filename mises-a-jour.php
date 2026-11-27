
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$content = storage_read('content', []);
$updates = array_reverse($content['updates'] ?? []);

require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <h1>Mises à jour</h1>
    <p>Consulte les évolutions et améliorations de EUROLINK V5.</p>

    <div class="cards">
        <?php if (!$updates): ?>
            <article class="card">
                <h2>Aucune mise à jour publiée</h2>
                <p>Les informations seront ajoutées ici prochainement.</p>
            </article>
        <?php else: ?>
            <?php foreach ($updates as $update): ?>
                <article class="card">
                    <h2><?= e($update['title'] ?? '') ?></h2>
                    <p><?= nl2br(e($update['description'] ?? '')) ?></p>
                    <?php if (!empty($update['version'])): ?>
                        <p>Version : <?= e($update['version']) ?></p>
                    <?php endif; ?>
                    <small><?= e($update['created_at'] ?? '') ?></small>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
