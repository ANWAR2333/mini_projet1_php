<?php
// Variables reçues de factureAdmin() : $factures, $paiements (id facture => liste des paiements),
// $statut (null | 'impayee' | 'payee'), $idQuartier, $idType, $quartiers, $types, $totalImpaye
$e        = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$moisNoms = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$dh       = fn($x) => number_format((float) $x, 2, ',', ' ') . ' DH';
$statuts  = ['' => 'Tous les statuts', 'impayee' => 'Impayées', 'payee' => 'Payées'];
$modes    = ['especes' => 'Espèces', 'carte' => 'Carte', 'virement' => 'Virement'];
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
  <form method="get" class="formulaire" style="margin-bottom:1rem;">
    <select name="statut" onchange="this.form.submit()">
      <?php foreach ($statuts as $valeur => $libelle): ?>
        <option value="<?= $valeur ?>" <?= ($statut ?? '') === $valeur ? 'selected' : '' ?>><?= $libelle ?></option>
      <?php endforeach; ?>
    </select>

    <select name="quartier" onchange="this.form.submit()">
      <option value="">Tous les quartiers</option>
      <?php foreach ($quartiers as $q): ?>
        <option value="<?= (int) $q['id'] ?>" <?= $idQuartier === (int) $q['id'] ? 'selected' : '' ?>><?= $e($q['nom']) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="type" onchange="this.form.submit()">
      <option value="">Tous les types</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= (int) $t['id'] ?>" <?= $idType === (int) $t['id'] ? 'selected' : '' ?>><?= $e($t['nom']) ?></option>
      <?php endforeach; ?>
    </select>

    <?php if ($statut || $idQuartier || $idType): ?>
      <a class="btn-secondaire" href="<?= BASE_URL ?>Facture/admin">Réinitialiser</a>
    <?php endif; ?>
  </form>

  <?php if (empty($factures)): ?>
    <p>Aucune facture.</p>
  <?php else: ?>
  <div class="scroll-x">
    <table class="tableau">
      <thead>
        <tr><th>N°</th><th>Client</th><th>Contrat</th><th>Type</th><th>Période</th><th>Total TTC</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($factures as $f):
          [$m, $a] = explode('/', $f['periode']); ?>
        <tr>
          <td><?= $e($f['numero']) ?></td>
          <td><?= $e($f['prenom'] . ' ' . $f['nom']) ?></td>
          <td><?= !empty($f['id_contrat']) ? '#' . (int) $f['id_contrat'] . ' · ' . $e($f['quartier'] ?? '') : 'Ancien relevé' ?></td>
          <td><?= $e($f['type_activite'] ?? '—') ?></td>
          <td><?= $moisNoms[(int) $m - 1] . ' ' . $e($a) ?></td>
          <td><?= $dh($f['montant_ttc']) ?></td>
          <td>
            <?php if ($f['statut'] === 'payee'): ?>
              <span class="badge badge-vert">Payée</span>
              <?php foreach ($paiements[(int) $f['id']] ?? [] as $p): ?>
                <br><small>
                  <?= date('d/m/Y', strtotime($p['date_paiement'])) ?> ·
                  <?= $modes[$p['mode']] ?? $e($p['mode']) ?> ·
                  <?= $dh($p['montant']) ?>
                </small>
              <?php endforeach; ?>
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