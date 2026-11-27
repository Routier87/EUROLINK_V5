<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_permission('application.manage');

define('EUROLINK_ADMIN', true);

$uploadDir = UPLOAD_PATH . '/applications';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$app = storage_read('applications.json', []);
if (!is_array($app)) {
    $app = [];
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $version = trim((string)($_POST['version'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $status = (string)($_POST['status'] ?? 'development');

    if ($version === '' || mb_strlen($version) > 30) {
        $error = 'Indique une version valide.';
    } elseif (!in_array($status, ['development', 'available'], true)) {
        $error = 'Statut invalide.';
    } else {
        $newFilename = null;

        if (isset($_FILES['package']) && $_FILES['package']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['package'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Erreur pendant le transfert du fichier.';
            } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
                $error = 'Le fichier dépasse la taille maximale autorisée.';
            } else {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['zip', 'exe', 'msi'];

                if (!in_array($extension, $allowedExtensions, true)) {
                    $error = 'Formats autorisés : ZIP, EXE et MSI.';
                } else {
                    $newFilename = 'eurolink-' . bin2hex(random_bytes(16)) . '.' . $extension;
                    $destination = $uploadDir . '/' . $newFilename;

                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        $error = 'Impossible d’enregistrer le fichier.';
                    }
                }
            }
        }

        if ($error === '') {
            $oldFilename = $app['filename'] ?? '';

            $app['version'] = $version;
            $app['description'] = $description;
            $app['status'] = $status;

            if ($newFilename !== null) {
                $app['filename'] = $newFilename;

                if ($oldFilename !== '' && basename($oldFilename) === $oldFilename) {
                    $oldFile = $uploadDir . '/' . $oldFilename;
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
            }

            if (storage_write('applications.json', $app)) {
                add_log('application_updated', 'Version de l’application mise à jour');
                $message = 'Application mise à jour.';
            } else {
                $error = 'Impossible d’enregistrer les informations.';
            }
        }
    }
}

$app = storage_read('applications.json', []);

require __DIR__ . '/includes/admin_header.php';
?>

<h2>Gestion de l'application</h2>

<?php if ($message): ?><p class="success-message"><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error-message"><?= e($error) ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-card">
    <?= csrf_field() ?>

    <label>Numéro de version</label>
    <input type="text" name="version" maxlength="30"
           value="<?= e($app['version'] ?? '1.0.0') ?>" required>

    <label>Description</label>
    <textarea name="description" rows="5"><?= e($app['description'] ?? '') ?></textarea>

    <label>État de l'application</label>
    <select name="status">
        <option value="development" <?= (($app['status'] ?? 'development') === 'development') ? 'selected' : '' ?>>
            En développement
        </option>
        <option value="available" <?= (($app['status'] ?? '') === 'available') ? 'selected' : '' ?>>
            Disponible
        </option>
    </select>

    <label>Fichier à déposer (ZIP, EXE ou MSI)</label>
    <input type="file" name="package" accept=".zip,.exe,.msi">

    <p class="muted">Taille maximale configurée : <?= (int)(MAX_UPLOAD_SIZE / 1024 / 1024) ?> Mo.</p>

    <?php if (!empty($app['filename'])): ?>
        <p>Fichier actuellement enregistré : <?= e($app['filename']) ?></p>
    <?php endif; ?>

    <button type="submit">Enregistrer l'application</button>
</form>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
