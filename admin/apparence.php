<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_permission('settings.manage');

define('EUROLINK_ADMIN', true);

$uploadDir = UPLOAD_PATH . '/site';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
$error = '';

function save_site_image(string $field, string $settingKey, string $uploadDir): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erreur pendant l’envoi du fichier.');
    }

    if ($file['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('Chaque image doit faire moins de 8 Mo.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format refusé. Utilise JPG, PNG ou WebP.');
    }

    $filename = bin2hex(random_bytes(20)) . '.' . $allowed[$mime];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Impossible de déplacer le fichier envoyé.');
    }

    $relativePath = 'uploads/site/' . $filename;

    $settings = storage_read('settings.json', []);
    $oldPath = (string)($settings[$settingKey] ?? '');

    $settings[$settingKey] = $relativePath;

    if (!storage_write('settings.json', $settings)) {
        @unlink($destination);
        throw new RuntimeException('Impossible d’enregistrer le nouveau réglage.');
    }

    if (str_starts_with($oldPath, 'uploads/site/')) {
        $oldFile = dirname(__DIR__) . '/' . $oldPath;
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    return $relativePath;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    try {
        save_site_image('logo', 'site_logo', $uploadDir);
        save_site_image('background', 'site_background', $uploadDir);
        $message = 'Apparence mise à jour.';
        add_log('appearance_updated', 'Logo ou arrière-plan modifié');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$settings = storage_read('settings.json', []);

require __DIR__ . '/includes/admin_header.php';
?>

<h2>Apparence du site</h2>

<?php if ($message): ?><p class="success-message"><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error-message"><?= e($error) ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-card">
    <?= csrf_field() ?>

    <h3>Logo du site</h3>
    <?php if (!empty($settings['site_logo'])): ?>
        <p><img src="../<?= e($settings['site_logo']) ?>" alt="Logo actuel" style="max-width:180px;max-height:100px"></p>
    <?php endif; ?>
    <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp">

    <hr>

    <h3>Image d'arrière-plan</h3>
    <?php if (!empty($settings['site_background'])): ?>
        <p><img src="../<?= e($settings['site_background']) ?>" alt="Arrière-plan actuel" style="max-width:260px;max-height:140px"></p>
    <?php endif; ?>
    <input type="file" name="background" accept=".jpg,.jpeg,.png,.webp">

    <p><button type="submit">Enregistrer l'apparence</button></p>
</form>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
