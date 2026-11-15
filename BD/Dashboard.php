<?php
declare(strict_types=1);
// Requêtes du tableau de bord fournisseur (lecture seule).

/** Totaux des factures : nombre, montants total / payé / impayé. */
function bdDashboardFactures(): array
{
    return getConnexion()->query(
        "SELECT COUNT(*) AS nb,
                COALESCE(SUM(montant_ttc), 0) AS total,
                COALESCE(SUM(CASE WHEN statut = 'impayee' THEN 1 ELSE 0 END), 0) AS nb_impayees,
                COALESCE(SUM(CASE WHEN statut = 'impayee' THEN montant_ttc END), 0) AS total_impaye,
                COALESCE(SUM(CASE WHEN statut = 'payee' THEN montant_ttc END), 0) AS total_paye
         FROM facture"
    )->fetch();
}

/** Compteurs : clients, contrats actifs, anomalies à traiter, réclamations en attente. */
function bdDashboardCompteurs(): array
{
    $pdo = getConnexion();
    return [
        'clients'      => (int) $pdo->query('SELECT COUNT(*) FROM client')->fetchColumn(),
        'contrats'     => (int) $pdo->query("SELECT COUNT(*) FROM contrat WHERE statut = 'actif'")->fetchColumn(),
        'anomalies'    => (int) $pdo->query("SELECT COUNT(*) FROM anomalie WHERE statut = 'a_traiter'")->fetchColumn(),
        'reclamations' => (int) $pdo->query("SELECT COUNT(*) FROM reclamation WHERE statut = 'en_attente'")->fetchColumn(),
    ];
}

/** Consommation facturée par mois (les 12 derniers mois facturés, du plus ancien au plus récent). */
function bdDashboardEvolution(): array
{
    $lignes = getConnexion()->query(
        'SELECT co.annee, co.mois, SUM(f.conso_eau) AS eau, SUM(f.conso_elec) AS elec
         FROM facture f
         JOIN consommation co ON co.id = f.id_consommation
         GROUP BY co.annee, co.mois
         ORDER BY co.annee DESC, co.mois DESC
         LIMIT 12'
    )->fetchAll();
    return array_reverse($lignes);
}

/** Factures par quartier : nombre, montant TTC, dont impayé (factures sans contrat regroupées). */
function bdDashboardParQuartier(): array
{
    return getConnexion()->query(
        "SELECT COALESCE(q.nom, 'Sans contrat') AS quartier,
                COUNT(f.id) AS nb,
                SUM(f.montant_ttc) AS total,
                SUM(CASE WHEN f.statut = 'impayee' THEN f.montant_ttc ELSE 0 END) AS impaye
         FROM facture f
         LEFT JOIN contrat c  ON c.id = f.id_contrat
         LEFT JOIN quartier q ON q.id = c.id_quartier
         GROUP BY q.id, q.nom
         ORDER BY total DESC"
    )->fetchAll();
}
