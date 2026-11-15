<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { header('Content-Type: text/plain; charset=utf-8'); }
/**
 * dev/test_contrats.php — teste BD/Contrat.php sur la base réelle (après import de gestion_factures.sql).
 * Lancer : php dev/test_contrats.php   (ou http://localhost/gestion_factures/dev/test_contrats.php)
 */
require __DIR__ . '/../BD/Connexion.php';
require __DIR__ . '/../BD/Contrat.php';
require __DIR__ . '/../BD/TypeActivite.php';
require __DIR__ . '/../BD/Quartier.php';

$ok = 0; $total = 0;
$verif = function (string $nom, bool $condition) use (&$ok, &$total) {
    $total++; $ok += $condition;
    printf("%-5s %s\n", $condition ? 'OK' : 'FAUX', $nom);
};

$c1 = bdContratsByClient(1);
$verif('Salma (client 1) a 2 contrats', count($c1) === 2);
$verif('ils sont dans 2 quartiers différents', count(array_unique(array_column($c1, 'id_quartier'))) === 2);
$verif('quartier et type d\'activité sont renvoyés', isset($c1[0]['quartier'], $c1[0]['type_activite']));
$verif('contrat 6 : Souk Jdid / commercial', bdContratOne(6)['quartier'] === 'Souk Jdid' && bdContratOne(6)['type_activite'] === 'commercial');
$verif('contrat inconnu : tableau vide', bdContratOne(999) === []);

$verif('contrat 6 appartient au client 1', bdContratAppartient(6, 1) === true);
$verif('contrat 6 n\'appartient pas au client 2', bdContratAppartient(6, 2) === false);

$verif('contrat 1 est actif', bdContratEstActif(1) === true);
$verif('contrat 7 (résilié) n\'est pas actif', bdContratEstActif(7) === false);
$verif('Mouad : 2 contrats, 1 seul actif', count(bdContratsByClient(5)) === 2 && count(bdContratsActifsByClient(5)) === 1);

$t = bdContratTranches(1);
$verif('contrat 1 (résidentiel) : 3 tranches élec, 4 tranches eau', count($t['elec']) === 3 && count($t['eau']) === 4);
$verif('contrat 1 : eau 7 à 20 m³ = 7,39 DH', $t['eau'][1] === [20.0, 7.39]);
$verif('dernière tranche sans limite (null)', $t['eau'][3][0] === null);
$verif('contrat 6 (commercial) a un autre barème', bdContratTranches(6)['eau'][0] === [10.0, 3.2]);
$verif('seuils du contrat 7 (industriel) = 1000 m³ / 10000 kWh', bdContratSeuils(7) === ['seuil_eau' => 1000.0, 'seuil_elec' => 10000.0]);

// Suppression : un quartier avec des contrats est refusé, un quartier vide est supprimé
try { bdQuartierDelete(1); $refuse = false; } catch (PDOException $e) { $refuse = $e->getCode() === '23000'; }
$verif('suppression d\'un quartier avec contrats refusée (23000)', $refuse);
$id = bdQuartierInsert('Quartier de test', 1);
$verif('ajout puis suppression d\'un quartier vide', bdQuartierDelete($id) === 1);

printf("\n%d / %d tests réussis\n", $ok, $total);
exit($ok === $total ? 0 : 1);
