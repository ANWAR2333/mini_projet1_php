<?php
declare(strict_types=1);

function contratDonneesFormulaire(array $contrat = []): array
{
    $champs = [
        'id_quartier',
        'num_compteur_eau',
        'num_compteur_elec',
        'index_initial_eau',
        'index_initial_elec',
        'date_debut',
        'date_fin',
        'statut',
    ];

    $donnees = array_fill_keys($champs, '');

    foreach ($champs as $champ) {
        if (array_key_exists($champ, $contrat)) {
            $donnees[$champ] = (string) $contrat[$champ];
        }
    }

    if ($donnees['statut'] === '') {
        $donnees['statut'] = 'actif';
    }
    if ($donnees['date_debut'] === '') {
        $donnees['date_debut'] = date('Y-m-d');
    }

    return $donnees;
}

function contratDonneesPostees(): array
{
    $donnees = contratDonneesFormulaire();
    foreach (array_keys($donnees) as $champ) {
        $valeur = $_POST[$champ] ?? '';
        $donnees[$champ] = trim((string) $valeur);
    }

    return $donnees;
}

function contratValider(array $donnees): array
{
    $erreurs = [];

    if (!ctype_digit((string) ($donnees['id_quartier'] ?? '')) || !bdQuartierOne((int) $donnees['id_quartier'])) {
        $erreurs[] = 'Choisissez un quartier valide.';
    }

    foreach (['num_compteur_eau', 'num_compteur_elec'] as $champ) {
        if (isset($donnees[$champ]) && strlen($donnees[$champ]) > 50) {
            $erreurs[] = 'Le numéro de compteur ne peut pas dépasser 50 caractères.';
        }
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $donnees['date_debut'] ?? '')) {
        $erreurs[] = 'La date de début est obligatoire et doit être au format YYYY-MM-DD.';
    }

    if (($donnees['date_fin'] ?? '') !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $donnees['date_fin'])) {
        $erreurs[] = 'La date de fin doit être vide ou au format YYYY-MM-DD.';
    }

    if (($donnees['date_fin'] ?? '') !== '' && $donnees['date_debut'] > $donnees['date_fin']) {
        $erreurs[] = 'La date de fin doit être postérieure à la date de début.';
    }

    if (!in_array($donnees['statut'] ?? 'actif', ['actif', 'resilie'], true)) {
        $erreurs[] = 'Le statut du contrat est invalide.';
    }

    foreach (['index_initial_eau', 'index_initial_elec'] as $champ) {
        if ($donnees[$champ] === '' || !is_numeric($donnees[$champ]) || (float) $donnees[$champ] < 0) {
            $erreurs[] = 'Les index initiaux sont obligatoires et doivent être positifs.';
            break;
        }
    }

    return $erreurs;
}

function contratAll(): void
{
    verifierRole('client');

    $contrats = bdContratsByClient((int) ($_SESSION['id'] ?? 0));

    afficherVue('contrat/index.php', [
        'titre'   => 'Mes contrats',
        'contrats' => $contrats,
    ]);
}

function contratAdd(int $idClient): void
{
    verifierRole('fournisseur');

    $client = bdClientOne($idClient);
    if (!$client) {
        afficher404();
    }

    $donnees = contratDonneesFormulaire();
    $erreurs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $donnees = contratDonneesPostees();
        $donnees['statut'] = 'actif';
        $erreurs = contratValider($donnees);

        if (!$erreurs) {
            try {
                bdContratInsert(
                    $idClient,
                    (int) $donnees['id_quartier'],
                    $donnees['num_compteur_eau'] !== '' ? $donnees['num_compteur_eau'] : null,
                    $donnees['num_compteur_elec'] !== '' ? $donnees['num_compteur_elec'] : null,
                    (float) $donnees['index_initial_eau'],
                    (float) $donnees['index_initial_elec'],
                    $donnees['date_debut'],
                    $donnees['date_fin'] !== '' ? $donnees['date_fin'] : null,
                    'actif'
                );
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Un numéro de compteur est déjà utilisé par un autre contrat.';
            }

            if (!$erreurs) {
                rediriger('Client/one/' . $idClient, 'Le contrat a été ajouté.');
            }
        }
    }

    afficherVue('contrat/form.php', [
        'titre'    => 'Ajouter un contrat',
        'client'   => $client,
        'contrat'  => $donnees,
        'quartiers' => bdQuartierAll(),
        'erreurs'  => $erreurs,
        'mode'     => 'add',
        'idClient' => $idClient,
    ]);
}

function contratUpdate(int $id): void
{
    verifierRole('fournisseur');

    $contrat = bdContratOne($id);
    if (!$contrat) {
        afficher404();
    }

    $client = bdClientOne((int) $contrat['id_client']);
    if (!$client) {
        afficher404();
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
                    $donnees['num_compteur_eau'] !== '' ? $donnees['num_compteur_eau'] : null,
                    $donnees['num_compteur_elec'] !== '' ? $donnees['num_compteur_elec'] : null,
                    (float) $donnees['index_initial_eau'],
                    (float) $donnees['index_initial_elec'],
                    $donnees['date_debut'],
                    $donnees['date_fin'] !== '' ? $donnees['date_fin'] : null,
                    $donnees['statut']
                );
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Un numéro de compteur est déjà utilisé par un autre contrat.';
            }

            if (!$erreurs) {
                rediriger('Client/one/' . (int) $contrat['id_client'], 'Le contrat a été modifié.');
            }
        }
    }

    afficherVue('contrat/form.php', [
        'titre'      => 'Modifier un contrat',
        'client'     => $client,
        'contrat'    => $donnees,
        'contratId'  => $id,
        'quartiers'  => bdQuartierAll(),
        'erreurs'    => $erreurs,
        'mode'       => 'update',
        'idClient'   => (int) $contrat['id_client'],
    ]);
}

function contratResilier(int $id): void
{
    verifierRole('fournisseur');

    $contrat = bdContratOne($id);
    if (!$contrat) {
        afficher404();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        afficher404();
    }

    $modifie = bdContratResilier($id, date('Y-m-d'));
    if ($modifie === 0) {
        rediriger('Client/one/' . (int) $contrat['id_client'], 'Ce contrat est déjà résilié.', 'erreur');
    }

    rediriger('Client/one/' . (int) $contrat['id_client'], 'Le contrat a été résilié.');
}
