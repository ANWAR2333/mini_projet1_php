<?php $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="map-pin"></i> Quartiers</h1>
    <p class="sous-titre">Chaque quartier a un type d’activité qui fixe le barème de ses contrats</p>
  </div>
  <div class="cercle">
    <?= (int) $total ?>
    <small>quartiers</small>
  </div>
</header>

<section class="carte">
  <p><a class="btn-principal" href="<?= BASE_URL ?>Quartier/add">Ajouter un quartier</a></p>
  <?php if (!$quartiers): ?>
    <p>Aucun quartier enregistré.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead>
          <tr><th>Quartier</th><th>Type d’activité</th><th>Contrats</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($quartiers as $q): ?>
            <tr>
              <td><?= $e($q['nom']) ?></td>
              <td><?= $e(ucfirst($q['type_activite'])) ?></td>
              <td><?= (int) $q['nb_contrats'] ?></td>
              <td>
                <div class="actions">
                  <a href="<?= BASE_URL ?>Quartier/update/<?= (int) $q['id'] ?>" title="Modifier le quartier"><i data-lucide="pencil"></i></a>
                  <form method="post" action="<?= BASE_URL ?>Quartier/delete/<?= (int) $q['id'] ?>"
                        onsubmit="return confirm('Supprimer ce quartier ?');">
                    <button type="submit" title="Supprimer le quartier"><i data-lucide="trash-2"></i></button>
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
