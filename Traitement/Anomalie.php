<?php

/**
 * Traitement/Anomalie.php — Amal
 * Traitement des anomalies de saisie par le fournisseur (routes Anomalie/all et Anomalie/one/{id}).
 * Une anomalie met la saisie en attente : le fournisseur vérifie (photo du compteur), corrige l'index si besoin,
 * puis la facture est générée par factureGenerer($idConso) (signature inchangée, Farah).
 * Anomalie/one/{id} : {id} est l'id de la SAISIE (consommation) — une saisie peut avoir deux anomalies (eau + élec.)
 * et on ne génère la facture que quand toutes sont traitées.
 */

const ANOMALIE_LIB_TYPE = [
    'index_inferieur'    => 'Index inférieur au précédent',
    'consommation_nulle' => 'Consommation nulle',
    'depasse_seuil'      => 'Dépasse le seuil',
];
const ANOMALIE_LIB_SERVICE = ['eau' => 'Eau', 'electricite' => 'Électricité'];
const ANOMALIE_UNITE       = ['eau' => 'm³', 'electricite' => 'kWh'];

/**
 * Contrôle l'index corrigé saisi par le fournisseur ($index = résultat de consoNombre(), null si invalide).
 * Renvoie '' si c'est valable, sinon le message d'erreur. L'index corrigé peut être égal à l'index précédent
 * (consommation nulle confirmée) mais pas inférieur.
 */
function anomalieErreurIndex(array $anomalie, ?float $index): string
{
    if ($index === null) {
        return "L'index corrigé doit être un nombre positif.";
    }
    if ($index < (float) $anomalie['index_precedent']) {
        return "L'index corrigé ne peut pas être inférieur à l'index précédent ("
             . number_format((float) $anomalie['index_precedent'], 2, ',', ' ') . ').';
    }
    return '';
}

/** Route Anomalie/all — les saisies en anomalie (à traiter d'abord). */
function anomalieAll(): void
{
    verifierRole('fournisseur');

    $saisies = [];                                   // regroupées par saisie (une saisie peut avoir 2 anomalies)
    foreach (bdAnomalieToutes() as $a) {
        $id = (int) $a['id_consommation'];
        if (!isset($saisies[$id])) {
            $saisies[$id] = [
                'id'        => $id,
                'client'    => $a['prenom'] . ' ' . $a['nom'],
                'contrat'   => (int) $a['id_contrat'],
                'quartier'  => $a['quartier'],
                'type'      => $a['type_activite'],
                'periode'   => consoNomMois((int) $a['mois']) . ' ' . $a['annee'],
                'a_traiter' => 0,
                'anomalies' => [],
            ];
        }
        $saisies[$id]['anomalies'][] = ANOMALIE_LIB_SERVICE[$a['service']] . ' : ' . ANOMALIE_LIB_TYPE[$a['type']];
        if ($a['statut_anomalie'] === 'a_traiter') {
            $saisies[$id]['a_traiter']++;
        }
    }
    // à traiter d'abord (le tri SQL est conservé à l'intérieur de chaque groupe)
    uasort($saisies, fn($x, $y) => ($y['a_traiter'] > 0) <=> ($x['a_traiter'] > 0));

    afficherVue('anomalie/index.php', [
        'titre'    => 'Anomalies',
        'saisies'  => array_values($saisies),
        'aTraiter' => count(array_filter($saisies, fn($s) => $s['a_traiter'] > 0)),
    ]);
}

/** Route Anomalie/one/{id} — détail d'une saisie en anomalie, correction des index, génération de la facture. */
function anomalieOne(int $id): void
{
    verifierRole('fournisseur');

    $saisie    = bdAnomalieSaisieOne($id);
    $anomalies = $saisie ? bdAnomalieByConso($id) : [];
    if (!$anomalies) {
        afficher404();                                // saisie inconnue ou sans anomalie
    }

    $erreurs = [];
    $old     = [];
    foreach ($anomalies as $a) {
        $old[$a['id']] = (string) (float) $a['index_saisi'];
    }
    $reste = array_filter($anomalies, fn($a) => $a['statut'] === 'a_traiter');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $corrections = [];
        foreach ($reste as $a) {
            $brut = trim((string) ($_POST['index_' . $a['id']] ?? ''));
            $old[$a['id']] = $brut;
            $index  = consoNombre($brut);
            $erreur = anomalieErreurIndex($a, $index);
            if ($erreur !== '') {
                $erreurs[] = ANOMALIE_LIB_SERVICE[$a['service']] . ' : ' . $erreur;
            } else {
                $corrections[(int) $a['id']] = $index;
            }
        }

        if (!$erreurs) {
            // Rien à corriger (déjà traitée) : on tente seulement de générer la facture manquante.
            $toutCorrige = $reste ? bdAnomalieCorriger($id, $corrections) : true;

            if (!$toutCorrige) {
                rediriger('Anomalie/one/' . $id, 'Correction enregistrée. Il reste une anomalie à traiter.');
            }
            try {
                factureGenerer($id);                  // idempotent : renvoie la facture existante s'il y en a déjà une
            } catch (Throwable $ex) {
                rediriger('Anomalie/one/' . $id, "Index corrigé, mais la facture n'a pas pu être générée. Réessayez.", 'erreur');
            }
            if (function_exists('notificationAdd')) {   // module de Farah : prévient le client
                try {
                    notificationAdd((int) $saisie['id_client'], 'Votre saisie de ' . consoNomMois((int) $saisie['mois'])
                        . ' ' . $saisie['annee'] . ' (contrat n°' . $saisie['id_contrat'] . ') a été vérifiée : votre facture est disponible.');
                } catch (Throwable $ex) {
                    // la notification est un plus : elle ne doit jamais bloquer la correction
                }
            }
            rediriger('Anomalie/all', 'Anomalie corrigée : la facture a été générée.');
        }
    }

    afficherVue('anomalie/one.php', [
        'titre'     => 'Anomalie — saisie n°' . $id,
        'saisie'    => $saisie,
        'anomalies' => $anomalies,
        'reste'     => $reste,
        'idFacture' => $reste ? null : bdFactureIdByConso($id),   // saisie déjà corrigée : facture déjà générée ?
        'periode'   => consoNomMois((int) $saisie['mois']) . ' ' . $saisie['annee'],
        'erreurs'   => $erreurs,
        'old'       => $old,
    ]);
}
