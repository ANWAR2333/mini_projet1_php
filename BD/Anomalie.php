<?php

// Insertion des anomalies
/**
 * BD/Anomalie.php — Amal
 * Requêtes SQL des anomalies de saisie (détection côté client, traitement côté fournisseur).
 */

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

// Selection des anomalies depuis la base de données selon la consommation
function bdAnomalieByConso(int $idConso): array
{
    $st = getConnexion()->prepare('SELECT * FROM anomalie WHERE id_consommation = ? ORDER BY id');
    $st->execute([$idConso]);
    return $st->fetchAll();
}

// Selection des anomalies depuis la base de données selon le client
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

/**
 * Toutes les anomalies avec la saisie, le client et le contrat (liste du fournisseur).
 * Une ligne par anomalie ; les anomalies à traiter d'abord, puis les plus récentes.
 */
function bdAnomalieToutes(): array
{
    return getConnexion()->query(
        "SELECT a.id AS id_anomalie, a.service, a.type, a.statut AS statut_anomalie,
                a.index_precedent, a.index_saisi, a.index_corrige,
                co.id AS id_consommation, co.mois, co.annee, co.date_saisie, co.id_contrat,
                cl.nom, cl.prenom, q.nom AS quartier, t.nom AS type_activite
         FROM anomalie a
         JOIN consommation co ON co.id = a.id_consommation
         JOIN client cl       ON cl.id = co.id_client
         JOIN contrat ct      ON ct.id = co.id_contrat
         JOIN quartier q      ON q.id  = ct.id_quartier
         JOIN type_activite t ON t.id  = q.id_type_activite
         ORDER BY (a.statut = 'a_traiter') DESC, co.date_saisie DESC, a.id"
    )->fetchAll();
}

/** Une saisie en anomalie avec son client, son contrat, son quartier et les seuils de son type d'activité. */
function bdAnomalieSaisieOne(int $idConso): array
{
    $st = getConnexion()->prepare(
        'SELECT co.id, co.id_client, co.id_contrat, co.mois, co.annee, co.date_saisie, co.statut,
                co.index_eau, co.index_elec, co.photo_eau, co.photo_elec,
                cl.nom, cl.prenom, ct.num_compteur_eau, ct.num_compteur_elec,
                q.nom AS quartier, t.nom AS type_activite, t.seuil_eau, t.seuil_elec
         FROM consommation co
         JOIN client cl       ON cl.id = co.id_client
         JOIN contrat ct      ON ct.id = co.id_contrat
         JOIN quartier q      ON q.id  = ct.id_quartier
         JOIN type_activite t ON t.id  = q.id_type_activite
         WHERE co.id = ?'
    );
    $st->execute([$idConso]);
    return $st->fetch() ?: [];
}

/**
 * Enregistre les index corrigés ($corrections : id d'anomalie => index) dans UNE transaction :
 *  - l'anomalie passe à « corrigee » (index_corrige + date_correction ; l'index saisi reste dans index_saisi) ;
 *  - l'index de la saisie est remplacé par l'index corrigé : ainsi la facture, le PDF, l'index précédent du mois
 *    suivant et le relevé annuel lisent tous la bonne valeur sans rien changer dans le code des autres ;
 *  - quand il ne reste plus aucune anomalie à traiter, la saisie passe à « corrigee ».
 * Renvoie true si plus aucune anomalie n'est à traiter (la facture peut alors être générée).
 * Une anomalie déjà traitée est ignorée (double clic, deux fournisseurs en même temps).
 */
function bdAnomalieCorriger(int $idConso, array $corrections): bool
{
    $colonnes = ['eau' => 'index_eau', 'electricite' => 'index_elec'];   // liste blanche : jamais de nom de colonne venant du formulaire

    $pdo = getConnexion();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT id, service FROM anomalie WHERE id_consommation = ? AND statut = 'a_traiter' FOR UPDATE");
        $st->execute([$idConso]);
        $aTraiter = $st->fetchAll(PDO::FETCH_KEY_PAIR);          // id_anomalie => service

        $majAnomalie = $pdo->prepare(
            "UPDATE anomalie SET index_corrige = ?, date_correction = NOW(), statut = 'corrigee'
             WHERE id = ? AND statut = 'a_traiter'"
        );
        foreach ($corrections as $idAnomalie => $index) {
            if (!isset($aTraiter[$idAnomalie]) || !isset($colonnes[$aTraiter[$idAnomalie]])) {
                continue;
            }
            $majAnomalie->execute([$index, $idAnomalie]);
            $pdo->prepare('UPDATE consommation SET ' . $colonnes[$aTraiter[$idAnomalie]] . ' = ? WHERE id = ?')
                ->execute([$index, $idConso]);
        }

        $reste = $pdo->prepare("SELECT COUNT(*) FROM anomalie WHERE id_consommation = ? AND statut = 'a_traiter'");
        $reste->execute([$idConso]);
        $toutCorrige = (int) $reste->fetchColumn() === 0;

        if ($toutCorrige) {
            $pdo->prepare("UPDATE consommation SET statut = 'corrigee' WHERE id = ?")->execute([$idConso]);
        }

        $pdo->commit();
        return $toutCorrige;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
