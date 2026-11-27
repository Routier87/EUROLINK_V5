
<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

$lockFile = DATA_PATH . '/fondateur_cree.lock';

if (file_exists($lockFile)) {
    exit("Le fichier de création a déjà été utilisé. Supprimez-le du serveur.\n");
}

$lock = @fopen($lockFile, 'x');

if ($lock === false) {
    exit("Création déjà effectuée ou impossible.\n");
}

try {
    $users = storage_read('users', []);

    foreach ($users as $user) {
        if (($user['role_id'] ?? '') === 'founder') {
            throw new RuntimeException(
                "Un compte Fondateur existe déjà."
            );
        }
    }

    $email = 'fondateur@eurolink.valgharie.haloweb.fr';

    foreach ($users as $user) {
        if (strtolower($user['email'] ?? '') === strtolower($email)) {
            throw new RuntimeException(
                "Cette adresse e-mail existe déjà."
            );
        }
    }

    $users[] = [
        'id' => bin2hex(random_bytes(16)),
        'username' => 'routier87',
        'display_name' => 'Routier 87',
        'email' => $email,
        'password' => password_hash('878787', PASSWORD_DEFAULT),
        'role_id' => 'founder',
        'status' => 'active',
        'email_verified' => true,
        'created_at' => date('Y-m-d H:i:s')
    ];

    if (!storage_write('users', $users)) {
        throw new RuntimeException(
            "Impossible d'enregistrer le compte."
        );
    }

    echo "Compte Fondateur créé avec succès.\n";
    echo "Adresse : " . $email . "\n";
    echo "Mot de passe : 878787\n";
    echo "Connectez-vous et modifiez immédiatement votre mot de passe.\n";

} catch (Throwable $e) {
    @unlink($lockFile);
    http_response_code(400);
    echo "Erreur : " . $e->getMessage() . "\n";
} finally {
    fclose($lock);
}
