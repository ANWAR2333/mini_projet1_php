<?php
// Échappe les valeurs affichées pour éviter d'interpréter du HTML saisi.
$e = fn($valeur) => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>
<!-- En-tête avec le nombre total de clients. -->
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="users"></i> Gestion des clients</h1>
    <p class="sous-titre">Consulter et gérer les comptes clients</p>
  </div>
  <div class="cercle">
    <?= (int) $total ?>
    <small>clients</small>
  </div>
</header>

<!-- Liste des clients et liens vers leurs fiches. -->
<section class="carte">
  <p><a class="btn-principal" href="<?= BASE_URL ?>Client/add">Ajouter un client</a></p>
  <?php if (!$clients): ?>
    <p>Aucun client enregistré.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead>
          <tr>
            <th>Nom</th><th>Email</th><th>Téléphone</th><th>Compteur eau</th><th>Compteur électricité</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $client): ?>
            <tr>
              <td><?= $e($client['prenom'] . ' ' . $client['nom']) ?></td>
              <td><?= $e($client['email']) ?></td>
              <td><?= $e($client['telephone'] ?: '—') ?></td>
              <td><?= $e($client['num_compteur_eau'] ?: '—') ?></td>
              <td><?= $e($client['num_compteur_elec'] ?: '—') ?></td>
              <td>
                <div class="actions">
                  <a href="<?= BASE_URL ?>Client/one/<?= (int) $client['id'] ?>" title="Voir le client"><i data-lucide="eye"></i></a>
                  <a href="<?= BASE_URL ?>Client/update/<?= (int) $client['id'] ?>" title="Modifier le client"><i data-lucide="pencil"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
