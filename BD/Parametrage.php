<?php
declare(strict_types=1);
// Requêtes de la page Paramètres (TVA et seuils d'anomalie par type d'activité).

/** Modifie le taux de TVA (0.18 = 18 %). */
function bdParametreSetTva(float $taux): void
{
    $st = getConnexion()->prepare('UPDATE parametre SET taux_tva = ? WHERE id = 1');
    $st->execute([$taux]);
}

/** Modifie les seuils d'anomalie d'un type d'activité. */
function bdTypeActiviteSetSeuils(int $id, float $seuilEau, float $seuilElec): void
{
    $st = getConnexion()->prepare('UPDATE type_activite SET seuil_eau = ?, seuil_elec = ? WHERE id = ?');
    $st->execute([$seuilEau, $seuilElec, $id]);
}
