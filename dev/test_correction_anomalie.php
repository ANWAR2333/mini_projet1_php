<?php
/**
 * dev/test_correction_anomalie.php — Amal
 * Vérifie sur la VRAIE base la correction d'une anomalie par le fournisseur (bdAnomalieCorriger) :
 * l'index corrigé remplace l'index saisi, la saisie passe à « corrigee » seulement quand TOUT est traité,
 * et l'index précédent du mois suivant est l'index corrigé. Utilise l'année 2032, nettoie ce qu'il crée.
 *   php dev/test_correction_anomalie.php
 */
require __DIR__ . '/../BD/Connexion.php';
require __DIR__ . '/../BD/Contrat.php';
require __DIR__ . '/../BD/Anomalie.php';
require __DIR__ . '/../BD/Consommation.php';

$pdo = getConnexion();
$ok = 0; $ko = 0;
function verif(string $nom, bool $condition): void
{
    global $ok, $ko;
    $condition ? $ok++ : $ko++;
    printf("%-3s %s\n", $condition ? 'OK' : 'KO', $nom);
}
function nettoyer(PDO $pdo): void
{
    $pdo->exec("DELETE FROM anomalie WHERE id_consommation IN (SELECT id FROM consommation WHERE annee = 2032)");
    $pdo->exec("DELETE FROM consommation WHERE annee = 2032");
}
nettoyer($pdo);

// Contrat 5 (Mouad) : precedent = 850 m3 / 2300 kWh (septembre 2026). Saisie de janvier 2032 avec 2 anomalies.
$idConso = bdConsoEnregistrer(
    ['id_contrat' => 5, 'id_client' => 5, 'mois' => 1, 'annee' => 2032, 'index_eau' => 800, 'index_elec' => 2300,
     'photo_eau' => null, 'photo_elec' => null, 'statut' => 'anomalie'],
    [['service' => 'eau',         'type' => 'index_inferieur',    'index_precedent' => 850.0,  'index_saisi' => 800.0],
     ['service' => 'electricite', 'type' => 'consommation_nulle', 'index_precedent' => 2300.0, 'index_saisi' => 2300.0]]
);
$a = bdAnomalieByConso($idConso);
verif('2 anomalies a traiter enregistrees', count($a) === 2 && $a[0]['statut'] === 'a_traiter' && $a[1]['statut'] === 'a_traiter');
verif('la saisie en anomalie n\'est pas retenue comme index precedent', bdConsoPrecedente(5, 2, 2032)['annee'] === 2026);
verif('la saisie apparait dans la liste du fournisseur',
      count(array_filter(bdAnomalieToutes(), fn($x) => (int) $x['id_consommation'] === $idConso)) === 2);
verif('bdAnomalieSaisieOne : client, contrat 5, seuils du type residentiel',
      ($s = bdAnomalieSaisieOne($idConso)) && (int) $s['id_contrat'] === 5 && $s['nom'] === 'Jemaoui' && (float) $s['seuil_eau'] === 50.0);

// 1) on corrige seulement l'eau : la saisie reste en anomalie
$tout = bdAnomalieCorriger($idConso, [(int) $a[0]['id'] => 860.0]);
verif('correction de l\'eau seule : il reste une anomalie (retour false)', $tout === false);
$c = bdConsoOne($idConso);
verif('l\'index d\'eau de la saisie est remplace par l\'index corrige, l\'index elec. est inchange',
      (float) $c['index_eau'] === 860.0 && (float) $c['index_elec'] === 2300.0);
verif('la saisie reste « anomalie » tant qu\'il reste une anomalie', $c['statut'] === 'anomalie');

// 2) on corrige l'electricite (en confirmant 2300 : consommation nulle reelle) : tout est traite
$tout = bdAnomalieCorriger($idConso, [(int) $a[1]['id'] => 2300.0]);
verif('tout est traite (retour true)', $tout === true);
$c = bdConsoOne($idConso);
verif('la saisie passe a « corrigee »', $c['statut'] === 'corrigee');
$a = bdAnomalieByConso($idConso);
verif('anomalies « corrigee » avec index_corrige et date_correction',
      $a[0]['statut'] === 'corrigee' && (float) $a[0]['index_corrige'] === 860.0 && $a[0]['date_correction'] !== null
      && (float) $a[0]['index_saisi'] === 800.0);   // l'index saisi par le client reste conserve

// 3) double envoi : rien ne change, rien ne casse
$tout = bdAnomalieCorriger($idConso, [(int) $a[0]['id'] => 999.0]);
verif('double envoi : ignore (index deja corrige inchange, retour true)',
      $tout === true && (float) bdConsoOne($idConso)['index_eau'] === 860.0);

verif('l\'index precedent du mois suivant est l\'index CORRIGE',
      ($p = bdConsoPrecedente(5, 2, 2032)) && $p['annee'] === 2032 && $p['mois'] === 1 && $p['index_eau'] == 860.0 && $p['index_elec'] == 2300.0);

// 4) une anomalie d'une AUTRE saisie ne peut pas etre corrigee par ce biais
$idAutre = bdConsoEnregistrer(
    ['id_contrat' => 5, 'id_client' => 5, 'mois' => 2, 'annee' => 2032, 'index_eau' => 10, 'index_elec' => 3000,
     'photo_eau' => null, 'photo_elec' => null, 'statut' => 'anomalie'],
    [['service' => 'eau', 'type' => 'index_inferieur', 'index_precedent' => 860.0, 'index_saisi' => 10.0]]
);
$idAnomalieAutre = (int) bdAnomalieByConso($idAutre)[0]['id'];
bdAnomalieCorriger($idConso, [$idAnomalieAutre => 900.0]);
verif('une anomalie d\'une autre saisie n\'est pas touchee',
      bdAnomalieByConso($idAutre)[0]['statut'] === 'a_traiter' && (float) bdConsoOne($idAutre)['index_eau'] === 10.0);

nettoyer($pdo);
printf("\n%d / %d tests reussis\n", $ok, $ok + $ko);
exit($ko === 0 ? 0 : 1);
