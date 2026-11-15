<?php
declare(strict_types=1);

// Route Dashboard/all — informations synthétiques pour le fournisseur.
function dashboardAll(): void
{
    verifierRole('fournisseur');

    // Import automatique d'un éventuel fichier annuel déposé par l'agent
    $rapport = annuelleImporterAuto();
    if ($rapport !== null) {
        [$msg, $type] = annuelleMessage($rapport);
        $_SESSION['flash'] = ['message' => 'Fichier annuel : ' . $msg, 'type' => $type];
    }

    $evolution = bdDashboardEvolution();
    afficherVue('dashboard/index.php', [
        'titre'      => 'Tableau de bord',
        'factures'   => bdDashboardFactures(),
        'compteurs'  => bdDashboardCompteurs(),
        'graphEau'   => dashboardBarres($evolution, 'eau'),
        'graphElec'  => dashboardBarres($evolution, 'elec'),
        'quartiers'  => bdDashboardParQuartier(),
    ]);
}

/** Prépare les barres d'un graphique SVG (viewBox 480 x 170) : position, hauteur, valeur, libellé. */
function dashboardBarres(array $mois, string $cle): array
{
    $max = 0.0;
    foreach ($mois as $m) {
        $max = max($max, (float) $m[$cle]);
    }
    $barres = [];
    foreach ($mois as $i => $m) {
        $v = (float) $m[$cle];
        $h = $max > 0 ? (int) round($v / $max * 110) : 0;
        $barres[] = [
            'x'       => 16 + $i * 38,
            'y'       => 135 - $h,
            'hauteur' => $h,
            'valeur'  => rtrim(rtrim(number_format($v, 2, ',', ''), '0'), ','),
            'libelle' => sprintf('%02d/%02d', $m['mois'], (int) $m['annee'] % 100),
        ];
    }
    return $barres;
}
