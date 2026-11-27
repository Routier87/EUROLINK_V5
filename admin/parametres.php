<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_permission('settings.manage');

define('EUROLINK_ADMIN', true);

$message = '';
$error = '';

$settings = storage_read('settings.json', []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $siteName = trim((string)($_POST['site_name'] ?? ''));
    $description = trim((string)($_POST['site_description'] ?? ''));
    $discord = trim((string)($_POST['discord_url'] ?? ''));
    $registration = isset($_POST['registration_enabled']);
    $maintenance = isset($_POST['maintenance_mode']);

    if ($siteName === '' || mb_strlen($siteName) > 80) {
        $error = 'Le nom du site est obligatoire et doit faire moins de 80 caractères.';
    } elseif ($discord !== '' && (!filter_var($discord, FILTER_VALIDATE_URL) || !str_starts_with($discord, 'https://'))) {
        $error = 'Le lien Discord doit être une adresse HTTPS valide.';
    } elseif ($maintenance && !is_founder()) {
        $error = 'Seul le Fondateur peut activer le mode maintenance.';
    } else {
        $settings['site_name'] = $siteName;
        $settings['site_description'] = $description;
        $settings['discord_url'] = $discord;
        $settings['registration_enabled'] = $registration;

        if (is_founder()) {
            $settings['maintenance_mode'] = $maintenance;
        }

        if (storage_write('settings.json', $settings)) {
            add_log('settings_updated', 'Paramètres généraux modifiés');
            $message = 'Paramètres enregistrés.';
        } else {
            $error = 'Erreur lors de l’enregistrement.';
        }
    }
}

$settings = storage_read('settings.json', []);

require __DIR__ . '/includes/admin_header.php';
?>

<h2>Paramètres généraux</h2>

<?php if ($message): ?><p class="success-message"><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error-message"><?= e($error) ?></p><?php endif; ?>

<form method="post" class="admin-card">
    <?= csrf_field() ?>

    <label>Nom du site</label>
    <input type="text" name="site_name" maxlength="80"
           value="<?= e($settings['site_name'] ?? SITE_NAME) ?>" required>

    <label>Description du site</label>
    <textarea name="site_description" rows="3"><?= e($settings['site_description'] ?? '') ?></textarea>

    <label>Lien d'invitation Discord</label>
    <input type="url" name="discord_url"
           value="<?= e($settings['discord_url'] ?? '') ?>"
           placeholder="https://discord.gg/...">

    <label class="checkbox-line">
        <input type="checkbox" name="registration_enabled" value="1"
            <?= !empty($settings['registration_enabled']) ? 'checked' : '' ?>>
        Autoriser les nouvelles inscriptions
    </label>

    <?php if (is_founder()): ?>
        <label class="checkbox-line">
            <input type="checkbox" name="maintenance_mode" value="1"
                <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?>>
            Activer le mode maintenance
        </label>
    <?php endif; ?>

    <button type="submit">Enregistrer les paramètres</button>
</form>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
