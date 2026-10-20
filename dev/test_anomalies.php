<?php

require __DIR__ . '/../Traitement/Consommation.php';

$param = ['seuil_eau' => 50.0, 'seuil_elec' => 500.0, 'taux_tva' => 0.18];
$prec  = ['index_eau' => 1245.0, 'index_elec' => 8430.0];
$echecs = 0;

function verifier(string $nom, array $prec, float $eau, float $elec, array $param, array $attendu): void
{
    global $echecs;
    $res = array_map(fn($a) => $a['service'] . ':' . $a['type'], consoDetecterAnomalies($prec, $eau, $elec, $param));
    $ok = $res === $attendu;
    if (!$ok) { $echecs++; }
    printf("%-52s %s\n", $nom, $ok ? 'OK' : 'ECHEC  obtenu=' . json_encode($res));
}

verifier('Saisie normale (15 m3, 120 kWh)',            $prec, 1260,  8550, $param, []);
verifier('Eau : meme index que le mois dernier',       $prec, 1245,  8550, $param, ['eau:consommation_nulle']);
verifier('Elec : meme index que le mois dernier',      $prec, 1260,  8430, $param, ['electricite:consommation_nulle']);
verifier('Les deux compteurs a consommation nulle',    $prec, 1245,  8430, $param, ['eau:consommation_nulle', 'electricite:consommation_nulle']);
verifier('Eau : index inferieur',                      $prec, 1125,  8550, $param, ['eau:index_inferieur']);
verifier('Elec : index inferieur',                     $prec, 1260,  8000, $param, ['electricite:index_inferieur']);
verifier('Eau : 51 m3 depasse le seuil',               $prec, 1296,  8550, $param, ['eau:depasse_seuil']);
verifier('Eau : exactement 50 m3 = pas d\'anomalie',   $prec, 1295,  8550, $param, []);
verifier('Elec : 501 kWh depasse le seuil',            $prec, 1260,  8931, $param, ['electricite:depasse_seuil']);
verifier('Elec : exactement 500 kWh = pas d\'anomalie',$prec, 1260,  8930, $param, []);
verifier('Eau inferieure + elec au-dessus du seuil',   $prec, 1100,  9000, $param, ['eau:index_inferieur', 'electricite:depasse_seuil']);

foreach ([['1260', 1260.0], ['1 260,5', 1260.5], ['abc', null], ['-5', null], ['', null], ['0', 0.0]] as [$in, $out]) {
    $r = consoNombre($in);
    $ok = $r === $out;
    if (!$ok) { $echecs++; }
    printf("%-52s %s\n", "consoNombre('$in')", $ok ? 'OK' : 'ECHEC');
}

echo $echecs === 0 ? "\nTous les tests passent.\n" : "\n$echecs test(s) en echec.\n";
exit($echecs === 0 ? 0 : 1);
