<?php
declare(strict_types=1);

// Route Quartier/all — liste des quartiers (fournisseur).
function quartierAll(): void
{
    verifierRole('fournisseur');
    afficherVue('quartier/index.php', [
        'titre'     => 'Quartiers',
        'quartiers' => bdQuartierAll(),
        'total'     => bdQuartierCount(),
    ]);
}

// Route Quartier/add — formulaire de création.
function quartierAdd(): void
{
    verifierRole('fournisseur');

    $donnees = ['nom' => '', 'id_type_activite' => ''];
    $erreurs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $donnees = quartierDonneesPostees();
        $erreurs = quartierValider($donnees);

        if (!$erreurs) {
            try {
                bdQuartierInsert($donnees['nom'], (int) $donnees['id_type_activite']);
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Un quartier porte déjà ce nom.';
            }
            if (!$erreurs) {
                rediriger('Quartier/all', 'Le quartier a été ajouté.');
            }
        }
    }

    afficherVue('quartier/form.php', [
        'titre'    => 'Ajouter un quartier',
        'quartier' => $donnees,
        'types'    => bdTypeActiviteAll(),
        'erreurs'  => $erreurs,
        'mode'     => 'add',
    ]);
}

// Route Quartier/update/{id} — formulaire de modification.
function quartierUpdate(int $id): void
{
    verifierRole('fournisseur');
    $quartier = bdQuartierOne($id);
    if (!$quartier) {
        afficher404();
    }

    $donnees = $quartier;
    $erreurs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $donnees = quartierDonneesPostees();
        $erreurs = quartierValider($donnees);

        if (!$erreurs) {
            try {
                bdQuartierUpdate($id, $donnees['nom'], (int) $donnees['id_type_activite']);
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Un quartier porte déjà ce nom.';
            }
            if (!$erreurs) {
                rediriger('Quartier/all', 'Le quartier a été modifié.');
            }
        }
    }

    $donnees['id'] = $id;
    afficherVue('quartier/form.php', [
        'titre'      => 'Modifier un quartier',
        'quartier'   => $donnees,
        'quartierId' => $id,
        'types'      => bdTypeActiviteAll(),
        'erreurs'    => $erreurs,
        'mode'       => 'update',
    ]);
}

// Route Quartier/delete/{id} — POST uniquement ; refusée si des contrats utilisent le quartier.
function quartierDelete(int $id): void
{
    verifierRole('fournisseur');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !bdQuartierOne($id)) {
        afficher404();
    }

    try {
        $supprime = bdQuartierDelete($id);
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') {
            throw $e;
        }
        rediriger('Quartier/all', 'Ce quartier ne peut pas être supprimé car des contrats y sont rattachés.', 'erreur');
    }

    rediriger(
        'Quartier/all',
        $supprime ? 'Le quartier a été supprimé.' : 'Aucun quartier n’a été supprimé.',
        $supprime ? 'succes' : 'erreur'
    );
}

function quartierDonneesPostees(): array
{
    return [
        'nom'              => trim((string) ($_POST['nom'] ?? '')),
        'id_type_activite' => trim((string) ($_POST['id_type_activite'] ?? '')),
    ];
}

function quartierValider(array $d): array
{
    $erreurs = [];
    if ($d['nom'] === '' || mb_strlen($d['nom']) > 100) {
        $erreurs[] = 'Le nom du quartier est obligatoire (100 caractères maximum).';
    }
    if (!ctype_digit($d['id_type_activite']) || !bdTypeActiviteOne((int) $d['id_type_activite'])) {
        $erreurs[] = 'Choisissez un type d’activité valide.';
    }
    return $erreurs;
}
