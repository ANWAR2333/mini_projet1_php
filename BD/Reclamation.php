<?php
declare(strict_types=1);
/**
 * BD/Reclamation.php — Chaimaa Racile
 * Couche BD : requêtes SQL uniquement (PDO préparé). Pas de HTML, pas de $_SESSION.
 * Table reclamation : id, id_client, id_facture (facultatif), id_contrat (facultatif, pour les fuites),
 * type, autre_type, description, date_reclamation, statut, reponse, date_reponse.
 */

/** Colonnes communes : réclamation + client + facture + contrat/quartier. */
const BD_RECLAMATION_SELECT =
    'SELECT r.*, c.nom, c.prenom, c.email,
            f.numero AS facture_numero, f.periode AS facture_periode,
            q.nom AS quartier
     FROM reclamation r
     JOIN client c        ON c.id  = r.id_client
     LEFT JOIN facture f  ON f.id  = r.id_facture
     LEFT JOIN contrat ct ON ct.id = r.id_contrat
     LEFT JOIN quartier q ON q.id  = ct.id_quartier';

/** Enregistre une réclamation (statut « en_attente ») et renvoie son id. */
function bdReclamationInsert(int $idClient, string $type, ?string $autreType, string $description,
                             ?int $idFacture, ?int $idContrat): int
{
    $pdo = getConnexion();
    $st  = $pdo->prepare(
        "INSERT INTO reclamation (id_client, id_facture, id_contrat, type, autre_type, description, statut)
         VALUES (:id_client, :id_facture, :id_contrat, :type, :autre_type, :description, 'en_attente')");
    $st->execute([
        ':id_client'   => $idClient,
        ':id_facture'  => $idFacture,
        ':id_contrat'  => $idContrat,
        ':type'        => $type,
        ':autre_type'  => $autreType,
        ':description' => $description,
    ]);
    return (int) $pdo->lastInsertId();
}

/** Réclamations d'un client, de la plus récente à la plus ancienne. */
function bdReclamationByClient(int $idClient): array
{
    $st = getConnexion()->prepare(
        BD_RECLAMATION_SELECT . ' WHERE r.id_client = :id ORDER BY r.date_reclamation DESC, r.id DESC');
    $st->execute([':id' => $idClient]);
    return $st->fetchAll();
}

/** Toutes les réclamations. $statut = null (toutes) | 'en_attente' | 'en_cours' | 'traitee'. */
function bdReclamationAll(?string $statut = null): array
{
    $sql    = BD_RECLAMATION_SELECT;
    $params = [];
    if ($statut !== null) {
        $sql .= ' WHERE r.statut = :statut';
        $params[':statut'] = $statut;
    }
    $sql .= " ORDER BY FIELD(r.statut, 'en_attente', 'en_cours', 'traitee'), r.date_reclamation DESC, r.id DESC";

    $st = getConnexion()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Une réclamation, ou [] si elle n'existe pas. */
function bdReclamationOne(int $id): array
{
    $st = getConnexion()->prepare(BD_RECLAMATION_SELECT . ' WHERE r.id = :id');
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    return $row ?: [];
}

/** Nombre de réclamations par statut, ex. ['en_attente' => 2, 'en_cours' => 0, 'traitee' => 1]. */
function bdReclamationCompterParStatut(): array
{
    $compte = ['en_attente' => 0, 'en_cours' => 0, 'traitee' => 0];
    foreach (getConnexion()->query('SELECT statut, COUNT(*) AS n FROM reclamation GROUP BY statut') as $l) {
        $compte[$l['statut']] = (int) $l['n'];
    }
    return $compte;
}

/**
 * Enregistre la réponse du fournisseur. Refusé (0 ligne) si la réclamation est déjà traitée :
 * une réclamation traitée est figée.
 */
function bdReclamationRepondre(int $id, string $statut, string $reponse): int
{
    $st = getConnexion()->prepare(
        "UPDATE reclamation
         SET statut = :statut, reponse = :reponse, date_reponse = NOW()
         WHERE id = :id AND statut <> 'traitee'");
    $st->execute([':statut' => $statut, ':reponse' => $reponse, ':id' => $id]);
    return $st->rowCount();
}
