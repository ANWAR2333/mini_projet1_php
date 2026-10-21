<?php
declare(strict_types=1);

/** Route Paiement/add/{id} : enregistre le paiement d'une facture impayée. */
function paiementAdd(int $id): void
{
    verifierRole('fournisseur');

    $facture = bdFactureOne($id);
    if (!$facture) {
        afficher404();
    }
    if ($facture['statut'] === 'payee') {
        rediriger('Facture/admin', 'Cette facture est déjà payée', 'erreur');
    }

    $modes   = ['especes' => 'Espèces', 'carte' => 'Carte', 'virement' => 'Virement'];
    $date    = date('Y-m-d');
    $mode    = 'especes';
    $erreurs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $date = (string) ($_POST['date_paiement'] ?? '');
        $mode = (string) ($_POST['mode'] ?? '');

        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            $erreurs['date'] = 'Date invalide';
        } elseif ($date > date('Y-m-d')) {
            $erreurs['date'] = 'La date ne peut pas être dans le futur';
        }
        if (!isset($modes[$mode])) {
            $erreurs['mode'] = 'Mode de paiement invalide';
        }

        if (!$erreurs) {
            // Transaction : le paiement et le statut sont enregistrés ensemble, ou pas du tout.
            $pdo = getConnexion();
            $pdo->beginTransaction();
            try {
                if (!bdFactureSetStatut($id, 'payee')) {
                    $pdo->rollBack();
                    rediriger('Facture/admin', 'Cette facture est déjà payée', 'erreur');
                }
                bdPaiementInsert($id, $date, (float) $facture['montant_ttc'], $mode);
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
            rediriger('Facture/admin', 'Paiement enregistré');
        }
    }

    afficherVue('paiement/form_add.php', [
        'titre'   => 'Enregistrer un paiement',
        'facture' => $facture,
        'modes'   => $modes,
        'date'    => $date,
        'mode'    => $mode,
        'erreurs' => $erreurs,
    ]);
}