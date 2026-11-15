<?php
$e = fn($valeur) => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="file-text"></i> Mes contrats</h1>
    <p class="sous-titre">Consulter les contrats liés à votre compte</p>
  </div>
</header>

<section class="carte">
  <?php if (!$contrats): ?>
    <p>Aucun contrat n’est actuellement associé à votre compte.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead>
          <tr>
            <th>#</th>
            <th>Quartier</th>
            <th>Type</th>
            <th>Compteur eau</th>
            <th>Compteur électricité</th>
            <th>Date de début</th>
            <th>Date de fin</th>
            <th>Statut</th>
          </tr>
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
              <td><?= $e($contrat['statut'] === 'resilie' ? 'Résilié' : 'Actif') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
