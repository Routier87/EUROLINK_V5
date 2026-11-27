
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$app = storage_read('applications', []);
$status = $app['status'] ?? 'development';
$available = $status === 'available'
    && !empty($app['filename'])
    && is_file(UPLOAD_PATH . '/applications/' . basename($app['filename']));

require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <h1>Application EUROLINK</h1>

    <div class="admin-card">
        <h2>Notre application</h2>

        <?php if ($available): ?>
            <div class="alert success">Application disponible</div>
            <p>Version : <?= e($app['version'] ?? 'Non renseignée') ?></p>
            <p><?= nl2br(e($app['description'] ?? '')) ?></p>
            <a class="button" href="telecharger-application.php">Télécharger l'application</a>
        <?php else: ?>
            <p>L'application n'est pas actuellement disponible. Elle est en cours de développement.</p>
            <p>Nous travaillons actuellement sur son développement.</p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
