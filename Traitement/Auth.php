<?php
declare(strict_types=1);


function verifierRole(string $role): void
{
    $actuel = $_SESSION['role'] ?? null;
    $autorise = ($role === 'connecte') ? ($actuel !== null) : ($actuel === $role);

    if (!$autorise) {
        rediriger(
            'Auth/login',
            $actuel === null ? 'Veuillez vous connecter.' : 'Accès refusé pour votre profil.',
            'erreur'
        );
    }
}

function rediriger(string $route, string $message = '', string $type = 'succes'): void
{
    if ($message !== '') {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
    header('Location: ' . BASE_URL . ltrim($route, '/'));
    exit;
}


function afficherVue(string $vue, array $donnees = []): void
{
    $fichier = __DIR__ . '/../IHM/' . $vue;
    if (!is_file($fichier)) {
        http_response_code(500);
        exit('Vue introuvable : ' . htmlspecialchars($vue, ENT_QUOTES, 'UTF-8'));
    }

    extract($donnees, EXTR_SKIP);
    $titre = $titre ?? 'eco house.';
    $role  = $_SESSION['role'] ?? null;

    require __DIR__ . '/../IHM/layout/header.php';

    if ($role === 'client' || $role === 'fournisseur') {
        require __DIR__ . '/../IHM/layout/sidebar_' . $role . '.php';
        echo '<main class="contenu">';
    } else {
        echo '<main class="plein">';
    }

    afficherBandeMessage();
    require $fichier;

    require __DIR__ . '/../IHM/layout/footer.php';
}

function afficherBandeMessage(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $classe = ($flash['type'] ?? '') === 'erreur' ? 'bande-erreur' : 'bande-succes';
    echo '<div class="bande ' . $classe . '">' . htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8') . '</div>';
}

function routeAccueil(string $role): string
{
    return $role === 'fournisseur' ? 'Client/all' : 'Consommation/add';
}


function authLogin(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['mot_de_passe'] ?? '');

        $utilisateur = $email !== '' ? bdUtilisateurSelectByEmail($email) : null;

        if ($utilisateur !== null && password_verify($password, $utilisateur['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['id']   = $utilisateur['id'];
            $_SESSION['role'] = $utilisateur['role'];
            $_SESSION['nom']  = $utilisateur['nom'];
            rediriger(routeAccueil($utilisateur['role']));
        }

        $_SESSION['flash'] = ['message' => 'Email ou mot de passe incorrect', 'type' => 'erreur'];
        afficherVue('auth/login.php', ['titre' => 'Connexion', 'email' => $email]);
        return;
    }

    afficherVue('auth/login.php', ['titre' => 'Connexion', 'email' => '']);
}

function authLogout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    rediriger('Auth/login');
}

function authBientot(string $page = ''): void
{
    verifierRole('connecte');

    $noms = [
        'reclamations'    => 'Réclamations',
        'notifications'   => 'Notifications',
        'tableau-de-bord' => 'Tableau de Bord',
        'anomalies'       => 'Anomalies',
    ];
    afficherVue('bientot.php', [
        'titre'  => 'Bientôt disponible',
        'module' => $noms[$page] ?? 'Cette page',
    ]);
}
