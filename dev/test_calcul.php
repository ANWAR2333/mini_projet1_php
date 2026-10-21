<?php
if (PHP_SAPI !== 'cli') { header('Content-Type: text/plain; charset=utf-8'); }
/**
 * dev/test_calcul.php — test de calculerMontant() SANS base de données.
 * Lancer : php dev/test_calcul.php   (ou http://localhost/mini_projet1_php/dev/test_calcul.php)
 * Ne pas mettre en production ; peut être commité dans dev/.
 */
require __DIR__ . '/../Traitement/Facture.php';   // ne contient que des fonctions : rien ne s'exécute

$cas = [
    // [conso, type, attendu]
    [120, 'elec', 110.40], [100, 'elec', 82.00], [101, 'elec', 92.92], [150, 'elec', 138.00], [151, 'elec', 166.10],
    [15, 'eau', 110.85], [6, 'eau', 14.22], [7, 'eau', 51.73], [20, 'eau', 147.80], [35, 'eau', 384.30], [36, 'eau', 397.08],
    [0, 'eau', 0.00],
];
$ok = 0;
foreach ($cas as [$c, $t, $att]) {
    $r = calculerMontant($c, $t);
    $bon = abs($r - $att) < 0.001;
    $ok += $bon;
    printf("%-4s %6s %-4s → %8.2f DH (attendu %8.2f)\n", $bon ? 'OK' : 'FAUX', $c, $t, $r, $att);
}
$ht  = calculerMontant(120, 'elec') + calculerMontant(15, 'eau');
$tva = round($ht * 0.18, 2);
printf("\nExemple Suivi 1 : HT %.2f · TVA %.2f · TTC %.2f (attendu 221.25 · 39.83 · 261.08)\n", $ht, $tva, $ht + $tva);
printf("%d / %d cas corrects\n", $ok, count($cas));
