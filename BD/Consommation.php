<?php

/**
 * BD/Consommation.php — Amal
 * Requêtes SQL de la saisie. Depuis la phase 2, tout passe par le CONTRAT (et plus par le client) :
 *  - une saisie par contrat et par mois (UNIQUE contrat_mois_annee) ;
 *  - l'index précédent est celui du contrat ; sans saisie, c'est l'index initial du contrat.
 * id_client reste enregistré dans consommation mais il est TOUJOURS celui du contrat.
 */

function bdConsoOne(int $id): array
{
    $st = getConnexion()->prepare('SELECT * FROM consommation WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: [];
}

/** Ce contrat a-t-il déjà une saisie pour ce mois ? (R2) */
function bdConsoExiste(int $idContrat, int $mois, int $annee): bool
{
    $st = getConnexion()->prepare(
        'SELECT COUNT(*) FROM consommation WHERE id_contrat = ? AND mois = ? AND annee = ?'
    );
    $st->execute([$idContrat, $mois, $annee]);
    return (int) $st->fetchColumn() > 0;
}

/** Index initial des deux compteurs du CONTRAT (et non plus du client). */
function bdConsoIndexInitial(int $idContrat): array
{
    $st = getConnexion()->prepare('SELECT index_initial_eau, index_initial_elec FROM contrat WHERE id = ?');
    $st->execute([$idContrat]);
    $r = $st->fetch() ?: [];
    return [
        'index_eau'  => (float) ($r['index_initial_eau']  ?? 0),
        'index_elec' => (float) ($r['index_initial_elec'] ?? 0),
    ];
}

function bdConsoSqlCorrige(string $service, string $alias): string
{
    return "(SELECT a.index_corrige FROM anomalie a
              WHERE a.id_consommation = c.id AND a.service = '$service'
                AND a.statut = 'corrigee' AND a.index_corrige IS NOT NULL
              LIMIT 1) AS $alias";
}


/**
 * R3 — Dernière saisie valide du CONTRAT avant (mois, annee) ; sinon index initial du contrat.
 * Attention (Farah) : le 1er paramètre est l'id du CONTRAT, pas celui du client.
 */
function bdConsoPrecedente(int $idContrat, int $mois, int $annee): array
{
    $sql = 'SELECT c.*, ' . bdConsoSqlCorrige('eau', 'eau_corrige') . ', '
         . bdConsoSqlCorrige('electricite', 'elec_corrige') . "
            FROM consommation c
            WHERE c.id_contrat = ?
              AND c.statut IN ('normale', 'corrigee')
              AND (c.annee < ? OR (c.annee = ? AND c.mois < ?))
            ORDER BY c.annee DESC, c.mois DESC
            LIMIT 1";
    $st = getConnexion()->prepare($sql);
    $st->execute([$idContrat, $annee, $annee, $mois]);
    $r = $st->fetch();

    if ($r) {
        return [
            'index_eau'   => (float) ($r['eau_corrige']  ?? $r['index_eau']),
            'index_elec'  => (float) ($r['elec_corrige'] ?? $r['index_elec']),
            'photo_eau'   => $r['photo_eau']  ?: null,
            'photo_elec'  => $r['photo_elec'] ?: null,
            'date_saisie' => $r['date_saisie'],
            'mois'        => (int) $r['mois'],
            'annee'       => (int) $r['annee'],
            'initial'     => false,
        ];
    }

    $init = bdConsoIndexInitial($idContrat);
    return [
        'index_eau'   => $init['index_eau'],
        'index_elec'  => $init['index_elec'],
        'photo_eau'   => null,
        'photo_elec'  => null,
        'date_saisie' => null,
        'mois'        => null,
        'annee'       => null,
        'initial'     => true,
    ];
}

/** Historique d'un contrat, du plus ancien au plus récent. */
function bdConsoByContrat(int $idContrat): array
{
    $sql = 'SELECT c.*, ' . bdConsoSqlCorrige('eau', 'eau_corrige') . ', '
         . bdConsoSqlCorrige('electricite', 'elec_corrige') . ',
                   q.nom AS quartier
            FROM consommation c
            LEFT JOIN contrat ct ON ct.id = c.id_contrat
            LEFT JOIN quartier q ON q.id = ct.id_quartier
            WHERE c.id_client = ?
            ORDER BY c.id_contrat ASC, c.annee ASC, c.mois ASC';
            WHERE c.id_contrat = ?
            ORDER BY c.annee ASC, c.mois ASC';
    $st = getConnexion()->prepare($sql);
    $st->execute([$idContrat]);
    return $st->fetchAll();
}

/**
 * Enregistre la saisie et ses anomalies dans une transaction.
 * $conso : id_contrat, id_client (celui du contrat), mois, annee, index_eau, index_elec, photo_eau, photo_elec, statut.
 */
function bdConsoEnregistrer(array $conso, array $anomalies): int
{
    $pdo = getConnexion();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'INSERT INTO consommation (id_contrat, id_client, mois, annee, index_eau, index_elec, photo_eau, photo_elec, statut)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $conso['id_contrat'], $conso['id_client'], $conso['mois'], $conso['annee'],
            $conso['index_eau'], $conso['index_elec'],
            $conso['photo_eau'], $conso['photo_elec'], $conso['statut'],
        ]);
        $idConso = (int) $pdo->lastInsertId();

        foreach ($anomalies as $a) {
            bdAnomalieInsert($idConso, $a['service'], $a['type'], $a['index_precedent'], $a['index_saisi']);
        }

        $pdo->commit();
        return $idConso;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
