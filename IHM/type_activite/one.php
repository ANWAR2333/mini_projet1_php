<?php
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$services = ['eau' => 'Eau', 'electricite' => 'Électricité'];
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="layers"></i> <?= $e(ucfirst($type['nom'])) ?></h1>
    <p class="sous-titre">Détails des seuils, quartiers et barèmes</p>
  </div>
</header>

<section class="carte">
  <h2>Seuils d’anomalie</h2>
  <div class="grille-2">
    <p><strong>Eau :</strong> <?= number_format((float) $type['seuil_eau'], 2, ',', ' ') ?> m³/mois</p>
    <p><strong>Électricité :</strong> <?= number_format((float) $type['seuil_elec'], 2, ',', ' ') ?> kWh/mois</p>
  </div>
  <p>
    <a class="btn-principal" href="<?= BASE_URL ?>TypeActivite/update/<?= (int) $type['id'] ?>">Modifier ce type</a>
    <a class="btn-secondaire" href="<?= BASE_URL ?>TypeActivite/all">Retour aux types</a>
  </p>
</section>

<section class="carte">
  <h2>Quartiers associés</h2>
  <?php if (!$quartiers): ?>
    <p>Aucun quartier n’utilise ce type.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead><tr><th>Quartier</th><th>Contrats</th></tr></thead>
        <tbody>
          <?php foreach ($quartiers as $quartier): ?>
            <tr>
              <td><?= $e($quartier['nom']) ?></td>
              <td><?= (int) $quartier['nb_contrats'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="carte">
  <h2>Barèmes tarifaires</h2>
  <?php if (!$tranches): ?>
    <p>Aucune tranche tarifaire configurée pour ce type.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead><tr><th>Service</th><th>Consommation maximale</th><th>Prix unitaire (DH)</th><th>Ordre</th></tr></thead>
        <tbody>
          <?php foreach ($tranches as $tranche): ?>
            <tr>
              <td><?= $e($services[$tranche['service']] ?? $tranche['service']) ?></td>
              <td><?= $tranche['borne_max'] === null ? 'Sans limite' : number_format((float) $tranche['borne_max'], 2, ',', ' ') ?></td>
              <td><?= number_format((float) $tranche['prix_unitaire'], 4, ',', ' ') ?></td>
              <td><?= (int) $tranche['ordre'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="carte">
  <h2>Supprimer ce type</h2>
  <p>Un type ne peut pas être supprimé tant qu’un quartier lui est associé.</p>
  <form method="post" action="<?= BASE_URL ?>TypeActivite/delete/<?= (int) $type['id'] ?>"
        onsubmit="return confirm('Supprimer ce type d’activité ?');">
    <button class="btn-principal" type="submit">Supprimer le type</button>
  </form>
</section>
