<?php
declare(strict_types=1);
/**
 * BD/Notification.php — Farah Bel Aali
 * Couche BD : requêtes SQL uniquement (PDO préparé). Pas de HTML, pas de $_SESSION, pas de calcul métier.
 * Table notification : id, id_client, id_reclamation (nullable), message, date_envoi, lue (0/1).
 */

function bdNotificationByClient(int $idClient): array
{
    $st = getConnexion()->prepare(
        'SELECT * FROM notification WHERE id_client = ? ORDER BY date_envoi DESC'
    );
    $st->execute([$idClient]);
    return $st->fetchAll();
}

function bdNotificationInsert(int $idClient, string $message, ?int $idReclamation = null): int
{
    $pdo = getConnexion();
    $st = $pdo->prepare(
        'INSERT INTO notification (id_client, message, id_reclamation) VALUES (?, ?, ?)'
    );
    $st->execute([$idClient, $message, $idReclamation]);
    return (int) $pdo->lastInsertId();
}

function bdNotificationMarquerLue(int $id, int $idClient): int
{
    $st = getConnexion()->prepare(
        'UPDATE notification SET lue = 1 WHERE id = ? AND id_client = ? AND lue = 0'
    );
    $st->execute([$id, $idClient]);
    return $st->rowCount();
}
