<?php
declare(strict_types=1);

// Route Parametre/update — le fournisseur définit la TVA et les seuils d'anomalie de chaque type d'activité.
function parametreUpdate(): void
{
    verifierRole('fournisseur');

    $types   = bdTypeActiviteAll();
    $tvaPct  = (string) round(bdParametreGet()['taux_tva'] * 100, 2);
    $seuils  = [];
    foreach ($types as $t) {
        $seuils[(int) $t['id']] = ['eau' => (string) (float) $t['seuil_eau'], 'elec' => (string) (float) $t['seuil_elec']];
    }
    $erreurs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tvaPct = trim((string) ($_POST['tva'] ?? ''));
        $tva    = nombrePositif($tvaPct);
        if ($tva === null || $tva > 100 || abs($tva - round($tva)) > 0.001) {
            $erreurs[] = 'La TVA doit être un pourcentage entier entre 0 et 100.';
        }

        $nouveaux = [];
        foreach ($types as $t) {
            $id   = (int) $t['id'];
            $eau  = trim((string) ($_POST['seuil_eau'][$id]  ?? ''));
            $elec = trim((string) ($_POST['seuil_elec'][$id] ?? ''));
            $seuils[$id] = ['eau' => $eau, 'elec' => $elec];
            $n1 = nombrePositif($eau);
            $n2 = nombrePositif($elec);
            if ($n1 === null || $n1 <= 0 || $n2 === null || $n2 <= 0) {
                $erreurs[] = 'Les seuils du type « ' . $t['nom'] . ' » doivent être des nombres strictement positifs.';
            } else {
                $nouveaux[$id] = [$n1, $n2];
            }
        }

        if (!$erreurs) {
            bdParametreSetTva(round($tva / 100, 2));
            foreach ($nouveaux as $id => [$eau, $elec]) {
                bdTypeActiviteSetSeuils($id, $eau, $elec);
            }
            rediriger('Parametre/update', 'Les paramètres ont été enregistrés.');
        }
    }

    afficherVue('parametre/form.php', [
        'titre'   => 'Paramètres',
        'types'   => $types,
        'tvaPct'  => $tvaPct,
        'seuils'  => $seuils,
        'erreurs' => $erreurs,
    ]);
}
