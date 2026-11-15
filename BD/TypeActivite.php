<?php
declare(strict_types=1);
/**
 * BD/TypeActivite.php — lecture seule, créé par Souraya pour que Quartier et Parametre fonctionnent.
 * Anwar : AJOUTE ici le reste du CRUD (bdTypeActiviteInsert, Update, Delete, tranches...).
 * Ne redéclare pas ces deux fonctions, elles existent déjà.
 */

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
