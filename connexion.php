
<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if ($_SESSION['login_attempts'] >= 10) {
        $error = 'Trop de tentatives. Attends quelques minutes avant de réessayer.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $users = storage_read('users', []);
        $matched = null;

        foreach ($users as $user) {
            if (strtolower($user['email'] ?? '') === $email) {
                $matched = $user;
                break;
            }
        }

        if (
            $matched &&
            ($matched['status'] ?? '') === 'active' &&
            password_verify($password, $matched['password'])
        ) {
            if (empty($matched['email_verified'])) {
                $error = 'Tu dois confirmer ton adresse e-mail avant de te connecter.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $matched['id'];
                $_SESSION['login_attempts'] = 0;

                add_log('member.login', 'Connexion de ' . $email);

                redirect('index.php');
            }
        } else {
            $_SESSION['login_attempts']++;
            $error = 'Adresse e-mail ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion - EUROLINK V5</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="auth-container">
<div class="auth-card">
<h1>Connexion</h1>
<p>Connecte-toi à ton espace EUROLINK V5.</p>

<?php if ($error): ?>
<div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">
<?= csrf_field() ?>

<label>Adresse e-mail</label>
<input type="email" name="email" autocomplete="username" required>

<label>Mot de passe</label>
<input type="password" name="password" autocomplete="current-password" required>

<button type="submit">Se connecter</button>
</form>

<p><a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a></p>
<p><a href="inscription.php">Créer un compte</a></p>
<a href="index.php">Retour à l'accueil</a>
</div>
</main>
</body>
</html>
