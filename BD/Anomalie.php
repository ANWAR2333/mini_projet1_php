<?php

function bdAnomalieInsert(int $idConso, string $service, string $type, float $indexPrecedent, float $indexSaisi): int
{
    $pdo = getConnexion();
    $st = $pdo->prepare(
        "INSERT INTO anomalie (id_consommation, service, type, index_precedent, index_saisi, statut)
         VALUES (?, ?, ?, ?, ?, 'a_traiter')"
    );
    $st->execute([$idConso, $service, $type, $indexPrecedent, $indexSaisi]);
    return (int) $pdo->lastInsertId();
}

function bdAnomalieByConso(int $idConso): array
{
    $st = getConnexion()->prepare('SELECT * FROM anomalie WHERE id_consommation = ? ORDER BY id');
    $st->execute([$idConso]);
    return $st->fetchAll();
}

function bdAnomalieByClient(int $idClient): array
{
    $st = getConnexion()->prepare(
        'SELECT a.id_consommation, a.service, a.type, a.statut
         FROM anomalie a
         JOIN consommation c ON c.id = a.id_consommation
         WHERE c.id_client = ?
         ORDER BY a.id'
    );
    $st->execute([$idClient]);
    return $st->fetchAll();
}
