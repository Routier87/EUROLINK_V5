
<?php
declare(strict_types=1);

function site_setting(string $key, mixed $default = null): mixed
{
    $settings = storage_read('settings', []);
    return $settings[$key] ?? $default;
}

function save_site_setting(string $key, mixed $value): void
{
    $settings = storage_read('settings', []);
    $settings[$key] = $value;
    storage_write('settings', $settings);
}

function current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? null;

    if (!$id) {
        return null;
    }

    $users = storage_read('users', []);

    foreach ($users as $user) {
        if (($user['id'] ?? '') === $id) {
            if (($user['status'] ?? 'inactive') !== 'active') {
                return null;
            }

            return $user;
        }
    }

    return null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_role_name(?array $user = null): string
{
    $user ??= current_user();

    if (!$user) {
        return 'Visiteur';
    }

    $roles = storage_read('roles', []);
    $roleId = $user['role_id'] ?? '';

    foreach ($roles as $role) {
        if (($role['id'] ?? '') === $roleId) {
            return (string) ($role['name'] ?? 'Membre');
        }
    }

    return 'Membre';
}

function format_date(?string $date): string
{
    if (!$date) {
        return 'Non renseigné';
    }

    $timestamp = strtotime($date);

    return $timestamp
        ? date('d/m/Y à H:i', $timestamp)
        : 'Non renseigné';
}

function add_log(string $action, string $details = ''): void
{
    $logs = storage_read('logs', []);

    array_unshift($logs, [
        'id' => bin2hex(random_bytes(8)),
        'user_id' => $_SESSION['user_id'] ?? null,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'created_at' => date('c')
    ]);

    storage_write('logs', array_slice($logs, 0, 2000));
}
