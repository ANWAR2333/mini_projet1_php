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
                         float $consoEau, float $consoElec, float $ht, float $tva, float $ttc,
                         ?int $idContrat = null, ?float $montantEau = null, ?float $montantElec = null): int
{
    $pdo = getConnexion();
    $sql = "INSERT INTO facture (numero, id_client, id_consommation, date_facture, periode,
                                 conso_eau, conso_elec, montant_eau, montant_elec,
                                 id_contrat, montant_ht, tva, montant_ttc, statut)
            VALUES (:numero, :id_client, :id_conso, :date_facture, :periode,
                    :conso_eau, :conso_elec, :montant_eau, :montant_elec,
                    :id_contrat, :ht, :tva, :ttc, 'impayee')";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':numero' => $numero, ':id_client' => $idClient, ':id_contrat' => $idContrat, ':id_conso' => $idConso,
        ':date_facture' => $dateFacture, ':periode' => $periode,
        ':conso_eau' => $consoEau, ':conso_elec' => $consoElec,
        ':montant_eau' => $montantEau, ':montant_elec' => $montantElec,
        ':id_contrat' => $idContrat,
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
function bdFactureByClient(int $idClient, ?int $idContrat = null): array
{
    $sql = "SELECT f.id, f.numero, f.id_contrat, q.nom AS quartier,
                   ct.num_compteur_eau, ct.num_compteur_elec,
                   f.date_facture, f.periode, f.conso_eau, f.conso_elec,
                   f.montant_ht, f.tva, f.montant_ttc, f.statut
            FROM facture f
            LEFT JOIN contrat ct ON ct.id = f.id_contrat
            LEFT JOIN quartier q ON q.id = ct.id_quartier
            WHERE f.id_client = :id";
    $params = [':id' => $idClient];
    if ($idContrat !== null) {
        $sql .= ' AND f.id_contrat = :id_contrat';
        $params[':id_contrat'] = $idContrat;
    }
    $sql .= ' ORDER BY f.date_facture DESC, f.id DESC';

    $st = getConnexion()->prepare($sql);
    $st->execute($params);
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
                c.nom, c.prenom, c.adresse,
                ct.num_compteur_eau, ct.num_compteur_elec, ct.id AS contrat_numero,
                q.nom AS quartier, t.nom AS type_activite,
                co.mois, co.annee, co.index_eau, co.index_elec, co.photo_eau, co.photo_elec
         FROM facture f
         JOIN client c        ON c.id  = f.id_client
         JOIN consommation co ON co.id = f.id_consommation
         LEFT JOIN contrat ct ON ct.id = f.id_contrat
         LEFT JOIN quartier q ON q.id = ct.id_quartier
         LEFT JOIN type_activite t ON t.id = q.id_type_activite
         WHERE f.id = :id");
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    return $row ?: [];
}

// Chaimaa : factures côté fournisseur

/** Toutes les factures. Filtres facultatifs : statut ('impayee'|'payee'), quartier, type d'activité. */
function bdFactureAll(?string $statut = null, ?int $idQuartier = null, ?int $idType = null): array
{
    $sql = "SELECT f.id, f.numero, f.id_contrat, q.nom AS quartier, t.nom AS type_activite,
                   f.date_facture, f.periode, f.montant_ttc, f.statut,
                   c.nom, c.prenom
            FROM facture f
            JOIN client c ON c.id = f.id_client
            LEFT JOIN contrat ct      ON ct.id = f.id_contrat
            LEFT JOIN quartier q      ON q.id  = ct.id_quartier
            LEFT JOIN type_activite t ON t.id  = q.id_type_activite
            WHERE 1 = 1";
    $params = [];
    if ($statut !== null) {
        $sql .= " AND f.statut = :statut";
        $params[':statut'] = $statut;
    }
    if ($idQuartier !== null) {
        $sql .= " AND q.id = :quartier";
        $params[':quartier'] = $idQuartier;
    }
    if ($idType !== null) {
        $sql .= " AND t.id = :type";
        $params[':type'] = $idType;
    }
    $sql .= " ORDER BY f.date_facture DESC, f.id DESC";

    $st = getConnexion()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Change le statut d'une facture. Renvoie false si elle avait déjà ce statut. */
function bdFactureSetStatut(int $id, string $statut): bool
{
    $st = getConnexion()->prepare(
        "UPDATE facture SET statut = :statut WHERE id = :id AND statut <> :statut2");
    $st->execute([':statut' => $statut, ':statut2' => $statut, ':id' => $id]);
    return $st->rowCount() === 1;
}