<?php
/**
 * IHM/facture/index.php — Farah Bel Aali
 * Maquette : Écran 4 « Mes Factures » (partie liste).
 * Variables reçues de factureAll() : $factures (array), $totalImpaye (float)
 */
$e        = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$moisNoms = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$dh       = fn($x) => number_format((float) $x, 2, ',', ' ') . ' DH';
$num      = fn($x) => rtrim(rtrim(number_format((float) $x, 2, ',', ''), '0'), ',');
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="file-text"></i> Mes Factures</h1>
    <p class="sous-titre">Consulter et télécharger vos factures</p>
  </div>
  <div class="cercle">
    <?= number_format($totalImpaye, 0, ',', ' ') ?> DH
    <small>à payer</small>
  </div>
</header>

<section class="carte">
  <?php if (empty($factures)): ?>
    <p>Vous n'avez encore aucune facture.</p>
  <?php else: ?>
  <div class="scroll-x">
    <table class="tableau">
      <thead>
        <tr>
          <th>N°</th><th>Date</th><th>Période</th><th>Eau</th><th>Élec.</th>
          <th>Total TTC</th><th>Statut</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($factures as $f):
          [$m, $a] = explode('/', $f['periode']); ?>
        <tr>
          <td><?= $e($f['numero']) ?></td>
          <td><?= date('d/m/Y', strtotime($f['date_facture'])) ?></td>
          <td><?= $moisNoms[(int) $m - 1] . ' ' . $e($a) ?></td>
          <td><?= $num($f['conso_eau']) ?> m³</td>
          <td><?= $num($f['conso_elec']) ?> kWh</td>
          <td><?= $dh($f['montant_ttc']) ?></td>
          <td>
            <?php if ($f['statut'] === 'payee'): ?>
              <span class="badge badge-vert">Payée</span>
            <?php else: ?>
              <span class="badge badge-rouge">Impayée</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="actions">
              <a href="<?= BASE_URL ?>Facture/one/<?= (int) $f['id'] ?>" title="Voir le détail"><i data-lucide="eye"></i></a>
              <a href="<?= BASE_URL ?>Facture/pdf/<?= (int) $f['id'] ?>" title="Télécharger le PDF"><i data-lucide="download"></i></a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
