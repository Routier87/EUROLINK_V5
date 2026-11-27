
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/storage.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0750, true);
}

$lockFile = DATA_PATH . '/installed.lock';

if (is_file($lockFile)) {
    exit('EUROLINK V5 est déjà installé. Pour des raisons de sécurité, supprime le dossier install du serveur.');
}

if (empty($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['install_token'], $token)) {
        $error = 'Session invalide. Recharge la page.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';
        $displayName = trim($_POST['display_name'] ?? '');

        if (!$email) {
            $error = 'Adresse e-mail invalide.';
        } elseif (strlen($password) < 12) {
            $error = 'Le mot de passe doit contenir au moins 12 caractères.';
        } elseif ($password !== $confirmation) {
            $error = 'Les mots de passe ne correspondent pas.';
        } elseif ($displayName === '') {
            $error = 'Le nom du Fondateur est obligatoire.';
        } else {
            $founder = [
                'id' => bin2hex(random_bytes(16)),
                'username' => 'routier87',
                'display_name' => $displayName,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role_id' => 'founder',
                'status' => 'active',
                'email_verified' => true,
                'must_change_password' => false,
                'created_at' => date('c')
            ];

            try {
                storage_write('users', [$founder]);

                storage_write('roles', [
                    [
                        'id' => 'founder',
                        'name' => 'Fondateur',
                        'permissions' => ['*']
                    ],
                    [
                        'id' => 'admin',
                        'name' => 'Administrateur',
                        'permissions' => [
                            'members.view',
                            'members.manage',
                            'content.manage',
                            'settings.manage',
                            'applications.manage',
                            'logs.view'
                        ]
                    ],
                    [
                        'id' => 'moderator',
                        'name' => 'Modérateur',
                        'permissions' => ['members.view', 'content.manage']
                    ],
                    [
                        'id' => 'member',
                        'name' => 'Membre',
                        'permissions' => []
                    ]
                ]);

                storage_write('permissions', [
                    'members.view',
                    'members.manage',
                    'roles.manage',
                    'content.manage',
                    'settings.manage',
                    'applications.manage',
                    'logs.view',
                    'backups.manage'
                ]);

                storage_write('settings', [
                    'site_name' => 'EUROLINK V5',
                    'site_description' => 'Bienvenue sur EUROLINK V5',
                    'site_logo' => '',
                    'site_background' => '',
                    'discord_url' => '',
                    'primary_color' => '#1769ff',
                    'secondary_color' => '#081426',
                    'registration_enabled' => true,
                    'maintenance_mode' => false
                ]);

                storage_write('content', [
                    'features' => [],
                    'news' => [],
                    'updates' => []
                ]);

                storage_write('applications', [
                    'status' => 'development',
                    'version' => '1.0.0',
                    'description' => "L'application n'est pas actuellement disponible. Elle est en cours de développement.",
                    'filename' => '',
                    'uploaded_at' => null
                ]);

                storage_write('email_tokens', []);
                storage_write('logs', []);

                $handle = fopen($lockFile, 'x');

                if ($handle === false) {
                    throw new RuntimeException('Impossible de verrouiller l’installation.');
                }

                fwrite($handle, date('c'));
                fclose($handle);
                chmod($lockFile, 0640);

                $success = true;
                unset($_SESSION['install_token']);
            } catch (Throwable $exception) {
                $error = 'Installation interrompue : ' . $exception->getMessage();
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
<title>Installation - EUROLINK V5</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#07111f;color:#fff;font-family:Arial,sans-serif;display:grid;place-items:center;min-height:100vh;padding:20px}
.panel{background:#101e31;border:1px solid #263c58;border-radius:16px;padding:30px;width:100%;max-width:480px}
h1{color:#4c91ff}
input{width:100%;padding:13px;margin:8px 0 16px;border-radius:8px;border:1px solid #38506d;background:#081426;color:white}
button{width:100%;padding:14px;border:0;border-radius:8px;background:#1769ff;color:white;font-weight:bold;cursor:pointer}
.error{background:#612d35;padding:12px;border-radius:8px}
.success{background:#174d3b;padding:12px;border-radius:8px}
label{font-size:14px;color:#cbd8e8}
</style>
</head>
<body>
<div class="panel">
<h1>EUROLINK V5</h1>
<h2>Installation du site</h2>

<?php if ($success): ?>
<div class="success">
<strong>Installation terminée !</strong>
<p>Ton compte Fondateur est créé. Supprime maintenant le dossier install du serveur, puis connecte-toi.</p>
<a href="../connexion.php">Accéder à la connexion</a>
</div>
<?php else: ?>

<?php if ($error): ?>
<p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post">
<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['install_token'], ENT_QUOTES, 'UTF-8') ?>">

<label>Nom affiché du Fondateur</label>
<input name="display_name" value="Routier 87" required>

<label>Adresse e-mail</label>
<input type="email" name="email" required>

<label>Mot de passe (12 caractères minimum)</label>
<input type="password" name="password" minlength="12" required>

<label>Confirmation du mot de passe</label>
<input type="password" name="confirmation" minlength="12" required>

<button type="submit">Installer EUROLINK V5</button>
</form>
<?php endif; ?>
</div>
</body>
</html>
