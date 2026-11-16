<?php
declare(strict_types=1);

function contratAll(): void
{
    verifierRole('client');
    afficherVue('contrat/index.php', [
        'titre' => 'Mes contrats',
        'contrats' => bdContratsByClient((int) $_SESSION['id']),
    ]);
}

function contratAdd(int $idClient): void
{
    verifierRole('fournisseur');
    $client = bdClientOne($idClient);
    if (!$client) {
        afficher404();
    }

    $contrat = contratDonneesFormulaire();
    $erreurs = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contrat = contratDonneesPostees();
        $erreurs = contratValider($contrat);
        if (!$erreurs) {
            try {
                bdContratInsert(
                    $idClient,
                    (int) $contrat['id_quartier'],
                    $contrat['num_compteur_eau'],
                    $contrat['num_compteur_elec'],
                    (float) $contrat['index_initial_eau'],
                    (float) $contrat['index_initial_elec'],
                    $contrat['date_debut']
                );
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Un numéro de compteur est déjà associé à un autre contrat.';
            }
            if (!$erreurs) {
                rediriger('Client/one/' . $idClient, 'Le contrat a été créé.');
            }
        }
    }

    afficherVue('contrat/form.php', [
        'titre' => 'Créer un contrat',
        'client' => $client,
        'contrat' => $contrat,
        'quartiers' => bdQuartierAll(),
        'erreurs' => $erreurs,
        'mode' => 'add',
    ]);
}

function contratUpdate(int $id): void
{
    verifierRole('fournisseur');
    $contrat = bdContratOne($id);
    if (!$contrat || $contrat['statut'] !== 'actif') {
        afficher404();
    }
    if (bdContratAConsommations($id)) {
        rediriger(
            'Client/one/' . (int) $contrat['id_client'],
            'Ce contrat a déjà des relevés et ne peut plus être modifié.',
            'erreur'
        );
    }

    $donnees = contratDonneesFormulaire($contrat);
    $erreurs = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $donnees = contratDonneesPostees();
        $erreurs = contratValider($donnees);
        if (!$erreurs) {
            try {
                bdContratUpdate(
                    $id,
                    (int) $donnees['id_quartier'],
                    $donnees['num_compteur_eau'],
                    $donnees['num_compteur_elec'],
                    (float) $donnees['index_initial_eau'],
                    (float) $donnees['index_initial_elec'],
                    $donnees['date_debut']
                );
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Un numéro de compteur est déjà associé à un autre contrat.';
            }
            if (!$erreurs) {
                rediriger('Client/one/' . (int) $contrat['id_client'], 'Le contrat a été modifié.');
            }
        }
    }

    afficherVue('contrat/form.php', [
        'titre' => 'Modifier un contrat',
        'client' => bdClientOne((int) $contrat['id_client']),
        'contrat' => $donnees,
        'quartiers' => bdQuartierAll(),
        'erreurs' => $erreurs,
        'mode' => 'update',
        'contratId' => $id,
    ]);
}

function contratResilier(int $id): void
{
    verifierRole('fournisseur');
    $contrat = bdContratOne($id);
    if (!$contrat || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        afficher404();
    }
    if ($contrat['statut'] !== 'actif') {
        rediriger('Client/one/' . (int) $contrat['id_client'], 'Ce contrat est déjà résilié.', 'erreur');
    }

    bdContratResilier($id);
    rediriger('Client/one/' . (int) $contrat['id_client'], 'Le contrat a été résilié.');
}

function contratDonneesFormulaire(array $contrat = []): array
{
    $champs = [
        'id_quartier', 'num_compteur_eau', 'num_compteur_elec',
        'index_initial_eau', 'index_initial_elec', 'date_debut',
    ];
    $donnees = array_fill_keys($champs, '');
    foreach ($champs as $champ) {
        if (isset($contrat[$champ])) {
            $donnees[$champ] = (string) $contrat[$champ];
        }
    }
    if ($donnees['date_debut'] === '') {
        $donnees['date_debut'] = date('Y-m-d');
    }
    return $donnees;
}

function contratDonneesPostees(): array
{
    $donnees = contratDonneesFormulaire();
    foreach ($donnees as $champ => $_) {
        $donnees[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }
    return $donnees;
}

function contratValider(array $donnees): array
{
    $erreurs = [];
    if (!ctype_digit($donnees['id_quartier']) || !bdQuartierOne((int) $donnees['id_quartier'])) {
        $erreurs[] = 'Choisissez un quartier valide.';
    }
    foreach (['num_compteur_eau' => 'eau', 'num_compteur_elec' => 'électricité'] as $champ => $libelle) {
        if ($donnees[$champ] === '' || strlen($donnees[$champ]) > 50) {
            $erreurs[] = 'Le numéro du compteur ' . $libelle . ' est obligatoire (50 caractères maximum).';
        }
    }
    foreach (['index_initial_eau' => 'eau', 'index_initial_elec' => 'électricité'] as $champ => $libelle) {
        if (!is_numeric($donnees[$champ]) || (float) $donnees[$champ] < 0 || (float) $donnees[$champ] > 99999999.99) {
            $erreurs[] = 'L’index initial ' . $libelle . ' doit être un nombre positif valide.';
        }
    }
    $date = DateTime::createFromFormat('!Y-m-d', $donnees['date_debut']);
    if (!$date || $date->format('Y-m-d') !== $donnees['date_debut'] || $donnees['date_debut'] > date('Y-m-d')) {
        $erreurs[] = 'La date de début doit être une date valide qui n’est pas dans le futur.';
    }
    return array_values(array_unique($erreurs));
}
