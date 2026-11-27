
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$app = storage_read('applications', []);

if (($app['status'] ?? '') !== 'available' || empty($app['filename'])) {
    http_response_code(404);
    exit('Aucune application disponible.');
}

$filename = basename($app['filename']);
$path = UPLOAD_PATH . '/applications/' . $filename;
$realPath = realpath($path);
$realDirectory = realpath(UPLOAD_PATH . '/applications');

if (
    !$realPath ||
    !$realDirectory ||
    !str_starts_with($realPath, $realDirectory . DIRECTORY_SEPARATOR) ||
    !is_file($realPath)
) {
    http_response_code(404);
    exit('Fichier introuvable.');
}

$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$types = [
    'zip' => 'application/zip',
    'exe' => 'application/vnd.microsoft.portable-executable',
    'msi' => 'application/x-msi'
];

if (!isset($types[$extension])) {
    http_response_code(403);
    exit('Type de fichier interdit.');
}

header('Content-Type: ' . $types[$extension]);
header('Content-Length: ' . filesize($realPath));
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');

readfile($realPath);
exit;
