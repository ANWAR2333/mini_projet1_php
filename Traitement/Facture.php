<?php
declare(strict_types=1);
/**
 * Traitement/Facture.php — Farah Bel Aali (partie client)
 * Logique : calcul des montants, génération automatique, liste et détail des factures du client.
 * Pas de SQL ici (tout passe par BD/), pas de HTML (tout est dans IHM/facture/).
 *
 * Les fichiers BD/ et Traitement/ sont chargés automatiquement par index.php (Souraya).
 * Fonctions utilisées :
 *  - BD/Facture.php      : bdFactureInsert, bdFactureIdByConso, bdFactureByClient, bdFactureOne (Farah)
 *  - BD/Consommation.php : bdConsoOne(), bdConsoPrecedente()   (Amal)
 *  - BD/Parametre.php    : bdParametreGet()                    (Amal)
 *  - Traitement/Auth.php : verifierRole(), afficherVue()       (Souraya) ; afficher404() dans index.php
 * Chaimaa ajoute factureAdmin() et facturePdf() à la fin de ce fichier.
 */

/* ---------------------------------------------------------------
 * R6 – Prix de la tranche appliqué à toute la consommation
 * (méthode du Suivi 1, à confirmer avec l'encadrant).
 * Pour passer au calcul progressif : mettre CALCUL_PROGRESSIF à true.
 * --------------------------------------------------------------- */
const CALCUL_PROGRESSIF = false;

const TRANCHES = [
    // [borne max incluse (null = sans limite), prix unitaire en DH]
    'elec' => [[100, 0.82], [150, 0.92], [null, 1.10]],
    'eau'  => [[6, 2.37], [20, 7.39], [35, 10.98], [null, 11.03]],
];

/** Montant HT d'un service. $type = 'eau' | 'elec'. Arrondi à 2 décimales. */
function calculerMontant(float $conso, string $type): float
{
    if ($conso <= 0) {
        return 0.0;
    }
    $tranches = TRANCHES[$type];

    if (!CALCUL_PROGRESSIF) {
        foreach ($tranches as [$max, $prix]) {
            if ($max === null || $conso <= $max) {
                return round($conso * $prix, 2);
            }
        }
    }

    // Calcul progressif : chaque tranche est facturée à son propre prix.
    $total = 0.0;
    $min = 0;
    foreach ($tranches as [$max, $prix]) {
        $haut = $max === null ? $conso : min($conso, $max);
        if ($haut > $min) {
            $total += ($haut - $min) * $prix;
        }
        if ($max === null || $conso <= $max) {
            break;
        }
        $min = $max;
    }
    return round($total, 2);
}

/**
 * Génère la facture d'une consommation (appelée par Amal après une saisie sans anomalie,
 * et plus tard après la correction d'une anomalie). Renvoie l'id de la facture.
 * Si la facture existe déjà pour cette consommation, renvoie son id sans en créer une autre.
 */
function factureGenerer(int $idConso): int
{
    $deja = bdFactureIdByConso($idConso);
    if ($deja !== null) {
        return $deja;
    }

    $c    = bdConsoOne($idConso);                                                          // saisie du mois
    $prec = bdConsoPrecedente((int) $c['id_client'], (int) $c['mois'], (int) $c['annee']); // R3
    $p    = bdParametreGet();                                                              // taux_tva = 0.18

    // R4 – consommation du mois
    $consoEau  = round((float) $c['index_eau']  - (float) $prec['index_eau'], 2);
    $consoElec = round((float) $c['index_elec'] - (float) $prec['index_elec'], 2);

    // R7 – montants
    $ht  = round(calculerMontant($consoEau, 'eau') + calculerMontant($consoElec, 'elec'), 2);
    $tva = round($ht * (float) $p['taux_tva'], 2);
    $ttc = round($ht + $tva, 2);

    // R8 – numéro F-MMAA-CCCC, période MM/AAAA
    $numero  = sprintf('F-%02d%02d-%04d', $c['mois'], $c['annee'] % 100, $c['id_client']);
    $periode = sprintf('%02d/%04d', $c['mois'], $c['annee']);

    return bdFactureInsert($numero, (int) $c['id_client'], $idConso, date('Y-m-d'), $periode,
                           $consoEau, $consoElec, $ht, $tva, $ttc);
}

/** Route Facture/all — liste des factures du client connecté. */
function factureAll(): void
{
    verifierRole('client');
    $factures = bdFactureByClient((int) $_SESSION['id']);

    $totalImpaye = 0.0;
    foreach ($factures as $f) {
        if ($f['statut'] === 'impayee') {
            $totalImpaye += (float) $f['montant_ttc'];
        }
    }

    afficherVue('facture/index.php', [
        'titre'       => 'Mes Factures',
        'factures'    => $factures,
        'totalImpaye' => $totalImpaye,
    ]);
}

/** Route Facture/one/{id} — détail d'une facture du client connecté. */
function factureOne(int $id): void
{
    verifierRole('client');
    $facture = bdFactureOne($id);

    // Un client ne voit que ses propres factures.
    if (!$facture || (int) $facture['id_client'] !== (int) $_SESSION['id']) {
        afficher404();
    }

    // Montant HT de chaque service pour le tableau du détail.
    $facture['montant_eau']  = calculerMontant((float) $facture['conso_eau'], 'eau');
    $facture['montant_elec'] = calculerMontant((float) $facture['conso_elec'], 'elec');

    afficherVue('facture/one.php', [
        'titre'   => 'Facture ' . $facture['numero'],
        'facture' => $facture,
    ]);
}

//  Factures côté fournisseur 

/** Route Facture/admin : toutes les factures, filtre ?statut=impayee|payee. */
function factureAdmin(): void
{
    verifierRole('fournisseur');

    $statut = $_GET['statut'] ?? null;
    if (!in_array($statut, ['impayee', 'payee'], true)) {
        $statut = null;
    }

    $totalImpaye = 0.0;
    foreach (bdFactureAll('impayee') as $f) {
        $totalImpaye += (float) $f['montant_ttc'];
    }

    afficherVue('facture/index_fournisseur.php', [
        'titre'       => 'Factures',
        'factures'    => bdFactureAll($statut),
        'statut'      => $statut,
        'totalImpaye' => $totalImpaye,
    ]);
}