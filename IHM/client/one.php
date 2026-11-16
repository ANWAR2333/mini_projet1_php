<?php
// Échappe les informations du client avant de les afficher dans la page.
$e = fn($valeur) => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>
<!-- Présente les informations principales du client sélectionné. -->
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="user-round"></i> <?= $e($client['prenom'] . ' ' . $client['nom']) ?></h1>
    <p class="sous-titre">Détails du compte client</p>
  </div>
</header>

<!-- Coordonnées et informations du compte client. -->
<section class="carte">
  <div class="grille-2">
    <p><strong>Email :</strong> <?= $e($client['email']) ?></p>
    <p><strong>Téléphone :</strong> <?= $e($client['telephone'] ?: '—') ?></p>
    <p><strong>Adresse :</strong> <?= $e($client['adresse'] ?: '—') ?></p>
    <p><strong>Créé le :</strong> <?= $e($client['date_creation'] ?: '—') ?></p>
  </div>
  <p>
    <a class="btn-principal" href="<?= BASE_URL ?>Client/update/<?= (int) $client['id'] ?>">Modifier</a>
    <a class="btn-secondaire" href="<?= BASE_URL ?>Client/all">Retour à la liste</a>
  </p>
</section>

<section class="carte">
  <h2>Contrats</h2>
  <p><a class="btn-principal" href="<?= BASE_URL ?>Contrat/add/<?= (int) $client['id'] ?>">Ajouter un contrat</a></p>
  <?php if (!$contrats): ?>
    <p>Aucun contrat pour ce client.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead>
          <tr><th>#</th><th>Quartier</th><th>Type</th><th>Compteur eau</th><th>Compteur électricité</th><th>Début</th><th>Fin</th><th>Statut</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($contrats as $contrat): ?>
            <tr>
              <td><?= (int) $contrat['id'] ?></td>
              <td><?= $e($contrat['quartier']) ?></td>
              <td><?= $e($contrat['type_activite']) ?></td>
              <td><?= $e($contrat['num_compteur_eau'] ?: '—') ?></td>
              <td><?= $e($contrat['num_compteur_elec'] ?: '—') ?></td>
              <td><?= $e($contrat['date_debut']) ?></td>
              <td><?= $e($contrat['date_fin'] ?: '—') ?></td>
              <td><?= $contrat['statut'] === 'resilie' ? 'Résilié' : 'Actif' ?></td>
              <td>
                <div class="actions">
                  <?php if ($contrat['statut'] === 'actif'): ?>
                    <a href="<?= BASE_URL ?>Contrat/update/<?= (int) $contrat['id'] ?>" title="Modifier le contrat"><i data-lucide="pencil"></i></a>
                  <?php endif; ?>
                  <?php if ($contrat['statut'] === 'actif'): ?>
                    <form method="post" action="<?= BASE_URL ?>Contrat/resilier/<?= (int) $contrat['id'] ?>"
                          onsubmit="return confirm('Résilier ce contrat ?');">
                      <button type="submit" title="Résilier le contrat"><i data-lucide="trash-2"></i></button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<!-- La suppression est envoyée en POST après confirmation. -->
<section class="carte">
  <h2>Supprimer ce client</h2>
  <p>La suppression est impossible si des consommations, factures ou autres données sont associées à ce compte.</p>
  <form method="post" action="<?= BASE_URL ?>Client/delete/<?= (int) $client['id'] ?>"
        onsubmit="return confirm('Confirmer la suppression de ce client ?');">
    <button class="btn-principal" type="submit">Supprimer le client</button>
  </form>
</section>
