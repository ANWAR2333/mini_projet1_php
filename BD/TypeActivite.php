<?php
declare(strict_types=1);
/** Tous les types d'activité, avec le nombre de quartiers qui l'utilisent. */
function bdTypeActiviteAll(): array
{
    return getConnexion()->query(
        'SELECT t.*, (SELECT COUNT(*) FROM quartier q WHERE q.id_type_activite = t.id) AS nb_quartiers
         FROM type_activite t ORDER BY t.id'
    )->fetchAll();
}

/** Un type d'activité, ou [] s'il n'existe pas. */
function bdTypeActiviteOne(int $id): array
{
    $st = getConnexion()->prepare('SELECT * FROM type_activite WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: [];
}

function bdTypeActiviteInsert(string $nom, float $seuilEau, float $seuilElec): int
{
    $pdo = getConnexion();
    $st = $pdo->prepare(
        'INSERT INTO type_activite (nom, seuil_eau, seuil_elec) VALUES (?, ?, ?)'
    );
    $st->execute([$nom, $seuilEau, $seuilElec]);
    return (int) $pdo->lastInsertId();
}

function bdTypeActiviteUpdate(int $id, string $nom, float $seuilEau, float $seuilElec): int
{
    $st = getConnexion()->prepare(
        'UPDATE type_activite SET nom = ?, seuil_eau = ?, seuil_elec = ? WHERE id = ?'
    );
    $st->execute([$nom, $seuilEau, $seuilElec, $id]);
    return $st->rowCount();
}

/** Les quartiers qui utilisent le type, avec leur nombre de contrats. */
function bdTypeActiviteQuartiers(int $id): array
{
    $st = getConnexion()->prepare(
        'SELECT q.id, q.nom, COUNT(c.id) AS nb_contrats
         FROM quartier q
         LEFT JOIN contrat c ON c.id_quartier = q.id
         WHERE q.id_type_activite = ?
         GROUP BY q.id, q.nom
         ORDER BY q.nom'
    );
    $st->execute([$id]);
    return $st->fetchAll();
}

/** Barèmes associés à ce type, triés dans l'ordre de facturation. */
function bdTypeActiviteTranches(int $id): array
{
    $st = getConnexion()->prepare(
        'SELECT service, borne_max, prix_unitaire, ordre
         FROM tranche_tarif
         WHERE id_type_activite = ?
         ORDER BY service, ordre'
    );
    $st->execute([$id]);
    return $st->fetchAll();
}

/** La suppression échoue avec une contrainte FK si des quartiers utilisent ce type. */
function bdTypeActiviteDelete(int $id): int
{
    $st = getConnexion()->prepare('DELETE FROM type_activite WHERE id = ?');
    $st->execute([$id]);
    return $st->rowCount();
}
