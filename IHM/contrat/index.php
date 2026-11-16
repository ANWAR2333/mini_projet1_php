<?php
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$nb = fn($v) => number_format((float) $v, 2, ',', ' ');
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="file-badge"></i> Mes contrats</h1>
    <p class="sous-titre">Les compteurs et consommations sont suivis séparément pour chaque contrat.</p>
  </div>
</header>

<section class="carte">
  <?php if (!$contrats): ?>
    <p>Vous n’avez pas encore de contrat. Contactez votre fournisseur.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead>
          <tr>
            <th>Contrat</th><th>Quartier</th><th>Activité</th><th>Compteur eau</th><th>Index initial eau</th>
            <th>Compteur électricité</th><th>Index initial électricité</th><th>Début</th><th>Fin</th><th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($contrats as $contrat): ?>
            <tr>
              <td>#<?= (int) $contrat['id'] ?></td>
              <td><?= $e($contrat['quartier']) ?></td>
              <td><?= $e($contrat['type_activite']) ?></td>
              <td><?= $e($contrat['num_compteur_eau'] ?: '—') ?></td>
              <td><?= $nb($contrat['index_initial_eau']) ?> m³</td>
              <td><?= $e($contrat['num_compteur_elec'] ?: '—') ?></td>
              <td><?= $nb($contrat['index_initial_elec']) ?> kWh</td>
              <td><?= $e($contrat['date_debut']) ?></td>
              <td><?= $e($contrat['date_fin'] ?: '—') ?></td>
              <td><?= $contrat['statut'] === 'resilie' ? 'Résilié' : 'Actif' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
