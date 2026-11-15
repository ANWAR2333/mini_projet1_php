<?php
// Variables reçues de reclamationAdmin() : $reclamations, $compte (par statut), $statut (null | clé de $statuts), $types, $statuts
$e    = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$date = fn($x) => $x ? date('d/m/Y', strtotime($x)) : '';
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="message-square"></i> Réclamations</h1>
    <p class="sous-titre">Répondre aux réclamations des clients</p>
  </div>
  <div class="cercle">
    <?= (int) $compte['en_attente'] ?>
    <small>en attente</small>
  </div>
</header>

<section class="carte">
  <p>
    <a class="<?= $statut === null ? 'btn-principal' : 'btn-secondaire' ?>" href="<?= BASE_URL ?>Reclamation/admin">
      Toutes (<?= array_sum($compte) ?>)
    </a>
    <?php foreach ($statuts as $valeur => [$libelle, $_]): ?>
      <a class="<?= $statut === $valeur ? 'btn-principal' : 'btn-secondaire' ?>"
         href="<?= BASE_URL ?>Reclamation/admin?statut=<?= $valeur ?>"><?= $libelle ?> (<?= (int) $compte[$valeur] ?>)</a>
    <?php endforeach; ?>
  </p>

  <?php if (empty($reclamations)): ?>
    <p>Aucune réclamation.</p>
  <?php else: ?>
  <div class="scroll-x">
    <table class="tableau">
      <thead>
        <tr><th>N°</th><th>Date</th><th>Client</th><th>Type</th><th>Concerne</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($reclamations as $r):
          [$libelleStatut, $classe] = $statuts[$r['statut']] ?? [$r['statut'], 'badge-jaune']; ?>
        <tr>
          <td><?= (int) $r['id'] ?></td>
          <td><?= $date($r['date_reclamation']) ?></td>
          <td><?= $e($r['prenom'] . ' ' . $r['nom']) ?></td>
          <td>
            <?= $e($types[$r['type']] ?? $r['type']) ?>
            <?php if ($r['type'] === 'autre' && $r['autre_type'] !== null): ?><br><small><?= $e($r['autre_type']) ?></small><?php endif; ?>
          </td>
          <td>
            <?php if (!empty($r['facture_numero'])): ?>
              Facture <?= $e($r['facture_numero']) ?>
            <?php elseif (!empty($r['id_contrat'])): ?>
              Contrat #<?= (int) $r['id_contrat'] ?> · <?= $e($r['quartier'] ?? '') ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td><span class="badge <?= $classe ?>"><?= $e($libelleStatut) ?></span></td>
          <td>
            <div class="actions">
              <a href="<?= BASE_URL ?>Reclamation/one/<?= (int) $r['id'] ?>" title="Ouvrir">
                <i data-lucide="<?= $r['statut'] === 'traitee' ? 'eye' : 'reply' ?>"></i>
                <?= $r['statut'] === 'traitee' ? 'Voir' : 'Répondre' ?>
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
