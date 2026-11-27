
<?php
declare(strict_types=1);

function require_login(): array
{
    $user = current_user();

    if (!$user) {
        redirect('connexion.php');
    }

    return $user;
}

function is_founder(?array $user = null): bool
{
    $user ??= current_user();

    return $user !== null
        && ($user['role_id'] ?? '') === 'founder';
}

function has_permission(string $permission, ?array $user = null): bool
{
    $user ??= current_user();

    if (!$user) {
        return false;
    }

    if (is_founder($user)) {
        return true;
    }

    $roles = storage_read('roles', []);
    $roleId = $user['role_id'] ?? '';

    foreach ($roles as $role) {
        if (($role['id'] ?? '') === $roleId) {
            return in_array(
                $permission,
                $role['permissions'] ?? [],
                true
            );
        }
    }

    return false;
}

function require_permission(string $permission): void
{
    require_login();

    if (!has_permission($permission)) {
        http_response_code(403);
        exit('Tu ne disposes pas des autorisations nécessaires.');
    }
}

function require_founder(): void
{
    require_login();

    if (!is_founder()) {
        http_response_code(403);
        exit('Accès réservé au Fondateur.');
    }
}
