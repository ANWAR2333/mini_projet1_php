<?php
declare(strict_types=1);

function typeActiviteAll(): void
{
    verifierRole('fournisseur');
    afficherVue('type_activite/index.php', [
        'titre' => 'Types d’activité',
        'types' => bdTypeActiviteAll(),
    ]);
}

function typeActiviteOne(int $id): void
{
    verifierRole('fournisseur');
    $type = bdTypeActiviteOne($id);
    if (!$type) {
        afficher404();
    }

    afficherVue('type_activite/one.php', [
        'titre' => 'Type d’activité ' . $type['nom'],
        'type' => $type,
        'quartiers' => bdTypeActiviteQuartiers($id),
        'tranches' => bdTypeActiviteTranches($id),
    ]);
}

function typeActiviteAdd(): void
{
    verifierRole('fournisseur');
    $type = ['nom' => '', 'seuil_eau' => '50', 'seuil_elec' => '500'];
    $erreurs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = typeActiviteDonneesPostees();
        $erreurs = typeActiviteValider($type);

        if (!$erreurs) {
            try {
                bdTypeActiviteInsert($type['nom'], (float) $type['seuil_eau'], (float) $type['seuil_elec']);
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Un type d’activité porte déjà ce nom.';
            }
            if (!$erreurs) {
                rediriger('TypeActivite/all', 'Le type d’activité a été ajouté.');
            }
        }
    }

    afficherVue('type_activite/form.php', [
        'titre' => 'Ajouter un type d’activité',
        'type' => $type,
        'erreurs' => $erreurs,
        'mode' => 'add',
    ]);
}

function typeActiviteUpdate(int $id): void
{
    verifierRole('fournisseur');
    $type = bdTypeActiviteOne($id);
    if (!$type) {
        afficher404();
    }
    $erreurs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = typeActiviteDonneesPostees();
        $erreurs = typeActiviteValider($type);

        if (!$erreurs) {
            try {
                bdTypeActiviteUpdate($id, $type['nom'], (float) $type['seuil_eau'], (float) $type['seuil_elec']);
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $erreurs[] = 'Un type d’activité porte déjà ce nom.';
            }
            if (!$erreurs) {
                rediriger('TypeActivite/one/' . $id, 'Le type d’activité a été modifié.');
            }
        }
    }

    afficherVue('type_activite/form.php', [
        'titre' => 'Modifier un type d’activité',
        'type' => $type,
        'typeId' => $id,
        'erreurs' => $erreurs,
        'mode' => 'update',
    ]);
}

function typeActiviteDelete(int $id): void
{
    verifierRole('fournisseur');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !bdTypeActiviteOne($id)) {
        afficher404();
    }

    try {
        $supprime = bdTypeActiviteDelete($id);
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') {
            throw $e;
        }
        rediriger('TypeActivite/all', 'Ce type ne peut pas être supprimé car il est utilisé par un ou plusieurs quartiers.', 'erreur');
    }

    rediriger(
        'TypeActivite/all',
        $supprime ? 'Le type d’activité a été supprimé.' : 'Aucun type d’activité n’a été supprimé.',
        $supprime ? 'succes' : 'erreur'
    );
}

function typeActiviteDonneesPostees(): array
{
    return [
        'nom' => trim((string) ($_POST['nom'] ?? '')),
        'seuil_eau' => trim(str_replace(',', '.', (string) ($_POST['seuil_eau'] ?? ''))),
        'seuil_elec' => trim(str_replace(',', '.', (string) ($_POST['seuil_elec'] ?? ''))),
    ];
}

function typeActiviteValider(array $type): array
{
    $erreurs = [];
    if ($type['nom'] === '' || mb_strlen($type['nom']) > 50) {
        $erreurs[] = 'Le nom du type est obligatoire (50 caractères maximum).';
    }

    foreach (['seuil_eau' => 'd’eau', 'seuil_elec' => 'd’électricité'] as $champ => $libelle) {
        if (!is_numeric($type[$champ]) || (float) $type[$champ] <= 0 || (float) $type[$champ] > 99999999.99) {
            $erreurs[] = 'Le seuil ' . $libelle . ' doit être un nombre positif valide.';
        }
    }

    return $erreurs;
}
