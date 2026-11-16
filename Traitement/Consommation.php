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

function consoPhotoEnregistrer(array $f, int $idContrat, int $annee, int $mois, string $suffixe): ?string
{
    $dossier = dirname(__DIR__) . '/uploads/';
    if (!is_dir($dossier) && !mkdir($dossier, 0755, true)) {
        return null;
    }
    $ext = CONSO_MIME[mime_content_type($f['tmp_name'])];
    $nom = sprintf('ct%d_%d_%02d_%s_%s.%s', $idContrat, $annee, $mois, $suffixe, uniqid(), $ext);

    return move_uploaded_file($f['tmp_name'], $dossier . $nom) ? 'uploads/' . $nom : null;
}

function consoPhotoSupprimer(?string $chemin): void
{
    if ($chemin !== null && is_file(dirname(__DIR__) . '/' . $chemin)) {
        unlink(dirname(__DIR__) . '/' . $chemin);
    }
}


/**
 * Contrat choisi pour la saisie : POST (champ caché) puis GET (?id_contrat=) puis, par défaut,
 * le premier contrat actif sans saisie ce mois-ci. Règle commune : appartenance (404 sinon)
 * ET contrat actif (un contrat résilié ne saisit plus).
 */
function consoContratChoisi(array $contratsActifs, int $idClient, int $mois, int $annee): int
{
    $brut = $_POST['id_contrat'] ?? $_GET['id_contrat'] ?? null;
    if ($brut !== null && $brut !== '') {
        $idValide = is_scalar($brut)
            ? filter_var($brut, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        if ($idValide === false) {
            afficher404();
        }
        $demande = (int) $idValide;
    } else {
        $demande = 0;
    }

    if ($demande > 0) {
        if (!bdContratAppartient($demande, $idClient)) {
            afficher404();
        }
        if (!bdContratEstActif($demande)) {
            rediriger('Consommation/add', "Ce contrat est résilié : la saisie n'est plus possible.", 'erreur');
        }
        return $demande;
    }

    foreach ($contratsActifs as $c) {
        if (!bdConsoExiste((int) $c['id'], $mois, $annee)) {
            return (int) $c['id'];
        }
    }
    return (int) $contratsActifs[0]['id'];
}

function consoAdd(): void
{
    verifierRole('client');

    $idClient = (int) $_SESSION['id'];
    $mois     = (int) date('n');
    $annee    = (int) date('Y');
    $ouverte  = consoPeriodeOuverte();
    $contrats = bdContratsActifsByClient($idClient);

    $vue = [
        'ouverte'     => $ouverte,
        'mois'        => $mois,
        'annee'       => $annee,
        'nomMois'     => consoNomMois($mois),
        'dernierJour' => (int) date('t'),
        'contrats'    => $contrats,
    ];

    if (!$contrats) {                       // aucun contrat actif : rien à saisir
        afficherVue('consommation/form_add.php', $vue + [
            'contrat' => [], 'saisiMois' => [], 'dejaSaisi' => false, 'prec' => [], 'param' => [],
            'tranches' => [], 'nomMoisPrec' => '', 'erreurs' => [], 'old' => [],
        ]);
        return;
    }

    $idContrat = consoContratChoisi($contrats, $idClient, $mois, $annee);
    $contrat   = bdContratOne($idContrat);
    $idContratClient = (int) $contrat['id_client'];     // = $idClient (vérifié par bdContratAppartient)

    $saisiMois = [];
    foreach ($contrats as $c) {
        $saisiMois[(int) $c['id']] = bdConsoExiste((int) $c['id'], $mois, $annee);
    }
    $dejaSaisi = $saisiMois[$idContrat] ?? bdConsoExiste($idContrat, $mois, $annee);
    $prec      = bdConsoPrecedente($idContrat, $mois, $annee);
    // Seuils du TYPE D'ACTIVITÉ du contrat ; la TVA reste celle de la table parametre.
    $param     = bdContratSeuils($idContrat) + ['taux_tva' => bdParametreGet()['taux_tva']];
    $erreurs   = [];
    $old       = ['index_eau' => '', 'index_elec' => ''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$contrats) {
            $erreurs[] = 'Aucun contrat actif ne permet de saisir une consommation.';
        } elseif ($contrat && !$ouverte) {
            $erreurs[] = 'La saisie est fermée : elle est ouverte à partir du 18 de chaque mois.';
        } elseif ($dejaSaisi) {
            $erreurs[] = 'Vous avez déjà saisi votre consommation pour ce mois (contrat n°' . $idContrat . ')';
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

            if (!$erreurs && (!$tranches['eau'] || !$tranches['elec'])) {
                $erreurs[] = 'Le barème de ce contrat est incomplet. Contactez le fournisseur.';
            }

            if (!$erreurs) {
                $photoEau  = consoPhotoEnregistrer($_FILES['photo_eau'],  $idContrat, $annee, $mois, 'eau');
                $photoElec = consoPhotoEnregistrer($_FILES['photo_elec'], $idContrat, $annee, $mois, 'elec');

                if ($photoEau === null || $photoElec === null) {
                    consoPhotoSupprimer($photoEau);
                    consoPhotoSupprimer($photoElec);
                    $erreurs[] = "Les photos n'ont pas pu être enregistrées. Réessayez.";
                } else {
                    $anomalies = consoDetecterAnomalies($prec, $indexEau, $indexElec, $param);

                    try {
                        $idConso = bdConsoEnregistrer([
                            'id_contrat' => $idContrat,
                            'id_client'  => $idContratClient,
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
                            ? 'Vous avez déjà saisi votre consommation pour ce mois (contrat n°' . $idContrat . ')'
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

    afficherVue('consommation/form_add.php', $vue + [
        'contrat'     => $contrat,
        'saisiMois'   => $saisiMois,
        'dejaSaisi'   => $dejaSaisi,
        'prec'        => $prec,
        'nomMoisPrec' => $prec['mois'] ? consoNomMois($prec['mois']) : '',
        'param'       => $param,
        'tranches'    => bdContratTranches($idContrat),   // barème du contrat, pour l'estimation JavaScript
        'erreurs'     => $erreurs,
        'old'         => $old,
    ]);
}


/** Route Consommation/all — historique de CHAQUE contrat du client (actifs ou résiliés). */
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

    $groupes = [];
    $total   = 0;
    foreach (bdContratsByClient($idClient) as $contrat) {
        $ref      = bdConsoIndexInitial((int) $contrat['id']);   // départ = index initial du contrat
        $prevEau  = $ref['index_eau'];
        $prevElec = $ref['index_elec'];

        $historique = [];
        foreach (bdConsoByContrat((int) $contrat['id']) as $l) {   // du plus ancien au plus récent
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

        $total   += count($historique);
        $groupes[] = ['contrat' => $contrat, 'historique' => array_reverse($historique)];
    }

    afficherVue('consommation/index.php', ['groupes' => $groupes, 'total' => $total]);
}
