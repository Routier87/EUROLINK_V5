
<?php
declare(strict_types=1);

/**
 * Stockage JSON EUROLINK V5
 */

function storage_path(string $name): string
{
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
        throw new InvalidArgumentException('Nom de fichier invalide.');
    }

    return DATA_PATH . '/' . $name . '.json';
}

function storage_read(string $name, array $default = []): array
{
    $path = storage_path($name);

    if (!is_file($path)) {
        return $default;
    }

    $handle = fopen($path, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Impossible de lire les données.');
    }

    try {
        if (!flock($handle, LOCK_SH)) {
            throw new RuntimeException('Impossible de verrouiller les données.');
        }

        $contents = stream_get_contents($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }

    if ($contents === false || $contents === '') {
        return $default;
    }

    $data = json_decode($contents, true);

    if (!is_array($data)) {
        throw new RuntimeException(
            'Fichier de données invalide : ' . $name
        );
    }

    return $data;
}

function storage_write(string $name, array $data): void
{
    if (!is_dir(DATA_PATH)) {
        if (!mkdir(DATA_PATH, 0750, true) && !is_dir(DATA_PATH)) {
            throw new RuntimeException('Impossible de créer data/.');
        }
    }

    $path = storage_path($name);
    $lockPath = $path . '.lock';

    $lock = fopen($lockPath, 'c');

    if ($lock === false) {
        throw new RuntimeException('Impossible de créer le verrou.');
    }

    try {
        if (!flock($lock, LOCK_EX)) {
            throw new RuntimeException('Impossible de verrouiller le fichier.');
        }

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );

        $temporary = $path . '.tmp';

        if (file_put_contents($temporary, $json, LOCK_EX) === false) {
            throw new RuntimeException('Écriture impossible.');
        }

        @chmod($temporary, 0640);

        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Impossible de finaliser la sauvegarde.');
        }

        @chmod($path, 0640);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
