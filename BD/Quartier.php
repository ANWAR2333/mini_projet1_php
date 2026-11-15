<?php
declare(strict_types=1);
// Requêtes de base de données pour les quartiers.

/** Tous les quartiers avec leur type d'activité et le nombre de contrats rattachés. */
function bdQuartierAll(): array
{
    return getConnexion()->query(
        'SELECT q.id, q.nom, q.id_type_activite, t.nom AS type_activite,
                (SELECT COUNT(*) FROM contrat c WHERE c.id_quartier = q.id) AS nb_contrats
         FROM quartier q
         JOIN type_activite t ON t.id = q.id_type_activite
         ORDER BY q.nom'
    )->fetchAll();
}

function bdQuartierCount(): int
{
    return (int) getConnexion()->query('SELECT COUNT(*) FROM quartier')->fetchColumn();
}

/** Un quartier, ou [] s'il n'existe pas. */
function bdQuartierOne(int $id): array
{
    $st = getConnexion()->prepare('SELECT id, nom, id_type_activite FROM quartier WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: [];
}

function bdQuartierInsert(string $nom, int $idTypeActivite): int
{
    $pdo = getConnexion();
    $st  = $pdo->prepare('INSERT INTO quartier (nom, id_type_activite) VALUES (?, ?)');
    $st->execute([$nom, $idTypeActivite]);
    return (int) $pdo->lastInsertId();
}

function bdQuartierUpdate(int $id, string $nom, int $idTypeActivite): int
{
    $st = getConnexion()->prepare('UPDATE quartier SET nom = ?, id_type_activite = ? WHERE id = ?');
    $st->execute([$nom, $idTypeActivite, $id]);
    return $st->rowCount();
}

/** Échoue (PDOException 23000) si des contrats utilisent ce quartier. */
function bdQuartierDelete(int $id): int
{
    $st = getConnexion()->prepare('DELETE FROM quartier WHERE id = ?');
    $st->execute([$id]);
    return $st->rowCount();
}
