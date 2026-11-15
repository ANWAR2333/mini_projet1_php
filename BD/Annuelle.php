<?php
declare(strict_types=1);
// Requêtes du relevé annuel (consommation_annuelle) et de sa vérification.

function bdAgentExiste(int $idAgent): bool
{
    $st = getConnexion()->prepare('SELECT COUNT(*) FROM agent WHERE id = ?');
    $st->execute([$idAgent]);
    return (int) $st->fetchColumn() > 0;
}

function bdAnnuelleExiste(int $idContrat, int $annee): bool
{
    $st = getConnexion()->prepare('SELECT COUNT(*) FROM consommation_annuelle WHERE id_contrat = ? AND annee = ?');
    $st->execute([$idContrat, $annee]);
    return (int) $st->fetchColumn() > 0;
}

/** Enregistre le relevé annuel d'un contrat (id_client est déduit du contrat). */
function bdAnnuelleInsert(int $idContrat, int $idClient, int $idAgent, float $eau, float $elec, int $annee, string $dateSaisie): int
{
    $pdo = getConnexion();
    $st  = $pdo->prepare(
        'INSERT INTO consommation_annuelle (id_client, id_contrat, id_agent, conso_eau, conso_elec, annee, date_saisie)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute([$idClient, $idContrat, $idAgent, $eau, $elec, $annee, $dateSaisie]);
    return (int) $pdo->lastInsertId();
}

/** Relevés annuels d'une année, avec client, contrat et agent. */
function bdAnnuelleByAnnee(int $annee): array
{
    $st = getConnexion()->prepare(
        'SELECT a.*, cl.nom, cl.prenom, ag.nom AS agent_nom, ag.prenom AS agent_prenom
         FROM consommation_annuelle a
         JOIN client cl ON cl.id = a.id_client
         JOIN agent ag  ON ag.id = a.id_agent
         WHERE a.annee = ?
         ORDER BY a.id_contrat'
    );
    $st->execute([$annee]);
    return $st->fetchAll();
}

/** Tous les contrats avec, s'il existe, leur relevé annuel de l'année. */
function bdAnnuelleContratsVerif(int $annee): array
{
    $st = getConnexion()->prepare(
        'SELECT c.id AS id_contrat, c.index_initial_eau, c.index_initial_elec, c.statut,
                cl.nom, cl.prenom, q.nom AS quartier, t.nom AS type_activite,
                a.conso_eau AS annuel_eau, a.conso_elec AS annuel_elec
         FROM contrat c
         JOIN client cl       ON cl.id = c.id_client
         JOIN quartier q      ON q.id = c.id_quartier
         JOIN type_activite t ON t.id = q.id_type_activite
         LEFT JOIN consommation_annuelle a ON a.id_contrat = c.id AND a.annee = ?
         ORDER BY c.id'
    );
    $st->execute([$annee]);
    return $st->fetchAll();
}

/** Saisies valides (normales ou corrigées) d'un contrat jusqu'à l'année donnée, avec les index corrigés. */
function bdAnnuelleSaisies(int $idContrat, int $annee): array
{
    $corrige = fn(string $service) =>
        "(SELECT a.index_corrige FROM anomalie a
          WHERE a.id_consommation = c.id AND a.service = '$service'
            AND a.statut = 'corrigee' AND a.index_corrige IS NOT NULL LIMIT 1)";
    $st = getConnexion()->prepare(
        'SELECT c.annee, c.mois,
                COALESCE(' . $corrige('eau') . ', c.index_eau) AS index_eau,
                COALESCE(' . $corrige('electricite') . ', c.index_elec) AS index_elec
         FROM consommation c
         WHERE c.id_contrat = ? AND c.statut IN (\'normale\', \'corrigee\') AND c.annee <= ?
         ORDER BY c.annee, c.mois'
    );
    $st->execute([$idContrat, $annee]);
    return $st->fetchAll();
}
