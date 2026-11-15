<?php
declare(strict_types=1);
/**
 * BD/Contrat.php — Souraya
 * Fonctions communes sur les contrats (utilisées par Anwar, Amal, Farah et Chaimaa).
 * Requêtes préparées uniquement : pas de HTML, pas de $_SESSION, pas de calcul métier.
 * Un contrat appartient à un client, est situé dans un quartier, et le quartier a un type d'activité
 * (c'est le type d'activité qui fixe le barème tarifaire et les seuils d'anomalie).
 */

// Requête de base : le contrat + son quartier + son type d'activité.
const BD_CONTRAT_SELECT =
    'SELECT c.*, q.nom AS quartier, t.id AS id_type_activite, t.nom AS type_activite,
            t.seuil_eau, t.seuil_elec
     FROM contrat c
     JOIN quartier q      ON q.id = c.id_quartier
     JOIN type_activite t ON t.id = q.id_type_activite';

/** Tous les contrats d'un client (actifs d'abord), avec nom du quartier et du type d'activité. */
function bdContratsByClient(int $idClient): array
{
    $st = getConnexion()->prepare(
        BD_CONTRAT_SELECT . " WHERE c.id_client = ? ORDER BY c.statut = 'actif' DESC, c.id"
    );
    $st->execute([$idClient]);
    return $st->fetchAll();
}

/** Un contrat (avec quartier et type d'activité), ou [] s'il n'existe pas. */
function bdContratOne(int $id): array
{
    $st = getConnexion()->prepare(BD_CONTRAT_SELECT . ' WHERE c.id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: [];
}

/** Le contrat appartient-il à ce client ? (à tester AVANT toute action d'un client sur un contrat) */
function bdContratAppartient(int $idContrat, int $idClient): bool
{
    $st = getConnexion()->prepare('SELECT COUNT(*) FROM contrat WHERE id = ? AND id_client = ?');
    $st->execute([$idContrat, $idClient]);
    return (int) $st->fetchColumn() > 0;
}

/**
 * Le contrat est-il actif ? (à tester en plus pour la SAISIE : on ne saisit pas sur un contrat résilié ;
 * mais on peut toujours CONSULTER les factures d'un contrat résilié, d'où cette fonction séparée.)
 */
function bdContratEstActif(int $idContrat): bool
{
    $st = getConnexion()->prepare("SELECT COUNT(*) FROM contrat WHERE id = ? AND statut = 'actif'");
    $st->execute([$idContrat]);
    return (int) $st->fetchColumn() > 0;
}

/** Contrats ACTIFS d'un client (pour la liste déroulante de la saisie). */
function bdContratsActifsByClient(int $idClient): array
{
    $st = getConnexion()->prepare(BD_CONTRAT_SELECT . " WHERE c.id_client = ? AND c.statut = 'actif' ORDER BY c.id");
    $st->execute([$idClient]);
    return $st->fetchAll();
}

/** Seuils d'anomalie du contrat = ceux de son type d'activité : ['seuil_eau' => float, 'seuil_elec' => float]. */
function bdContratSeuils(int $idContrat): array
{
    $c = bdContratOne($idContrat);
    return [
        'seuil_eau'  => (float) ($c['seuil_eau']  ?? 50),
        'seuil_elec' => (float) ($c['seuil_elec'] ?? 500),
    ];
}

/**
 * Barème du contrat, au MÊME format que l'ancienne constante TRANCHES de Traitement/Facture.php :
 * ['elec' => [[borne_max|null, prix], ...], 'eau' => [[borne_max|null, prix], ...]] trié par ordre.
 * Farah : calculerMontant() peut donc garder sa logique, en recevant ce tableau au lieu de la constante.
 */
function bdContratTranches(int $idContrat): array
{
    $st = getConnexion()->prepare(
        'SELECT tt.service, tt.borne_max, tt.prix_unitaire
         FROM tranche_tarif tt
         JOIN quartier q ON q.id_type_activite = tt.id_type_activite
         JOIN contrat c  ON c.id_quartier = q.id
         WHERE c.id = ?
         ORDER BY tt.service, tt.ordre'
    );
    $st->execute([$idContrat]);

    $res = ['eau' => [], 'elec' => []];
    foreach ($st->fetchAll() as $t) {
        $cle = $t['service'] === 'electricite' ? 'elec' : 'eau';
        $res[$cle][] = [
            $t['borne_max'] === null ? null : (float) $t['borne_max'],
            (float) $t['prix_unitaire'],
        ];
    }
    return $res;
}
