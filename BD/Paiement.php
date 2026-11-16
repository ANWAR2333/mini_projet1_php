<?php
declare(strict_types=1);

/** Enregistre un paiement et renvoie son id. $mode = 'especes' | 'carte' | 'virement'. */
function bdPaiementInsert(int $idFacture, string $datePaiement, float $montant, string $mode): int
{
    $pdo = getConnexion();
    $st  = $pdo->prepare(
        "INSERT INTO paiement (id_facture, date_paiement, montant, mode)
         VALUES (:id_facture, :date_paiement, :montant, :mode)");
    $st->execute([
        ':id_facture'    => $idFacture,
        ':date_paiement' => $datePaiement,
        ':montant'       => $montant,
        ':mode'          => $mode,
    ]);
    return (int) $pdo->lastInsertId();
}
/** Paiements d'une facture, du plus récent au plus ancien. */
function bdPaiementByFacture(int $idFacture): array
{
    $st = getConnexion()->prepare(
        "SELECT id, date_paiement, montant, mode
         FROM paiement
         WHERE id_facture = :id
         ORDER BY date_paiement DESC, id DESC");
    $st->execute([':id' => $idFacture]);
    return $st->fetchAll();
}