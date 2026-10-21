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