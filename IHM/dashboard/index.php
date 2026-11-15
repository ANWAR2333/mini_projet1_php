<?php
$e  = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$dh = fn($v) => number_format((float) $v, 2, ',', ' ') . ' DH';
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="layout-dashboard"></i> Tableau de bord</h1>
    <p class="sous-titre">Vue d’ensemble de l’activité</p>
  </div>
</header>

<section class="grille-4">
  <div class="carte stat"><small>Factures impayées</small><strong><?= $dh($factures['total_impaye']) ?></strong><span><?= (int) $factures['nb_impayees'] ?> facture(s)</span></div>
  <div class="carte stat"><small>Montant total facturé</small><strong><?= $dh($factures['total']) ?></strong><span><?= (int) $factures['nb'] ?> facture(s)</span></div>
  <div class="carte stat"><small>Montant encaissé</small><strong><?= $dh($factures['total_paye']) ?></strong><span>factures payées</span></div>
  <div class="carte stat"><small>Clients / contrats actifs</small><strong><?= (int) $compteurs['clients'] ?> / <?= (int) $compteurs['contrats'] ?></strong><span>&nbsp;</span></div>
</section>

<section class="grille-2">
  <div class="carte stat">
    <small>Anomalies à traiter</small>
    <strong><?= (int) $compteurs['anomalies'] ?></strong>
    <a href="<?= BASE_URL ?>Anomalie/all">Voir les anomalies</a>
  </div>
  <div class="carte stat">
    <small>Réclamations en attente</small>
    <strong><?= (int) $compteurs['reclamations'] ?></strong>
    <a href="<?= BASE_URL ?>Reclamation/admin">Voir les réclamations</a>
  </div>
</section>

<section class="grille-2">
  <?php foreach ([['Consommation d’eau par mois (m³)', $graphEau, 'barre-eau'], ['Consommation d’électricité par mois (kWh)', $graphElec, 'barre-elec']] as [$titreGraph, $barres, $classe]): ?>
    <div class="carte">
      <h2><?= $e($titreGraph) ?></h2>
      <?php if (!$barres): ?>
        <p>Pas encore de facture.</p>
      <?php else: ?>
        <svg class="graphique" viewBox="0 0 480 170" role="img" aria-label="<?= $e($titreGraph) ?>">
          <?php foreach ($barres as $b): ?>
            <rect class="<?= $classe ?>" x="<?= (int) $b['x'] ?>" y="<?= (int) $b['y'] ?>" width="28" height="<?= (int) $b['hauteur'] ?>" rx="4"></rect>
            <text x="<?= (int) $b['x'] + 14 ?>" y="<?= (int) $b['y'] - 5 ?>" text-anchor="middle"><?= $e($b['valeur']) ?></text>
            <text x="<?= (int) $b['x'] + 14 ?>" y="155" text-anchor="middle"><?= $e($b['libelle']) ?></text>
          <?php endforeach; ?>
        </svg>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>

<section class="carte">
  <h2>Factures par quartier</h2>
  <div class="scroll-x">
    <table class="tableau">
      <thead><tr><th>Quartier</th><th>Factures</th><th>Montant TTC</th><th>Dont impayé</th></tr></thead>
      <tbody>
        <?php foreach ($quartiers as $q): ?>
          <tr>
            <td><?= $e($q['quartier']) ?></td>
            <td><?= (int) $q['nb'] ?></td>
            <td><?= $dh($q['total']) ?></td>
            <td><?= $dh($q['impaye']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
