<?php

const CONSO_PHOTO_MAX = 5 * 1024 * 1024;           
const CONSO_INDEX_MAX = 99999999.99;               
const CONSO_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png'];


function consoNomMois(int $mois): string
{
    $noms = [1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet',
             'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    return $noms[$mois] ?? '';
}

function consoPeriodeOuverte(): bool
{
    if (defined('SAISIE_TOUJOURS_OUVERTE') && SAISIE_TOUJOURS_OUVERTE) {
        return true;
    }
    return (int) date('j') >= 18;
}

function consoNombre(mixed $valeur): ?float
{
    $v = str_replace([' ', ','], ['', '.'], trim((string) $valeur));
    if ($v === '' || !is_numeric($v)) {
        return null;
    }
    $n = (float) $v;
    return ($n < 0 || $n > CONSO_INDEX_MAX) ? null : round($n, 2);
}


function consoDetecterAnomalies(array $prec, float $indexEau, float $indexElec, array $param): array
{
    $compteurs = [
        'eau'         => [(float) $prec['index_eau'],  $indexEau,  (float) $param['seuil_eau']],
        'electricite' => [(float) $prec['index_elec'], $indexElec, (float) $param['seuil_elec']],
    ];

    $anomalies = [];
    foreach ($compteurs as $service => [$precedent, $saisi, $seuil]) {
        $conso = round($saisi - $precedent, 2);

        if ($conso < 0) {
            $type = 'index_inferieur';
        } elseif ($conso == 0) {
            $type = 'consommation_nulle';
        } elseif ($conso > $seuil) {
            $type = 'depasse_seuil';
        } else {
            continue;
        }

        $anomalies[] = [
            'service'         => $service,
            'type'            => $type,
            'index_precedent' => $precedent,
            'index_saisi'     => $saisi,
        ];
    }
    return $anomalies;
}

function consoPhotoErreur(?array $f, string $nom): string
{
    if ($f === null || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return "La photo du compteur $nom est obligatoire.";
    }
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > CONSO_PHOTO_MAX) {
        return "La photo du compteur $nom dépasse 5 Mo.";
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return "La photo du compteur $nom n'a pas pu être envoyée.";
    }
    if (!isset(CONSO_MIME[mime_content_type($f['tmp_name'])])) {
        return "La photo du compteur $nom doit être au format JPG ou PNG.";
    }
    return '';
}

function consoPhotoEnregistrer(array $f, int $idClient, int $annee, int $mois, string $suffixe): ?string
{
    $dossier = dirname(__DIR__) . '/uploads/';
    if (!is_dir($dossier) && !mkdir($dossier, 0755, true)) {
        return null;
    }
    $ext = CONSO_MIME[mime_content_type($f['tmp_name'])];
    $nom = sprintf('c%d_%d_%02d_%s_%s.%s', $idClient, $annee, $mois, $suffixe, uniqid(), $ext);

    return move_uploaded_file($f['tmp_name'], $dossier . $nom) ? 'uploads/' . $nom : null;
}

function consoPhotoSupprimer(?string $chemin): void
{
    if ($chemin !== null && is_file(dirname(__DIR__) . '/' . $chemin)) {
        unlink(dirname(__DIR__) . '/' . $chemin);
    }
}


function consoAdd(): void
{
    verifierRole('client');

    $idClient  = (int) $_SESSION['id'];
    $mois      = (int) date('n');         
    $annee     = (int) date('Y');
    $ouverte   = consoPeriodeOuverte();
    $dejaSaisi = bdConsoExiste($idClient, $mois, $annee);
    $prec      = bdConsoPrecedente($idClient, $mois, $annee);  
    $param     = bdParametreGet();
    $erreurs   = [];
    $old       = ['index_eau' => '', 'index_elec' => ''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$ouverte) {
            $erreurs[] = 'La saisie est fermée : elle est ouverte à partir du 18 de chaque mois.';
        } elseif ($dejaSaisi) {
            $erreurs[] = 'Vous avez déjà saisi votre consommation pour ce mois';
        } else {
            $old['index_eau']  = trim((string) ($_POST['index_eau']  ?? ''));
            $old['index_elec'] = trim((string) ($_POST['index_elec'] ?? ''));
            $indexEau  = consoNombre($old['index_eau']);
            $indexElec = consoNombre($old['index_elec']);

            if ($indexEau === null) {
                $erreurs[] = "L'index d'eau doit être un nombre positif.";
            }
            if ($indexElec === null) {
                $erreurs[] = "L'index d'électricité doit être un nombre positif.";
            }
            foreach (['eau' => 'd\'eau', 'elec' => "d'électricité"] as $cle => $nom) {
                if ($e = consoPhotoErreur($_FILES['photo_' . $cle] ?? null, $nom)) {
                    $erreurs[] = $e;
                }
            }

            if (!$erreurs) {
                $photoEau  = consoPhotoEnregistrer($_FILES['photo_eau'],  $idClient, $annee, $mois, 'eau');
                $photoElec = consoPhotoEnregistrer($_FILES['photo_elec'], $idClient, $annee, $mois, 'elec');

                if ($photoEau === null || $photoElec === null) {
                    consoPhotoSupprimer($photoEau);
                    consoPhotoSupprimer($photoElec);
                    $erreurs[] = "Les photos n'ont pas pu être enregistrées. Réessayez.";
                } else {
                    $anomalies = consoDetecterAnomalies($prec, $indexEau, $indexElec, $param);

                    try {
                        $idConso = bdConsoEnregistrer([
                            'id_client'  => $idClient,
                            'mois'       => $mois,
                            'annee'      => $annee,
                            'index_eau'  => $indexEau,
                            'index_elec' => $indexElec,
                            'photo_eau'  => $photoEau,
                            'photo_elec' => $photoElec,
                            'statut'     => $anomalies ? 'anomalie' : 'normale',
                        ], $anomalies);
                    } catch (PDOException $ex) {
                        consoPhotoSupprimer($photoEau);
                        consoPhotoSupprimer($photoElec);
                        $erreurs[] = ($ex->getCode() === '23000')
                            ? 'Vous avez déjà saisi votre consommation pour ce mois'
                            : "Erreur lors de l'enregistrement. Réessayez.";
                        $idConso = 0;
                    }

                    if ($idConso > 0) {
                        if ($anomalies) {
                            rediriger('Consommation/all', 'Votre saisie est en attente de vérification par le fournisseur');
                            return;
                        }
                        try {
                            $idFacture = factureGenerer($idConso);
                        } catch (Throwable $ex) {
                            rediriger('Consommation/all', "Saisie enregistrée, mais la facture n'a pas pu être générée.", 'erreur');
                            return;
                        }
                        rediriger('Facture/one/' . $idFacture, 'Votre facture a été générée');
                        return;
                    }
                }
            }
        }
    }

    afficherVue('consommation/form_add.php', [
        'ouverte'     => $ouverte,
        'dejaSaisi'   => $dejaSaisi,
        'mois'        => $mois,
        'annee'       => $annee,
        'nomMois'     => consoNomMois($mois),
        'dernierJour' => (int) date('t'),
        'prec'        => $prec,
        'nomMoisPrec' => $prec['mois'] ? consoNomMois($prec['mois']) : '',
        'param'       => $param,
        'erreurs'     => $erreurs,
        'old'         => $old,
    ]);
}


function consoAll(): void
{
    verifierRole('client');
    $idClient = (int) $_SESSION['id'];

    $libStatut = [
        'normale'  => ['Normale',         'badge-vert'],
        'anomalie' => ['En vérification', 'badge-jaune'],
        'corrigee' => ['Corrigée',        'badge-vert'],
    ];
    $libType = [
        'index_inferieur'    => 'Index inférieur',
        'consommation_nulle' => 'Consommation nulle',
        'depasse_seuil'      => 'Dépasse le seuil',
    ];
    $libService = ['eau' => 'Eau', 'electricite' => 'Électricité'];

    $motifs = [];
    foreach (bdAnomalieByClient($idClient) as $a) {
        $motifs[$a['id_consommation']][] =
            ($libService[$a['service']] ?? $a['service']) . ' : ' . ($libType[$a['type']] ?? $a['type']);
    }

    $ref = bdConsoIndexInitial($idClient);
    $prevEau  = $ref['index_eau'];
    $prevElec = $ref['index_elec'];

    $historique = [];
    foreach (bdConsoByClient($idClient) as $l) {          // du plus ancien au plus récent
        $eau  = (float) ($l['eau_corrige']  ?? $l['index_eau']);
        $elec = (float) ($l['elec_corrige'] ?? $l['index_elec']);
        [$lib, $classe] = $libStatut[$l['statut']] ?? [$l['statut'], ''];

        $historique[] = [
            'periode'     => consoNomMois((int) $l['mois']) . ' ' . $l['annee'],
            'index_eau'   => $eau,
            'index_elec'  => $elec,
            'conso_eau'   => round($eau  - $prevEau, 2),
            'conso_elec'  => round($elec - $prevElec, 2),
            'statut'      => $lib,
            'badge'       => $classe,
            'motifs'      => $motifs[$l['id']] ?? [],
        ];

        if ($l['statut'] !== 'anomalie') {                 
            $prevEau  = $eau;
            $prevElec = $elec;
        }
    }

    afficherVue('consommation/index.php', ['historique' => array_reverse($historique)]);
}
