<?php
declare(strict_types=1);

// Affiche la liste des clients au fournisseur connecté.
function clientAll(): void
{
    verifierRole('fournisseur');
    afficherVue('client/index.php', [
        'titre'   => 'Gestion des clients',
        'clients' => bdClientAll(),
        'total'   => bdClientCount(),
    ]);
}

// Affiche les détails d'un client, ou une page 404 s'il n'existe pas.
function clientOne(int $id): void
{
    verifierRole('fournisseur');
    $client = bdClientOne($id);
    if (!$client) {
        afficher404();
    }

    afficherVue('client/one.php', [
        'titre'  => 'Client ' . $client['prenom'] . ' ' . $client['nom'],
        'client' => $client,
    ]);
}

// Traite le formulaire de création et conserve les valeurs saisies en cas d'erreur.
function clientAdd(): void
{
    verifierRole('fournisseur');

    $donnees = clientDonneesFormulaire();
    $erreurs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $donnees = clientDonneesPostees();
        $erreurs = clientValiderDonnees($donnees, true);

        if (!$erreurs) {
            try {
                // Les champs facultatifs vides sont enregistrés comme NULL.
                bdClientInsert(
                    $donnees['nom'],
                    $donnees['prenom'],
                    $donnees['adresse'] !== '' ? $donnees['adresse'] : null,
                    $donnees['telephone'] !== '' ? $donnees['telephone'] : null,
                    $donnees['email'],
                    $donnees['mot_de_passe'],
                    $donnees['num_compteur_eau'],
                    $donnees['num_compteur_elec'],
                    (float) $donnees['index_initial_eau'],
                    (float) $donnees['index_initial_elec']
                );
            } catch (PDOException $e) {
                // Le code SQL 23000 signale notamment une valeur unique déjà utilisée.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Cet email ou ce numéro de compteur est déjà utilisé.';
            }

            if (!$erreurs) {
                rediriger('Client/all', 'Le client a été ajouté.');
            }
        }
    }

    afficherVue('client/form.php', [
        'titre'   => 'Ajouter un client',
        'client'  => $donnees,
        'erreurs' => $erreurs,
        'mode'    => 'add',
    ]);
}

// Charge un client puis traite son formulaire de modification.
function clientUpdate(int $id): void
{
    verifierRole('fournisseur');
    $client = bdClientOne($id);
    if (!$client) {
        afficher404();
    }

    $donnees = clientDonneesFormulaire($client);
    $erreurs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $donnees = clientDonneesPostees();
        $erreurs = clientValiderDonnees($donnees, false);

        if (!$erreurs) {
            try {
                // Le mot de passe n'est pas modifié par ce formulaire.
                bdClientUpdate(
                    $id,
                    $donnees['nom'],
                    $donnees['prenom'],
                    $donnees['adresse'] !== '' ? $donnees['adresse'] : null,
                    $donnees['telephone'] !== '' ? $donnees['telephone'] : null,
                    $donnees['email'],
                    $donnees['num_compteur_eau'],
                    $donnees['num_compteur_elec'],
                    (float) $donnees['index_initial_eau'],
                    (float) $donnees['index_initial_elec']
                );
            } catch (PDOException $e) {
                // L'email et les numéros de compteur doivent rester uniques.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Cet email ou ce numéro de compteur est déjà utilisé.';
            }

            if (!$erreurs) {
                rediriger('Client/one/' . $id, 'Les informations du client ont été modifiées.');
            }
        }
    }

    $donnees['id'] = $id;
    afficherVue('client/form.php', [
        'titre'   => 'Modifier un client',
        'client'  => $donnees,
        'clientId' => $id,
        'erreurs' => $erreurs,
        'mode'    => 'update',
    ]);
}

// La suppression se fait uniquement par POST et échoue si le client a des données liées.
function clientDelete(int $id): void
{
    verifierRole('fournisseur');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        afficher404();
    }

    if (!bdClientOne($id)) {
        afficher404();
    }

    try {
        $supprime = bdClientDelete($id);
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') {
            throw $e;
        }
        rediriger(
            'Client/one/' . $id,
            'Ce client ne peut pas être supprimé car des données lui sont associées.',
            'erreur'
        );
    }

    rediriger(
        'Client/all',
        $supprime ? 'Le client a été supprimé.' : 'Aucun client n’a été supprimé.',
        $supprime ? 'succes' : 'erreur'
    );
}

// Prépare les champs attendus par le formulaire, avec des valeurs vides par défaut.
function clientDonneesFormulaire(array $client = []): array
{
    $champs = [
        'nom', 'prenom', 'adresse', 'telephone', 'email',
        'num_compteur_eau', 'num_compteur_elec',
        'index_initial_eau', 'index_initial_elec',
    ];
    $donnees = array_fill_keys($champs, '');

    foreach ($champs as $champ) {
        if (isset($client[$champ])) {
            $donnees[$champ] = (string) $client[$champ];
        }
    }

    $donnees['mot_de_passe'] = '';
    return $donnees;
}

// Récupère et nettoie les valeurs envoyées par le formulaire.
function clientDonneesPostees(): array
{
    $donnees = clientDonneesFormulaire();
    foreach ($donnees as $champ => $_) {
        $donnees[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }

    return $donnees;
}

// Vérifie les champs obligatoires, les limites, l'email, le mot de passe et les index.
function clientValiderDonnees(array &$donnees, bool $motDePasseObligatoire): array
{
    $erreurs = [];
    $limites = [
        'nom'                 => 100,
        'prenom'              => 100,
        'adresse'             => 255,
        'telephone'           => 20,
        'email'               => 150,
        'num_compteur_eau'    => 50,
        'num_compteur_elec'   => 50,
    ];

    foreach (['nom', 'prenom', 'email'] as $champ) {
        if ($donnees[$champ] === '') {
            $erreurs[] = 'Les champs nom, prénom et email sont obligatoires.';
            break;
        }
    }

    foreach ($limites as $champ => $max) {
        if (strlen($donnees[$champ]) > $max) {
            $erreurs[] = 'La valeur du champ « ' . $champ . ' » dépasse ' . $max . ' caractères.';
        }
    }

    if ($donnees['email'] !== '' && !filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'Veuillez saisir une adresse email valide.';
    }

    $donnees['email'] = strtolower($donnees['email']);

    if ($motDePasseObligatoire && $donnees['mot_de_passe'] === '') {
        $erreurs[] = 'Le mot de passe est obligatoire.';
    } elseif ($motDePasseObligatoire && strlen($donnees['mot_de_passe']) < 8) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }

    foreach (['index_initial_eau', 'index_initial_elec'] as $champ) {
        $valeur = str_replace(',', '.', $donnees[$champ]);
        if ($valeur === '' || !is_numeric($valeur) || (float) $valeur < 0 || (float) $valeur > 99999999.99) {
            $erreurs[] = 'Les index initiaux doivent être des nombres positifs valides.';
            continue;
        }
        $donnees[$champ] = (string) round((float) $valeur, 2);
    }

    return array_values(array_unique($erreurs));
}
