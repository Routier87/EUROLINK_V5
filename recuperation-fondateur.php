<?php
declare(strict_types=1);

/*
 * OUTIL TEMPORAIRE DE RÉCUPÉRATION DU COMPTE FONDATEUR
 *
 * À supprimer immédiatement après utilisation.
 * Remplacer la clé ci-dessous avant l'envoi sur l'hébergement.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$recoveryKey = 'REMPLACE-MOI-PAR-UNE-CLE-ALEATOIRE-TRES-LONGUE-ET-UNIQUE';

$usedFile = DATA_PATH . '/founder-recovery-used.lock';

if (is_file($usedFile)) {
    http_response_code(410);
    exit('Cet outil a déjà été utilisé.');
}

if ($recoveryKey === 'REMPLACE-MOI-PAR-UNE-CLE-ALEATOIRE-TRES-LONGUE-ET-UNIQUE') {
    http_response_code(503);
    exit('Configure la clé de sécurité dans le fichier avant utilisation.');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $submittedKey = (string)($_POST['recovery_key'] ?? '');
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirmation = (string)($_POST['password_confirmation'] ?? '');

    if (!hash_equals($recoveryKey, $submittedKey)) {
        $error = 'Clé de récupération incorrecte.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (strlen($password) < 12) {
        $error = 'Le mot de passe doit contenir au moins 12 caractères.';
    } elseif ($password !== $confirmation) {
        $error = 'Les deux mots de passe ne correspondent pas.';
    } else {
        $users = storage_read('users.json', []);

        $founderIndex = null;
        $emailAlreadyUsed = false;

        foreach ($users as $index => $user) {
            if (
                mb_strtolower((string)($user['email'] ?? '')) === $email
                && ($user['role_id'] ?? '') !== 'founder'
            ) {
                $emailAlreadyUsed = true;
                break;
            }

            if (($user['role_id'] ?? '') === 'founder') {
                $founderIndex = $index;
            }
        }

        if ($emailAlreadyUsed) {
            $error = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
        } elseif ($founderIndex === null) {
            $error = 'Aucun compte Fondateur existant n’a été trouvé.';
        } else {
            $users[$founderIndex]['email'] = $email;
            $users[$founderIndex]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $users[$founderIndex]['email_verified'] = true;
            $users[$founderIndex]['status'] = 'active';

            if (storage_write('users.json', $users)) {
                $marker = "Récupération du compte Fondateur effectuée le "
                    . date('Y-m-d H:i:s') . PHP_EOL;

                file_put_contents($usedFile, $marker, LOCK_EX);
                @chmod($usedFile, 0600);

                $success = true;

                // Tentative de suppression automatique du fichier.
                @unlink(__FILE__);
            } else {
                $error = 'Impossible d’enregistrer les modifications.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Récupération Fondateur - EUROLINK V5</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="container page-content">
    <section class="admin-card">
        <h1>Récupération du compte Fondateur</h1>

        <?php if ($success): ?>
            <h2>Récupération effectuée</h2>
            <p>Ton adresse e-mail et ton mot de passe ont été modifiés.</p>
            <p>Connecte-toi maintenant depuis la page de connexion.</p>
            <p><strong>Vérifie que le fichier recuperation-fondateur.php a bien été supprimé de ton hébergement.</strong></p>
        <?php else: ?>
            <p>Cette page est un outil temporaire de sécurité. Ne la laisse pas sur le site après utilisation.</p>

            <?php if ($error !== ''): ?>
                <p class="error-message"><?= e($error) ?></p>
            <?php endif; ?>

            <form method="post">
                <?= csrf_field() ?>

                <label>Clé de récupération</label>
                <input type="password" name="recovery_key" autocomplete="off" required>

                <label>Nouvelle adresse e-mail du Fondateur</label>
                <input type="email" name="email" autocomplete="email" required>

                <label>Nouveau mot de passe</label>
                <input type="password" name="password" minlength="12" autocomplete="new-password" required>

                <label>Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required>

                <button type="submit">Modifier le compte Fondateur</button>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
