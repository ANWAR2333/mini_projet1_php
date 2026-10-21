<?php
// Variables reçues de factureAdmin() : $factures, $statut (null | 'impayee' | 'payee'), $totalImpaye
$e        = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$moisNoms = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$dh       = fn($x) => number_format((float) $x, 2, ',', ' ') . ' DH';
$filtres  = ['' => 'Toutes', 'impayee' => 'Impayées', 'payee' => 'Payées'];
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="receipt"></i> Factures</h1>
    <p class="sous-titre">Suivre les factures et enregistrer les paiements</p>
  </div>
  <div class="cercle">
    <?= number_format($totalImpaye, 0, ',', ' ') ?> DH
    <small>impayés</small>
  </div>
</header>

<section class="carte">
  <p>
    <?php foreach ($filtres as $valeur => $libelle): ?>
      <a class="<?= ($statut ?? '') === $valeur ? 'btn-principal' : 'btn-secondaire' ?>"
         href="<?= BASE_URL ?>Facture/admin<?= $valeur !== '' ? '?statut=' . $valeur : '' ?>"><?= $libelle ?></a>
    <?php endforeach; ?>
  </p>

  <?php if (empty($factures)): ?>
    <p>Aucune facture.</p>
  <?php else: ?>
  <div class="scroll-x">
    <table class="tableau">
      <thead>
        <tr><th>N°</th><th>Client</th><th>Période</th><th>Total TTC</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($factures as $f):
          [$m, $a] = explode('/', $f['periode']); ?>
        <tr>
          <td><?= $e($f['numero']) ?></td>
          <td><?= $e($f['prenom'] . ' ' . $f['nom']) ?></td>
          <td><?= $moisNoms[(int) $m - 1] . ' ' . $e($a) ?></td>
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
              <a href="<?= BASE_URL ?>Facture/pdf/<?= (int) $f['id'] ?>" title="Télécharger le PDF"><i data-lucide="download"></i></a>
              <?php if ($f['statut'] === 'impayee'): ?>
                <a href="<?= BASE_URL ?>Paiement/add/<?= (int) $f['id'] ?>" title="Encaisser"><i data-lucide="banknote"></i> Encaisser</a>
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