<?php
declare(strict_types=1);


function getConnexion(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $hote = 'localhost';
    $base = 'gestion_factures';
    $user = 'root';
    $mdp  = '';

    try {
        $pdo = new PDO("mysql:host=$hote;dbname=$base;charset=utf8mb4", $user, $mdp, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Connexion à la base impossible : MySQL est-il démarré et la base gestion_factures importée ?');
    }
    return $pdo;
}
