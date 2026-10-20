<?php

function bdConsoOne(int $id): array
{
    $st = getConnexion()->prepare('SELECT * FROM consommation WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: [];
}

function bdConsoExiste(int $idClient, int $mois, int $annee): bool
{
    $st = getConnexion()->prepare(
        'SELECT COUNT(*) FROM consommation WHERE id_client = ? AND mois = ? AND annee = ?'
    );
    $st->execute([$idClient, $mois, $annee]);
    return (int) $st->fetchColumn() > 0;
}

function bdConsoIndexInitial(int $idClient): array
{
    $st = getConnexion()->prepare('SELECT index_initial_eau, index_initial_elec FROM client WHERE id = ?');
    $st->execute([$idClient]);
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


function bdConsoPrecedente(int $idClient, int $mois, int $annee): array
{
    $sql = 'SELECT c.*, ' . bdConsoSqlCorrige('eau', 'eau_corrige') . ', '
         . bdConsoSqlCorrige('electricite', 'elec_corrige') . "
            FROM consommation c
            WHERE c.id_client = ?
              AND c.statut IN ('normale', 'corrigee')
              AND (c.annee < ? OR (c.annee = ? AND c.mois < ?))
            ORDER BY c.annee DESC, c.mois DESC
            LIMIT 1";
    $st = getConnexion()->prepare($sql);
    $st->execute([$idClient, $annee, $annee, $mois]);
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

    $init = bdConsoIndexInitial($idClient);
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

function bdConsoByClient(int $idClient): array
{
    $sql = 'SELECT c.*, ' . bdConsoSqlCorrige('eau', 'eau_corrige') . ', '
         . bdConsoSqlCorrige('electricite', 'elec_corrige') . '
            FROM consommation c
            WHERE c.id_client = ?
            ORDER BY c.annee ASC, c.mois ASC';
    $st = getConnexion()->prepare($sql);
    $st->execute([$idClient]);
    return $st->fetchAll();
}


function bdConsoEnregistrer(array $conso, array $anomalies): int
{
    $pdo = getConnexion();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'INSERT INTO consommation (id_client, mois, annee, index_eau, index_elec, photo_eau, photo_elec, statut)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $conso['id_client'], $conso['mois'], $conso['annee'],
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
