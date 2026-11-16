<?php $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="layers"></i> Types d’activité</h1>
    <p class="sous-titre">Les seuils sont appliqués aux contrats selon le type de leur quartier</p>
  </div>
  <div class="cercle">
    <?= count($types) ?>
    <small>types</small>
  </div>
</header>

<section class="carte">
  <p><a class="btn-principal" href="<?= BASE_URL ?>TypeActivite/add">Ajouter un type d’activité</a></p>
  <?php if (!$types): ?>
    <p>Aucun type d’activité enregistré.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead>
          <tr>
            <th>Type</th>
            <th>Seuil eau (m³/mois)</th>
            <th>Seuil électricité (kWh/mois)</th>
            <th>Quartiers</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($types as $type): ?>
            <tr>
              <td><?= $e(ucfirst($type['nom'])) ?></td>
              <td><?= number_format((float) $type['seuil_eau'], 2, ',', ' ') ?></td>
              <td><?= number_format((float) $type['seuil_elec'], 2, ',', ' ') ?></td>
              <td><?= (int) $type['nb_quartiers'] ?></td>
              <td>
                <div class="actions">
                  <a href="<?= BASE_URL ?>TypeActivite/one/<?= (int) $type['id'] ?>" title="Voir le type"><i data-lucide="eye"></i></a>
                  <a href="<?= BASE_URL ?>TypeActivite/update/<?= (int) $type['id'] ?>" title="Modifier le type"><i data-lucide="pencil"></i></a>
                  <form method="post" action="<?= BASE_URL ?>TypeActivite/delete/<?= (int) $type['id'] ?>"
                        onsubmit="return confirm('Supprimer ce type d’activité ?');">
                    <button type="submit" title="Supprimer le type"><i data-lucide="trash-2"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
