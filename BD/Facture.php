<?php
declare(strict_types=1);
/**
 * BD/Facture.php — Farah Bel Aali
 * Couche BD : requêtes SQL uniquement (PDO préparé). Pas de HTML, pas de $_SESSION, pas de calcul métier.
 * Dépend de : BD/Connexion.php → getConnexion(): PDO (Souraya)
 */
// Chargé automatiquement par le routeur (index.php charge tout BD/ et Traitement/).

/** Insère une facture et renvoie son id. */
function bdFactureInsert(string $numero, int $idClient, int $idConso, string $dateFacture, string $periode,
                         float $consoEau, float $consoElec, float $ht, float $tva, float $ttc): int
{
    $pdo = getConnexion();
    $sql = "INSERT INTO facture (numero, id_client, id_consommation, date_facture, periode,
                                 conso_eau, conso_elec, montant_ht, tva, montant_ttc, statut)
            VALUES (:numero, :id_client, :id_conso, :date_facture, :periode,
                    :conso_eau, :conso_elec, :ht, :tva, :ttc, 'impayee')";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':numero' => $numero, ':id_client' => $idClient, ':id_conso' => $idConso,
        ':date_facture' => $dateFacture, ':periode' => $periode,
        ':conso_eau' => $consoEau, ':conso_elec' => $consoElec,
        ':ht' => $ht, ':tva' => $tva, ':ttc' => $ttc,
    ]);
    return (int) $pdo->lastInsertId();
}

/** Facture déjà créée pour cette consommation ? (une seule par saisie). Renvoie son id ou null. */
function bdFactureIdByConso(int $idConso): ?int
{
    $st = getConnexion()->prepare("SELECT id FROM facture WHERE id_consommation = :id");
    $st->execute([':id' => $idConso]);
    $id = $st->fetchColumn();
    return $id === false ? null : (int) $id;
}

/** Factures d'un client, de la plus récente à la plus ancienne. */
function bdFactureByClient(int $idClient): array
{
    $st = getConnexion()->prepare(
        "SELECT id, numero, date_facture, periode, conso_eau, conso_elec,
                montant_ht, tva, montant_ttc, statut
         FROM facture
         WHERE id_client = :id
         ORDER BY date_facture DESC, id DESC");
    $st->execute([':id' => $idClient]);
    return $st->fetchAll();
}

/**
 * Une facture avec le client et la consommation (photos + index).
 * Contrat partagé (utilisé aussi par Chaimaa pour le PDF). Renvoie [] si introuvable.
 */
function bdFactureOne(int $id): array
{
    $st = getConnexion()->prepare(
        "SELECT f.*,
                c.nom, c.prenom, c.adresse, c.num_compteur_eau, c.num_compteur_elec,
                co.mois, co.annee, co.index_eau, co.index_elec, co.photo_eau, co.photo_elec
         FROM facture f
         JOIN client c        ON c.id  = f.id_client
         JOIN consommation co ON co.id = f.id_consommation
         WHERE f.id = :id");
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    return $row ?: [];
}
