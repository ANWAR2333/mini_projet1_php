<?php
/**
 * dev/test_saisie_contrat.php — Amal
 * Vérifie sur la VRAIE base (après import de gestion_factures.sql) que la saisie fonctionne par contrat :
 * index précédent par contrat, une saisie par contrat et par mois, id_client = celui du contrat.
 * Utilise l'année 2031 (aucune donnée de test) et supprime tout ce qu'il crée.
 *   php dev/test_saisie_contrat.php
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
    $pdo->exec("DELETE FROM anomalie WHERE id_consommation IN (SELECT id FROM consommation WHERE annee = 2031)");
    $pdo->exec("DELETE FROM consommation WHERE annee = 2031");
}
nettoyer($pdo);

// Salma (client 1) : contrat 1 (residentiel, a une saisie de septembre 2026) et contrat 6 (commercial, aucune saisie).
verif('contrat 1 : l\'index precedent est la saisie de septembre 2026',
      ($p = bdConsoPrecedente(1, 11, 2026)) && $p['initial'] === false && $p['index_eau'] == 1245.0 && $p['index_elec'] == 8430.0);
verif('contrat 6 : sans saisie, l\'index precedent est l\'index INITIAL DU CONTRAT',
      ($p = bdConsoPrecedente(6, 11, 2026)) && $p['initial'] === true && $p['index_eau'] == 100.0 && $p['index_elec'] == 200.0);
verif('bdConsoIndexInitial lit le contrat', bdConsoIndexInitial(6) === ['index_eau' => 100.0, 'index_elec' => 200.0]);
verif('contrat 1 : saisie de septembre 2026 trouvee',  bdConsoExiste(1, 9, 2026));
verif('contrat 6 : pas de saisie en septembre 2026',  !bdConsoExiste(6, 9, 2026));

$base = ['id_client' => 1, 'mois' => 1, 'annee' => 2031, 'photo_eau' => null, 'photo_elec' => null, 'statut' => 'normale'];

$id6 = bdConsoEnregistrer($base + ['id_contrat' => 6, 'index_eau' => 115, 'index_elec' => 320], []);
verif('saisie du contrat 6 enregistree', $id6 > 0);
$ligne = bdConsoOne($id6);
verif('la ligne porte id_contrat = 6 et id_client = celui du contrat (1)', (int) $ligne['id_contrat'] === 6 && (int) $ligne['id_client'] === 1);
verif('contrat 6 : saisie detectee pour ce mois', bdConsoExiste(6, 1, 2031));
verif('contrat 1 : rien pour ce mois (autre contrat du MEME client)', !bdConsoExiste(1, 1, 2031));

$id1 = bdConsoEnregistrer($base + ['id_contrat' => 1, 'index_eau' => 1260, 'index_elec' => 8550], []);
verif('le meme client saisit le meme mois sur son autre contrat', $id1 > 0);

try {
    bdConsoEnregistrer($base + ['id_contrat' => 6, 'index_eau' => 130, 'index_elec' => 400], []);
    verif('2e saisie du meme contrat le meme mois refusee (R2)', false);
} catch (PDOException $e) {
    verif('2e saisie du meme contrat le meme mois refusee (R2, code 23000)', $e->getCode() === '23000');
}

verif('contrat 6 : l\'index precedent du mois suivant est la saisie de janvier 2031',
      ($p = bdConsoPrecedente(6, 2, 2031)) && $p['initial'] === false && $p['index_eau'] == 115.0 && $p['index_elec'] == 320.0);
verif('contrat 1 : l\'index precedent du mois suivant vient de SON historique',
      ($p = bdConsoPrecedente(1, 2, 2031)) && $p['index_eau'] == 1260.0 && $p['index_elec'] == 8550.0);

// Une saisie en anomalie ne devient pas « l'index precedent ».
$idA = bdConsoEnregistrer(['id_contrat' => 5, 'id_client' => 5, 'mois' => 2, 'annee' => 2031, 'index_eau' => 10, 'index_elec' => 10,
                           'photo_eau' => null, 'photo_elec' => null, 'statut' => 'anomalie'],
                          [['service' => 'eau', 'type' => 'index_inferieur', 'index_precedent' => 850.0, 'index_saisi' => 10.0]]);
verif('une saisie en anomalie n\'est pas retenue : l\'index precedent reste celui de septembre 2026',
      ($p = bdConsoPrecedente(5, 3, 2031)) && $p['index_eau'] == 850.0 && $p['index_elec'] == 2300.0 && $p['annee'] === 2026);
verif('l\'anomalie est enregistree avec la saisie', count(bdAnomalieByConso($idA)) === 1);

verif('seuils du contrat 6 = ceux du type commercial (200 / 2000)', bdContratSeuils(6) === ['seuil_eau' => 200.0, 'seuil_elec' => 2000.0]);
verif('seuils du contrat 1 = ceux du type residentiel (50 / 500)',   bdContratSeuils(1) === ['seuil_eau' => 50.0,  'seuil_elec' => 500.0]);

nettoyer($pdo);
printf("\n%d / %d tests reussis\n", $ok, $ok + $ko);
exit($ko === 0 ? 0 : 1);
