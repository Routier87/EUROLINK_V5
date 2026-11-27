<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_permission('members.manage');

define('EUROLINK_ADMIN', true);

$users = storage_read('users.json', []);
$roles = storage_read('roles.json', []);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $userId = (string)($_POST['user_id'] ?? '');
    $action = (string)($_POST['action'] ?? '');

    $targetIndex = null;

    foreach ($users as $index => $user) {
        if ((string)($user['id'] ?? '') === $userId) {
            $targetIndex = $index;
            break;
        }
    }

    if ($targetIndex === null) {
        $error = 'Membre introuvable.';
    } else {
        $target = $users[$targetIndex];
        $actor = current_user();

        $targetIsFounder = ($target['role_id'] ?? '') === 'founder';
        $actorIsFounder = is_founder();

        if ($targetIsFounder && !$actorIsFounder) {
            $error = 'Seul le Fondateur peut modifier ce compte.';
        } elseif ($action === 'update') {
            $newRole = (string)($_POST['role_id'] ?? 'member');
            $newStatus = (string)($_POST['status'] ?? 'active');

            $validRoleIds = array_column($roles, 'id');

            if (!in_array($newRole, $validRoleIds, true)) {
                $error = 'Rôle invalide.';
            } elseif (!in_array($newStatus, ['active', 'disabled'], true)) {
                $error = 'Statut invalide.';
            } elseif (!$actorIsFounder && $newRole === 'founder') {
                $error = 'Tu ne peux pas attribuer le rôle Fondateur.';
            } elseif (!$actorIsFounder && $newRole === 'admin' && !has_permission('roles.manage')) {
                $error = 'Tu ne peux pas attribuer ce rôle.';
            } else {
                if ($targetIsFounder && ($newRole !== 'founder' || $newStatus !== 'active')) {
                    $founderCount = count(array_filter(
                        $users,
                        fn($u) => ($u['role_id'] ?? '') === 'founder'
                            && ($u['status'] ?? 'active') === 'active'
                    ));

                    if ($founderCount <= 1) {
                        $error = 'Impossible de désactiver ou rétrograder le dernier Fondateur.';
                    }
                }

                if ($error === '') {
                    $users[$targetIndex]['role_id'] = $newRole;
                    $users[$targetIndex]['status'] = $newStatus;

                    if (storage_write('users.json', $users)) {
                        add_log('member_updated', 'Modification du membre ' . ($target['username'] ?? $userId));
                        $message = 'Les modifications ont été enregistrées.';
                    } else {
                        $error = 'Impossible d'enregistrer les modifications.';
                    }
                }
            }
        }
    }
}

$users = storage_read('users.json', []);
require __DIR__ . '/includes/admin_header.php';
?>

<h2>Gestion des membres</h2>

<?php if ($message): ?>
    <p class="success-message"><?= e($message) ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p class="error-message"><?= e($error) ?></p>
<?php endif; ?>

<div class="admin-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Pseudo</th>
                    <th>E-mail</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Vérification e-mail</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $member): ?>
                <tr>
                    <td><?= e($member['display_name'] ?? $member['username'] ?? 'Membre') ?></td>
                    <td><?= e($member['email'] ?? '') ?></td>
                    <td>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= e((string)($member['id'] ?? '')) ?>">

                            <select name="role_id">
                                <?php foreach ($roles as $role): ?>
                                    <?php
                                    $roleId = (string)($role['id'] ?? '');
                                    if (!$actorIsFounder = is_founder()) {
                                        if ($roleId === 'founder' || ($roleId === 'admin' && !has_permission('roles.manage'))) {
                                            continue;
                                        }
                                    }
                                    ?>
                                    <option value="<?= e($roleId) ?>"
                                        <?= (($member['role_id'] ?? 'member') === $roleId) ? 'selected' : '' ?>>
                                        <?= e($role['name'] ?? $roleId) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                    </td>
                    <td>
                            <select name="status">
                                <option value="active" <?= (($member['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Actif</option>
                                <option value="disabled" <?= (($member['status'] ?? '') === 'disabled') ? 'selected' : '' ?>>Désactivé</option>
                            </select>
                    </td>
                    <td><?= !empty($member['email_verified']) ? 'Vérifié' : 'En attente' ?></td>
                    <td>
                            <button type="submit" name="action" value="update">Enregistrer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
