<?php $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="circle-alert"></i> Anomalies</h1>
    <p class="sous-titre">Saisies mises en attente : à vérifier, corriger si besoin, puis la facture est générée</p>
  </div>
  <div class="cercle">
    <?= (int) $aTraiter ?>
    <small>à traiter</small>
  </div>
</header>

<section class="carte">
  <?php if (!$saisies): ?>
    <p>Aucune anomalie pour le moment.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead>
          <tr><th>Client</th><th>Contrat</th><th>Période</th><th>Anomalies</th><th>Statut</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php foreach ($saisies as $s): ?>
            <tr>
              <td><?= $e($s['client']) ?></td>
              <td>n°<?= (int) $s['contrat'] ?> · <?= $e($s['quartier']) ?> (<?= $e($s['type']) ?>)</td>
              <td><?= $e($s['periode']) ?></td>
              <td>
                <?php foreach ($s['anomalies'] as $motif): ?>
                  <small class="motif"><?= $e($motif) ?></small>
                <?php endforeach; ?>
              </td>
              <td>
                <?php if ($s['a_traiter'] > 0): ?>
                  <span class="badge badge-jaune">À traiter</span>
                <?php else: ?>
                  <span class="badge badge-vert">Corrigée</span>
                <?php endif; ?>
              </td>
              <td>
                <a class="btn-secondaire" href="<?= BASE_URL ?>Anomalie/one/<?= (int) $s['id'] ?>">
                  <?= $s['a_traiter'] > 0 ? 'Traiter' : 'Voir' ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
