<?php
// Variables reçues de reclamationAll() : $reclamations, $types, $statuts
$e        = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$date     = fn($x) => $x ? date('d/m/Y H:i', strtotime($x)) : '';
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="message-square"></i> Mes réclamations</h1>
    <p class="sous-titre">Suivre vos réclamations et les réponses du fournisseur</p>
  </div>
  <a class="btn-principal" href="<?= BASE_URL ?>Reclamation/add"><i data-lucide="plus"></i> Nouvelle réclamation</a>
</header>

<?php if (empty($reclamations)): ?>
  <section class="carte">
    <p>Vous n'avez envoyé aucune réclamation.</p>
  </section>
<?php else: ?>
  <?php foreach ($reclamations as $r):
      [$libelleStatut, $classe] = $statuts[$r['statut']] ?? [$r['statut'], 'badge-jaune']; ?>
    <section class="carte">
      <p>
        <strong>N° <?= (int) $r['id'] ?> · <?= $e($types[$r['type']] ?? $r['type']) ?></strong>
        <?php if ($r['type'] === 'autre' && $r['autre_type'] !== null): ?> : <?= $e($r['autre_type']) ?><?php endif; ?>
        <span class="badge <?= $classe ?>"><?= $e($libelleStatut) ?></span>
      </p>
      <p><small>
        Envoyée le <?= $date($r['date_reclamation']) ?>
        <?php if (!empty($r['id_contrat'])): ?> · Contrat #<?= (int) $r['id_contrat'] ?> · <?= $e($r['quartier'] ?? '') ?><?php endif; ?>
        <?php if (!empty($r['facture_numero'])): ?> · Facture <?= $e($r['facture_numero']) ?><?php endif; ?>
      </small></p>
      <p><?= nl2br($e($r['description'])) ?></p>

      <?php if ($r['reponse'] !== null && $r['reponse'] !== ''): ?>
        <p class="note-info">
          <span><strong>Réponse du fournisseur</strong> <small>(<?= $date($r['date_reponse']) ?>)</small><br>
          <?= nl2br($e($r['reponse'])) ?></span>
        </p>
      <?php else: ?>
        <p><small>Pas encore de réponse.</small></p>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
