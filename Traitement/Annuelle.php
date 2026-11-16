<?php
declare(strict_types=1);
/**
 * Traitement/Annuelle.php — relevé annuel de l'agent : import automatique + vérification.
 *
 * Format du fichier depot/Consommation_Annuelle.txt (une ligne par contrat, séparateur « ; ») :
 *   ID_Contrat;ID_Agent;ConsommationEau;ConsommationElect;Annee;Date_Saisie
 *   1;1;14;110;2026;2026-12-20          (date AAAA-MM-JJ ou JJ/MM/AAAA ; ligne d'en-tête et lignes # ignorées)
 * (adapté à notre base : l'énoncé donne ID_Client, mais avec les contrats la consommation se relève par contrat.)
 */

const ANNUELLE_DOSSIER   = __DIR__ . '/../depot/';
const ANNUELLE_FICHIER   = 'Consommation_Annuelle.txt';
const ANNUELLE_TOLERANCE = 0.05;   // écart relatif toléré entre relevé annuel et somme des saisies mensuelles

/**
 * Importe depot/Consommation_Annuelle.txt s'il existe, puis l'archive dans depot/traites/.
 * Renvoie null s'il n'y a pas de fichier, sinon ['importes' => n, 'ignores' => n, 'erreurs' => [...]].
 * Appelée automatiquement à l'ouverture du tableau de bord et de la page Relevé annuel,
 * et par dev/import_annuel.php (tâche planifiée).
 */
function annuelleImporterAuto(): ?array
{
    $chemin = ANNUELLE_DOSSIER . ANNUELLE_FICHIER;
    if (!is_file($chemin)) {
        return null;
    }

    $rapport = ['importes' => 0, 'ignores' => 0, 'erreurs' => []];
    $lignes = file($chemin, FILE_IGNORE_NEW_LINES);
    if ($lignes === false) {
        $rapport['erreurs'][] = 'Le fichier annuel existe mais ne peut pas être lu.';
        return $rapport;
    }

    foreach ($lignes as $i => $ligne) {
        $ligne = trim(preg_replace('/^\xEF\xBB\xBF/', '', $ligne));
        if ($ligne === '' || $ligne[0] === '#') {
            continue;
        }
        $sep    = str_contains($ligne, ';') ? ';' : ',';
        $champs = array_map('trim', explode($sep, $ligne));
        if ($i === 0 && !ctype_digit($champs[0])) {
            continue;   // ligne d'en-tête
        }

        $erreur = annuelleImporterLigne($champs, $ignore);
        if ($erreur !== '') {
            $rapport['erreurs'][] = 'Ligne ' . ($i + 1) . ' : ' . $erreur;
        } elseif ($ignore) {
            $rapport['ignores']++;
        } else {
            $rapport['importes']++;
        }
    }

    $archive = ANNUELLE_DOSSIER . 'traites/';
    if (!is_dir($archive) && !mkdir($archive, 0755, true) && !is_dir($archive)) {
        $rapport['erreurs'][] = 'Le dossier d’archivage des relevés annuels ne peut pas être créé.';
        return $rapport;
    }
    if (!rename($chemin, $archive . 'Consommation_Annuelle_' . date('Ymd_His') . '.txt')) {
        $rapport['erreurs'][] = 'Le relevé a été traité mais ne peut pas être archivé.';
    }

    return $rapport;
}

/** Valide et insère une ligne. Renvoie un message d'erreur ('' si OK) ; $ignore = true si déjà importée. */
function annuelleImporterLigne(array $c, ?bool &$ignore = false): string
{
    $ignore = false;
    if (count($c) !== 6) {
        return '6 champs attendus (ID_Contrat;ID_Agent;ConsommationEau;ConsommationElect;Annee;Date_Saisie).';
    }
    [$idContrat, $idAgent, $eau, $elec, $annee, $date] = $c;

    if (!ctype_digit($idContrat) || !($contrat = bdContratOne((int) $idContrat))) {
        return 'contrat « ' . $idContrat . ' » introuvable.';
    }
    if (!ctype_digit($idAgent) || !bdAgentExiste((int) $idAgent)) {
        return 'agent « ' . $idAgent . ' » introuvable.';
    }
    $eau  = nombrePositif($eau);
    $elec = nombrePositif($elec);
    if ($eau === null || $elec === null) {
        return 'les consommations doivent être des nombres positifs.';
    }
    if (!ctype_digit($annee) || (int) $annee < 2000 || (int) $annee > 2100) {
        return 'année invalide.';
    }
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date, $m)) {
        $date = "$m[3]-$m[2]-$m[1]";
    }
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return 'date invalide (AAAA-MM-JJ ou JJ/MM/AAAA).';
    }
    if (bdAnnuelleExiste((int) $idContrat, (int) $annee)) {
        $ignore = true;   // déjà importé : on ne l'écrase pas
        return '';
    }

    bdAnnuelleInsert((int) $idContrat, (int) $contrat['id_client'], (int) $idAgent, $eau, $elec, (int) $annee, $date);
    return '';
}

/** Nombre positif (virgule ou point acceptés), arrondi à 2 décimales ; null si invalide. */
function nombrePositif(mixed $valeur): ?float
{
    $v = str_replace([' ', ','], ['', '.'], trim((string) $valeur));
    if ($v === '' || !is_numeric($v) || (float) $v < 0 || (float) $v > 99999999.99) {
        return null;
    }
    return round((float) $v, 2);
}

/** Message de synthèse d'un import. */
function annuelleMessage(array $r): array
{
    $msg  = $r['importes'] . ' relevé(s) importé(s)';
    $msg .= $r['ignores'] ? ', ' . $r['ignores'] . ' déjà présent(s) ignoré(s)' : '';
    $msg .= $r['erreurs'] ? ', ' . count($r['erreurs']) . ' ligne(s) en erreur' : '';
    return [$msg . '.', $r['erreurs'] ? 'erreur' : 'succes'];
}

// Route Annuelle/import — import automatique du fichier déposé + dépôt manuel + liste des relevés.
function annuelleImport(): void
{
    verifierRole('fournisseur');

    $rapport = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $f = $_FILES['fichier'] ?? null;
        if ($f === null || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            rediriger('Annuelle/import', 'Le fichier n’a pas pu être envoyé.', 'erreur');
        }
        if (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'txt' || $f['size'] > 1024 * 1024) {
            rediriger('Annuelle/import', 'Déposez un fichier .txt de 1 Mo maximum.', 'erreur');
        }
        if (!is_dir(ANNUELLE_DOSSIER)) {
            mkdir(ANNUELLE_DOSSIER, 0755, true);
        }
        if (!move_uploaded_file($f['tmp_name'], ANNUELLE_DOSSIER . ANNUELLE_FICHIER)) {
            rediriger('Annuelle/import', 'Le fichier n’a pas pu être enregistré dans le dépôt.', 'erreur');
        }
    }

    $rapport = annuelleImporterAuto();   // import AUTOMATIQUE : dès qu'un fichier est présent
    if ($rapport !== null) {
        $_SESSION['rapport_import'] = $rapport;
        [$msg, $type] = annuelleMessage($rapport);
        $_SESSION['flash'] = ['message' => $msg, 'type' => $type];
    }

    $annee = isset($_GET['annee']) && ctype_digit((string) $_GET['annee']) ? (int) $_GET['annee'] : (int) date('Y');
    afficherVue('annuelle/import.php', [
        'titre'    => 'Relevé annuel',
        'annee'    => $annee,
        'releves'  => bdAnnuelleByAnnee($annee),
        'rapport'  => $_SESSION['rapport_import'] ?? null,
        'chemin'   => 'depot/' . ANNUELLE_FICHIER,
    ]);
    unset($_SESSION['rapport_import']);
}

/**
 * Route Annuelle/verification?annee=AAAA — compare, pour chaque contrat, la consommation annuelle relevée
 * par l'agent à la somme des consommations mensuelles saisies par le client.
 */
function annuelleVerification(): void
{
    verifierRole('fournisseur');

    $annee = isset($_GET['annee']) && ctype_digit((string) $_GET['annee']) ? (int) $_GET['annee'] : (int) date('Y');
    $lignes = [];
    $nbEcarts = 0;

    foreach (bdAnnuelleContratsVerif($annee) as $c) {
        $saisies = bdAnnuelleSaisies((int) $c['id_contrat'], $annee);

        // Index de départ = dernier index des années précédentes, sinon l'index initial du contrat
        $base = ['eau' => (float) $c['index_initial_eau'], 'elec' => (float) $c['index_initial_elec']];
        $fin  = null;
        $nbMois = 0;
        foreach ($saisies as $s) {
            if ((int) $s['annee'] < $annee) {
                $base = ['eau' => (float) $s['index_eau'], 'elec' => (float) $s['index_elec']];
            } else {
                $fin = ['eau' => (float) $s['index_eau'], 'elec' => (float) $s['index_elec']];
                $nbMois++;
            }
        }
        $mensuelEau  = $fin ? round($fin['eau']  - $base['eau'],  2) : null;
        $mensuelElec = $fin ? round($fin['elec'] - $base['elec'], 2) : null;
        $annuelEau   = $c['annuel_eau']  === null ? null : (float) $c['annuel_eau'];
        $annuelElec  = $c['annuel_elec'] === null ? null : (float) $c['annuel_elec'];

        if ($annuelEau === null) {
            $statut = 'pas_de_releve';
        } elseif ($mensuelEau === null) {
            $statut = 'pas_de_saisie';
        } else {
            $ecart = fn(float $a, float $m) => abs($a - $m) <= ANNUELLE_TOLERANCE * max(abs($a), abs($m), 1);
            $statut = ($ecart($annuelEau, $mensuelEau) && $ecart($annuelElec, $mensuelElec)) ? 'conforme' : 'ecart';
        }
        $nbEcarts += $statut === 'ecart' ? 1 : 0;

        $lignes[] = $c + [
            'nb_mois' => $nbMois, 'mensuel_eau' => $mensuelEau, 'mensuel_elec' => $mensuelElec,
            'statut_verif' => $statut,
        ];
    }

    afficherVue('annuelle/verification.php', [
        'titre'    => 'Vérification annuelle',
        'annee'    => $annee,
        'lignes'   => $lignes,
        'nbEcarts' => $nbEcarts,
        'tolerance' => (int) (ANNUELLE_TOLERANCE * 100),
    ]);
}
