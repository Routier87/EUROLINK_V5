<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_permission('admin.access');

define('EUROLINK_ADMIN', true);

$users = storage_read('users.json', []);
$applications = storage_read('applications.json', []);
$logs = storage_read('logs.json', []);

$totalUsers = count($users);
$activeUsers = count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'active'));
$pendingUsers = count(array_filter($users, fn($u) => empty($u['email_verified'])));
$totalLogs = count($logs);

require __DIR__ . '/includes/admin_header.php';
?>

<h2>Vue générale</h2>

<div class="admin-grid">
    <section class="admin-card">
        <h3>Membres inscrits</h3>
        <p class="admin-number"><?= $totalUsers ?></p>
    </section>

    <section class="admin-card">
        <h3>Membres actifs</h3>
        <p class="admin-number"><?= $activeUsers ?></p>
    </section>

    <section class="admin-card">
        <h3>E-mails à vérifier</h3>
        <p class="admin-number"><?= $pendingUsers ?></p>
    </section>

    <section class="admin-card">
        <h3>Événements enregistrés</h3>
        <p class="admin-number"><?= $totalLogs ?></p>
    </section>
</div>

<section class="admin-card">
    <h2>Gestion du site</h2>
    <p>Utilise les rubriques ci-dessus pour gérer les membres, les paramètres, l'apparence, les rôles et l'application.</p>
</section>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
