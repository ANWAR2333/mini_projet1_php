<?php
/**
 * IHM/facture/one.php — Farah Bel Aali
 * Maquette : Écran 4 « Mes Factures » (carte détail).
 * Variable reçue de factureOne() : $facture (bdFactureOne + montant_eau, montant_elec)
 * Uniquement des classes de assets/css/style.css (carte, grille-2, tableau, badge, btn-principal…).
 */
$e        = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$moisNoms = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$dh       = fn($x) => number_format((float) $x, 2, ',', ' ') . ' DH';
$num      = fn($x) => rtrim(rtrim(number_format((float) $x, 2, ',', ''), '0'), ',');
$f        = $facture;
$debut    = sprintf('01/%02d/%04d', $f['mois'], $f['annee']);
$fin      = date('t/m/Y', mktime(0, 0, 0, (int) $f['mois'], 1, (int) $f['annee']));
?>
<p><a href="<?= BASE_URL ?>Facture/all">← Retour à mes factures</a></p>

<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="file-text"></i> Facture N° <?= $e($f['numero']) ?></h1>
    <p class="sous-titre"><?= $moisNoms[(int) $f['mois'] - 1] . ' ' . (int) $f['annee'] ?></p>
  </div>
  <?php if ($f['statut'] === 'payee'): ?>
    <span class="badge badge-vert">Payée</span>
  <?php else: ?>
    <span class="badge badge-rouge">Impayée</span>
  <?php endif; ?>
</header>

<div class="grille-2">
  <section class="carte">
    <h3>Facture et client</h3>
    <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($f['date_facture'])) ?></p>
    <p><strong>Période :</strong> <?= $debut ?> – <?= $fin ?></p>
    <p><strong>Client :</strong> <?= $e($f['prenom'] . ' ' . $f['nom']) ?></p>
    <p><strong>Adresse :</strong> <?= $e($f['adresse'] ?? '') ?></p>
  </section>

  <section class="carte">
    <h3>Photos des compteurs</h3>
    <div class="grille-2">
      <figure>
        <?php if (!empty($f['photo_eau'])): ?>
          <img src="<?= BASE_URL . $e($f['photo_eau']) ?>" alt="Compteur d'eau">
        <?php else: ?>
          <i data-lucide="camera"></i>
        <?php endif; ?>
        <figcaption>Eau · <?= $num($f['index_eau']) ?> m³</figcaption>
      </figure>
      <figure>
        <?php if (!empty($f['photo_elec'])): ?>
          <img src="<?= BASE_URL . $e($f['photo_elec']) ?>" alt="Compteur d'électricité">
        <?php else: ?>
          <i data-lucide="camera"></i>
        <?php endif; ?>
        <figcaption>Élec. · <?= $num($f['index_elec']) ?> kWh</figcaption>
      </figure>
    </div>
  </section>
</div>

<section class="carte">
  <table class="tableau">
    <thead>
      <tr><th>Service</th><th>Consommation</th><th>Montant HT</th></tr>
    </thead>
    <tbody>
      <tr><td>Eau</td><td><?= $num($f['conso_eau']) ?> m³</td><td><?= $dh($f['montant_eau']) ?></td></tr>
      <tr><td>Électricité</td><td><?= $num($f['conso_elec']) ?> kWh</td><td><?= $dh($f['montant_elec']) ?></td></tr>
      <tr><td><strong>Montant HT</strong></td><td></td><td><strong><?= $dh($f['montant_ht']) ?></strong></td></tr>
      <tr><td>TVA 18 %</td><td></td><td><?= $dh($f['tva']) ?></td></tr>
      <tr><td><strong>Total TTC</strong></td><td></td><td><strong><?= $dh($f['montant_ttc']) ?></strong></td></tr>
    </tbody>
  </table>
  <p>
    <a class="btn-principal" href="<?= BASE_URL ?>Facture/pdf/<?= (int) $f['id'] ?>">
      <i data-lucide="download"></i> Télécharger PDF
    </a>
  </p>
</section>
