<?php
declare(strict_types=1);

// Requêtes de base de données pour la gestion des clients.

// Retourne le nombre total de clients.
function bdClientCount(): int
{
    return (int) getConnexion()->query('SELECT COUNT(*) FROM client')->fetchColumn();
}

// Retourne les informations des clients sans leurs mots de passe.
function bdClientAll(): array
{
    $sql = 'SELECT c.id, c.nom, c.prenom, c.adresse, c.telephone, c.email, c.date_creation,
                   (SELECT COUNT(*) FROM contrat ct WHERE ct.id_client = c.id) AS nb_contrats
            FROM client c
            ORDER BY c.nom, c.prenom';

    return getConnexion()->query($sql)->fetchAll();
}

// Recherche un client par son identifiant, ou retourne un tableau vide.
function bdClientOne(int $id): array
{
    $sql = 'SELECT id, nom, prenom, adresse, telephone, email, date_creation
            FROM client
            WHERE id = ?';
    $st = getConnexion()->prepare($sql);
    $st->execute([$id]);

    return $st->fetch() ?: [];
}

// Ajoute un client et retourne l'identifiant créé.
function bdClientInsert(
    string $nom,
    string $prenom,
    ?string $adresse,
    ?string $telephone,
    string $email,
    string $motDePasse
): int {
    $sql = 'INSERT INTO client
                (nom, prenom, adresse, telephone, email, mot_de_passe)
            VALUES (?, ?, ?, ?, ?, ?)';
    $st = getConnexion()->prepare($sql);
    // Le mot de passe est haché avant d'être enregistré.
    $st->execute([
        $nom,
        $prenom,
        $adresse,
        $telephone,
        $email,
        password_hash($motDePasse, PASSWORD_DEFAULT),
    ]);

    return (int) getConnexion()->lastInsertId();
}

// Modifie les informations du client sans toucher à son mot de passe.
function bdClientUpdate(
    int $id,
    string $nom,
    string $prenom,
    ?string $adresse,
    ?string $telephone,
    string $email
): int {
    $sql = 'UPDATE client
            SET nom = ?, prenom = ?, adresse = ?, telephone = ?, email = ?
            WHERE id = ?';
    $st = getConnexion()->prepare($sql);
    $st->execute([
        $nom,
        $prenom,
        $adresse,
        $telephone,
        $email,
        $id,
    ]);

    return $st->rowCount();
}

// Supprime un client et retourne le nombre de lignes supprimées.
function bdClientDelete(int $id): int
{
    $st = getConnexion()->prepare('DELETE FROM client WHERE id = ?');
    $st->execute([$id]);

    return $st->rowCount();
}
