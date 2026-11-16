<?php
declare(strict_types=1);
/**
 * Traitement/Reclamation.php — Chaimaa Racile
 * Contrôles, sessions et redirections des réclamations. Pas de SQL (tout est dans BD/), pas de HTML (IHM/reclamation/).
 *
 * Routes (déclarées par Souraya dans index.php) :
 *  - Reclamation/add       client      : formulaire d'une nouvelle réclamation
 *  - Reclamation/all       client      : suivi de ses réclamations et des réponses
 *  - Reclamation/admin     fournisseur : liste de toutes les réclamations, filtre ?statut=
 *  - Reclamation/one/{id}  fournisseur : détail et réponse, puis notification du client
 *
 * Fonctions des autres membres utilisées (appelées, jamais modifiées) :
 *  - BD/Contrat.php         : bdContratsActifsByClient, bdContratAppartient, bdContratEstActif (Souraya)
 *  - BD/Facture.php         : bdFactureByClient, bdFactureOne (Farah)
 *  - Traitement/Notification.php : notificationAdd (Farah)
 */

/** Types de réclamation (valeurs de l'enum SQL => libellés). */
function reclamationTypes(): array
{
    return [
        'fuite_externe' => 'Fuite externe',
        'fuite_interne' => 'Fuite interne',
        'facture'       => 'Facture',
        'autre'         => 'Autre',
    ];
}

/** Statuts (valeurs de l'enum SQL => [libellé, classe du badge]). */
function reclamationStatuts(): array
{
    return [
        'en_attente' => ['En attente', 'badge-rouge'],
        'en_cours'   => ['En cours',   'badge-jaune'],
        'traitee'    => ['Traitée',    'badge-vert'],
    ];
}

/** Route Reclamation/add : un client envoie une réclamation. */
function reclamationAdd(): void
{
    verifierRole('client');

    $idClient = (int) $_SESSION['id'];
    $types    = reclamationTypes();
    $contrats = bdContratsActifsByClient($idClient);
    $factures = bdFactureByClient($idClient);
    $erreurs  = [];
    $old      = ['type' => '', 'autre_type' => '', 'description' => '', 'id_contrat' => '', 'id_facture' => ''];

    // Reclamation/add?id_facture=… pré-remplit le formulaire (la facture est revérifiée à l'envoi)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && ctype_digit((string) ($_GET['id_facture'] ?? ''))) {
        $old['type']       = 'facture';
        $old['id_facture'] = (string) $_GET['id_facture'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($old as $cle => $_) {
            $old[$cle] = trim((string) ($_POST[$cle] ?? ''));
        }
        $type       = $old['type'];
        $autreType  = null;
        $idContrat  = null;
        $idFacture  = null;

        if (!isset($types[$type])) {
            $erreurs['type'] = 'Choisissez le type de réclamation.';
        }

        if ($type === 'autre') {
            if ($old['autre_type'] === '') {
                $erreurs['autre_type'] = 'Précisez l’objet de la réclamation.';
            } elseif (mb_strlen($old['autre_type']) > 100) {
                $erreurs['autre_type'] = '100 caractères au maximum.';
            } else {
                $autreType = $old['autre_type'];
            }
        }

        // Une fuite concerne un contrat actif du client
        if ($type === 'fuite_externe' || $type === 'fuite_interne') {
            $id = ctype_digit($old['id_contrat']) ? (int) $old['id_contrat'] : 0;
            if ($id === 0) {
                $erreurs['id_contrat'] = 'Choisissez le contrat concerné par la fuite.';
            } elseif (!bdContratAppartient($id, $idClient) || !bdContratEstActif($id)) {
                $erreurs['id_contrat'] = 'Choisissez un de vos contrats actifs.';
            } else {
                $idContrat = $id;
            }
        }

        // Une réclamation sur facture concerne une facture du client
        if ($type === 'facture') {
            $id = ctype_digit($old['id_facture']) ? (int) $old['id_facture'] : 0;
            $f  = $id > 0 ? bdFactureOne($id) : [];
            if ($id === 0) {
                $erreurs['id_facture'] = 'Choisissez la facture concernée.';
            } elseif (!$f || (int) $f['id_client'] !== $idClient) {
                $erreurs['id_facture'] = 'Choisissez une de vos factures.';
            } else {
                $idFacture = $id;
                $idContrat = !empty($f['id_contrat']) ? (int) $f['id_contrat'] : null;
            }
        }

        $longueur = mb_strlen($old['description']);
        if ($longueur < 10) {
            $erreurs['description'] = 'Décrivez le problème (10 caractères au minimum).';
        } elseif ($longueur > 2000) {
            $erreurs['description'] = '2000 caractères au maximum.';
        }

        if (!$erreurs) {
            bdReclamationInsert($idClient, $type, $autreType, $old['description'], $idFacture, $idContrat);
            rediriger('Reclamation/all', 'Réclamation envoyée. Vous serez notifié de la réponse.');
        }
    }

    afficherVue('reclamation/form_add.php', [
        'titre'    => 'Nouvelle réclamation',
        'types'    => $types,
        'contrats' => $contrats,
        'factures' => $factures,
        'old'      => $old,
        'erreurs'  => $erreurs,
    ]);
}

/** Route Reclamation/all : suivi des réclamations du client connecté. */
function reclamationAll(): void
{
    verifierRole('client');

    afficherVue('reclamation/index.php', [
        'titre'        => 'Mes réclamations',
        'reclamations' => bdReclamationByClient((int) $_SESSION['id']),
        'types'        => reclamationTypes(),
        'statuts'      => reclamationStatuts(),
    ]);
}

/** Route Reclamation/admin : toutes les réclamations, filtre ?statut=en_attente|en_cours|traitee. */
function reclamationAdmin(): void
{
    verifierRole('fournisseur');

    $statuts = reclamationStatuts();
    $statut  = $_GET['statut'] ?? null;
    if (!is_string($statut) || !isset($statuts[$statut])) {
        $statut = null;
    }

    afficherVue('reclamation/index_fournisseur.php', [
        'titre'        => 'Réclamations',
        'reclamations' => bdReclamationAll($statut),
        'compte'       => bdReclamationCompterParStatut(),
        'statut'       => $statut,
        'types'        => reclamationTypes(),
        'statuts'      => $statuts,
    ]);
}

/** Route Reclamation/one/{id} : détail d'une réclamation et réponse du fournisseur. */
function reclamationOne(int $id): void
{
    verifierRole('fournisseur');

    $reclamation = bdReclamationOne($id);
    if (!$reclamation) {
        afficher404();
    }

    $erreurs = [];
    $old     = ['statut' => 'traitee', 'reponse' => (string) ($reclamation['reponse'] ?? '')];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($reclamation['statut'] === 'traitee') {
            rediriger('Reclamation/one/' . $id, 'Cette réclamation est déjà traitée.', 'erreur');
        }

        $old['statut']  = (string) ($_POST['statut'] ?? '');
        $old['reponse'] = trim((string) ($_POST['reponse'] ?? ''));

        if (!in_array($old['statut'], ['en_cours', 'traitee'], true)) {
            $erreurs['statut'] = 'Statut invalide.';
        }
        $longueur = mb_strlen($old['reponse']);
        if ($longueur === 0) {
            $erreurs['reponse'] = 'Écrivez une réponse au client.';
        } elseif ($longueur > 2000) {
            $erreurs['reponse'] = '2000 caractères au maximum.';
        }

        if (!$erreurs) {
            if (bdReclamationRepondre($id, $old['statut'], $old['reponse']) === 0) {
                rediriger('Reclamation/one/' . $id, 'Cette réclamation est déjà traitée.', 'erreur');
            }

            $message = $old['statut'] === 'traitee'
                ? "Votre réclamation n° $id a été traitée. Consultez la réponse dans « Réclamations »."
                : "Votre réclamation n° $id est en cours de traitement.";
            notificationAdd((int) $reclamation['id_client'], $message, $id);

            rediriger('Reclamation/admin', 'Réponse enregistrée, le client a été notifié.');
        }
    }

    afficherVue('reclamation/one.php', [
        'titre'       => 'Réclamation n° ' . $id,
        'reclamation' => $reclamation,
        'types'       => reclamationTypes(),
        'statuts'     => reclamationStatuts(),
        'old'         => $old,
        'erreurs'     => $erreurs,
    ]);
}
