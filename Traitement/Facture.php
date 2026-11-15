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
function calculerMontant(float $conso, string $type, ?array $barème = null): float
{
    if ($conso <= 0) {
        return 0.0;
    }
    $grilles = $barème ?? TRANCHES;
    if (empty($grilles[$type])) {
        throw new RuntimeException('Aucun barème tarifaire disponible pour le service « ' . $type . ' ».');
    }
    $tranches = $grilles[$type];

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

    $c = bdConsoOne($idConso);
    if (!$c || empty($c['id_contrat'])) {
        throw new RuntimeException('La consommation ne correspond à aucun contrat.');
    }
    $idContrat = (int) $c['id_contrat'];
    $prec = bdConsoPrecedente($idContrat, (int) $c['mois'], (int) $c['annee']);
    $p = bdParametreGet();

    // R4 – consommation du mois
    $consoEau  = round((float) $c['index_eau']  - (float) $prec['index_eau'], 2);
    $consoElec = round((float) $c['index_elec'] - (float) $prec['index_elec'], 2);

    // R7 – montants
    $tarifs = bdContratTranches($idContrat);
    $montantEau = calculerMontant($consoEau, 'eau', $tarifs);
    $montantElec = calculerMontant($consoElec, 'elec', $tarifs);
    $ht  = round($montantEau + $montantElec, 2);
    $tva = round($ht * (float) $p['taux_tva'], 2);
    $ttc = round($ht + $tva, 2);

    // R8 – numéro unique par contrat et période, période MM/AAAA
    $numero  = sprintf('F-%02d%02d-%04d-%04d', $c['mois'], $c['annee'] % 100, $c['id_client'], $idContrat);
    $periode = sprintf('%02d/%04d', $c['mois'], $c['annee']);

    return bdFactureInsert(
        $numero,
        (int) $c['id_client'],
        $idConso,
        date('Y-m-d'),
        $periode,
        $consoEau,
        $consoElec,
        $ht,
        $tva,
        $ttc,
        $idContrat,
        $montantEau,
        $montantElec
    );
}

/** Route Facture/all — liste des factures du client connecté. */
function factureAll(): void
{
    verifierRole('client');
    $idClient = (int) $_SESSION['id'];

    // Filtre optionnel ?id_contrat= : on ignore une valeur qui n'appartient pas au client,
    // au lieu de planter ou de montrer les factures de quelqu'un d'autre.
    $idContrat = isset($_GET['id_contrat']) ? (int) $_GET['id_contrat'] : null;
    if ($idContrat !== null && !bdContratAppartient($idContrat, $idClient)) {
        $idContrat = null;
    }

    $factures = bdFactureByClient($idClient, $idContrat);

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
        'contrats'    => bdContratsByClient($idClient),
        'idContrat'   => $idContrat,
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
    $tarifs = !empty($facture['id_contrat']) ? bdContratTranches((int) $facture['id_contrat']) : null;
    $facture['montant_eau'] = $facture['montant_eau'] !== null
        ? (float) $facture['montant_eau']
        : calculerMontant((float) $facture['conso_eau'], 'eau', $tarifs);
    $facture['montant_elec'] = $facture['montant_elec'] !== null
        ? (float) $facture['montant_elec']
        : calculerMontant((float) $facture['conso_elec'], 'elec', $tarifs);

    afficherVue('facture/one.php', [
        'titre'   => 'Facture ' . $facture['numero'],
        'facture' => $facture,
    ]);
}

//  Factures côté fournisseur 

/** Route Facture/admin : toutes les factures, filtres ?statut= &quartier= &type=. */
function factureAdmin(): void
{
    verifierRole('fournisseur');

    $statut = $_GET['statut'] ?? null;
    if (!in_array($statut, ['impayee', 'payee'], true)) {
        $statut = null;
    }
    // Un id invalide ou vide = pas de filtre
    $idQuartier = filter_var($_GET['quartier'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    $idType     = filter_var($_GET['type'] ?? '',     FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;

    // Total des impayés : toujours sur toutes les factures, pas seulement les filtrées
    $totalImpaye = 0.0;
    foreach (bdFactureAll('impayee') as $f) {
        $totalImpaye += (float) $f['montant_ttc'];
    }

    $factures = bdFactureAll($statut, $idQuartier, $idType);

    // Historique des paiements des factures payées
    $paiements = [];
    foreach ($factures as $f) {
        if ($f['statut'] === 'payee') {
            $paiements[(int) $f['id']] = bdPaiementByFacture((int) $f['id']);
        }
    }

    afficherVue('facture/index_fournisseur.php', [
        'titre'       => 'Factures',
        'factures'    => $factures,
        'paiements'   => $paiements,
        'statut'      => $statut,
        'idQuartier'  => $idQuartier,
        'idType'      => $idType,
        'quartiers'   => bdQuartierAll(),
        'types'       => bdTypeActiviteAll(),
        'totalImpaye' => $totalImpaye,
    ]);
}


/** Route Facture/pdf/{id} : PDF d'une facture. Un client ne télécharge que les siennes. */
function facturePdf(int $id): void
{
    verifierRole('connecte');
    $f = bdFactureOne($id);
    if (!$f || ($_SESSION['role'] === 'client' && (int) $f['id_client'] !== (int) $_SESSION['id'])) {
        afficher404();
    }

    $tarifs = !empty($f['id_contrat']) ? bdContratTranches((int) $f['id_contrat']) : null;
    $f['montant_eau'] = $f['montant_eau'] !== null
        ? (float) $f['montant_eau']
        : calculerMontant((float) $f['conso_eau'], 'eau', $tarifs);
    $f['montant_elec'] = $f['montant_elec'] !== null
        ? (float) $f['montant_elec']
        : calculerMontant((float) $f['conso_elec'], 'elec', $tarifs);

    require_once __DIR__ . '/../lib/fpdf/fpdf.php';

    // FPDF ne gère pas l'UTF-8 : on convertit les accents.
    $t        = fn($x) => iconv('UTF-8', 'windows-1252//TRANSLIT', (string) $x);
    $dh       = fn($x) => number_format((float) $x, 2, ',', ' ') . ' DH';
    $num      = fn($x) => rtrim(rtrim(number_format((float) $x, 2, ',', ''), '0'), ',');
    $moisNoms = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    $debut    = sprintf('01/%02d/%04d', $f['mois'], $f['annee']);
    $fin      = date('t/m/Y', mktime(0, 0, 0, (int) $f['mois'], 1, (int) $f['annee']));
    $payee    = $f['statut'] === 'payee';

    $pdf = new FPDF();
    $pdf->SetMargins(15, 15);
    $pdf->AddPage();

    // En-tête : logo à gauche, numéro, date et statut à droite
    $pdf->Image(__DIR__ . '/../assets/img/logo.png', 15, 12, 50);
    $pdf->SetXY(100, 14);
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->Cell(95, 8, $t('Facture N° ' . $f['numero']), 0, 2, 'R');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(95, 6, $t('Date : ' . date('d/m/Y', strtotime($f['date_facture']))), 0, 2, 'R');
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor($payee ? 22 : 224, $payee ? 130 : 36, $payee ? 60 : 36);
    $pdf->Cell(95, 6, $t($payee ? 'Payée' : 'Impayée'), 0, 1, 'R');
    $pdf->SetTextColor(20, 22, 58);
    $pdf->Ln(10);

    // Client et période
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 8, $t('Facture de ' . $moisNoms[(int) $f['mois'] - 1] . ' ' . (int) $f['annee']), 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 6, $t('Période : ' . $debut . ' – ' . $fin), 0, 1);
    $pdf->Cell(0, 6, $t('Client : ' . $f['prenom'] . ' ' . $f['nom']), 0, 1);
    $pdf->Cell(0, 6, $t('Adresse : ' . ($f['adresse'] ?? '')), 0, 1);
    if (!empty($f['contrat_numero'])) {
        $pdf->Cell(0, 6, $t('Contrat : #' . $f['contrat_numero'] . ' · ' . ($f['quartier'] ?? '')), 0, 1);
        $pdf->Cell(0, 6, $t('Compteurs : eau ' . ($f['num_compteur_eau'] ?? '—') . ' · électricité ' . ($f['num_compteur_elec'] ?? '—')), 0, 1);
    }
    $pdf->Cell(0, 6, $t('Index relevés : eau ' . $num($f['index_eau']) . ' m³ · électricité ' . $num($f['index_elec']) . ' kWh'), 0, 1);
    $pdf->Ln(6);

    // Tableau des montants
    $pdf->SetFillColor(220, 232, 251);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(70, 9, $t('Service'), 'B', 0, 'L', true);
    $pdf->Cell(55, 9, $t('Consommation'), 'B', 0, 'R', true);
    $pdf->Cell(55, 9, $t('Montant HT'), 'B', 1, 'R', true);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(70, 8, $t('Eau'), 'B');
    $pdf->Cell(55, 8, $t($num($f['conso_eau']) . ' m³'), 'B', 0, 'R');
    $pdf->Cell(55, 8, $t($dh($f['montant_eau'])), 'B', 1, 'R');
    $pdf->Cell(70, 8, $t('Électricité'), 'B');
    $pdf->Cell(55, 8, $t($num($f['conso_elec']) . ' kWh'), 'B', 0, 'R');
    $pdf->Cell(55, 8, $t($dh($f['montant_elec'])), 'B', 1, 'R');

    $pdf->Ln(4);
    $pdf->Cell(125, 7, $t('Montant HT'), 0, 0, 'R');
    $pdf->Cell(55, 7, $t($dh($f['montant_ht'])), 0, 1, 'R');
    $pdf->Cell(125, 7, $t('TVA 18 %'), 0, 0, 'R');
    $pdf->Cell(55, 7, $t($dh($f['tva'])), 0, 1, 'R');
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(125, 10, $t('Total TTC'), 'T', 0, 'R');
    $pdf->Cell(55, 10, $t($dh($f['montant_ttc'])), 'T', 1, 'R');

    // Photos des compteurs (si les fichiers existent)
    $y = $pdf->GetY() + 10;
    foreach (['photo_eau' => [15, 'Compteur d\'eau'], 'photo_elec' => [110, 'Compteur d\'électricité']] as $cle => [$x, $legende]) {
        $chemin = __DIR__ . '/../' . ($f[$cle] ?? '');
        if (!empty($f[$cle]) && is_file($chemin)) {
            try {
                [$lw, $lh] = getimagesize($chemin);
                $w = 85;
                $h = $w * $lh / $lw;
                if ($h > 50) {
                    $h = 50;
                    $w = $h * $lw / $lh;
                }
                $pdf->Image($chemin, $x, $y, $w, $h);
                $pdf->SetXY($x, $y + 52);
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->Cell(85, 5, $t($legende), 0, 0, 'L');
            } catch (Exception $e) {
                // Image illisible par FPDF : on l'ignore, le reste du PDF reste valable.
            }
        }
    }

    $pdf->Output('D', 'Facture_' . $f['numero'] . '.pdf');
    exit;
}