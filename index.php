<?php
declare(strict_types=1);


const SAISIE_TOUJOURS_OUVERTE = true;

define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/');

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once __DIR__ . '/BD/Connexion.php';
require_once __DIR__ . '/BD/Utilisateur.php';
require_once __DIR__ . '/Traitement/Auth.php';


const ROUTES = [
    'Auth'        => ['login', 'logout', 'bientot'],
    'Client'      => ['all', 'one', 'add', 'update', 'delete'],
    'Consommation'=> ['add', 'all'],
    'Facture'     => ['all', 'one', 'pdf', 'admin'],
    'Paiement'    => ['add'],
];

const PREFIXES = ['Consommation' => 'conso'];

function afficher404(): void
{
    http_response_code(404);
    afficherVue('404.php', ['titre' => 'Page introuvable']);
    exit;
}

// URL vide -> Auth/login
$url      = trim((string) ($_GET['url'] ?? ''), '/');
$segments = $url === '' ? ['Auth', 'login'] : explode('/', $url);

$entite = ucfirst(strtolower($segments[0]));
$action = strtolower($segments[1] ?? '');
$param  = $segments[2] ?? null;

if (!isset(ROUTES[$entite]) || !in_array($action, ROUTES[$entite], true) || count($segments) > 3) {
    afficher404();
}

$GLOBALS['routeCourante'] = ['entite' => $entite, 'action' => $action];


foreach (['BD', 'Traitement'] as $dossier) {
    foreach (glob(__DIR__ . "/$dossier/*.php") ?: [] as $fichier) {
        require_once $fichier;
    }
}

$fonction = (PREFIXES[$entite] ?? strtolower($entite)) . ucfirst($action);
if (!function_exists($fonction)) {
    afficher404();
}

$rf   = new ReflectionFunction($fonction);
$args = [];
if ($rf->getNumberOfParameters() >= 1) {
    $type = $rf->getParameters()[0]->getType();
    if ($type instanceof ReflectionNamedType && $type->getName() === 'int') {
        if ($param === null || !ctype_digit($param)) {
            afficher404();
        }
        $args[] = (int) $param;
    } elseif ($param !== null) {
        $args[] = preg_match('/^[a-z0-9_-]+$/i', $param) ? $param : '';
    }
} elseif ($param !== null) {
    afficher404();
}

$fonction(...$args);
