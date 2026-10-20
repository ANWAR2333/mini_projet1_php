<?php
declare(strict_types=1);

function bdUtilisateurSelectByEmail(string $email): ?array
{
    $pdo = getConnexion();

    $st = $pdo->prepare('SELECT id, nom, email, mot_de_passe FROM fournisseur WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    if ($ligne = $st->fetch()) {
        return [
            'id'           => (int) $ligne['id'],
            'role'         => 'fournisseur',
            'nom'          => $ligne['nom'],
            'email'        => $ligne['email'],
            'mot_de_passe' => $ligne['mot_de_passe'],
        ];
    }

    $st = $pdo->prepare('SELECT id, nom, prenom, email, mot_de_passe FROM client WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    if ($ligne = $st->fetch()) {
        return [
            'id'           => (int) $ligne['id'],
            'role'         => 'client',
            'nom'          => $ligne['prenom'] . ' ' . $ligne['nom'],
            'email'        => $ligne['email'],
            'mot_de_passe' => $ligne['mot_de_passe'],
        ];
    }

    return null;
}
