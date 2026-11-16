<?php
// Variables reçues de reclamationOne() : $reclamation, $types, $statuts, $old, $erreurs
$e    = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$date = fn($x) => $x ? date('d/m/Y H:i', strtotime($x)) : '';
$r    = $reclamation;
[$libelleStatut, $classe] = $statuts[$r['statut']] ?? [$r['statut'], 'badge-jaune'];
?>
<p><a href="<?= BASE_URL ?>Reclamation/admin">← Retour aux réclamations</a></p>

<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="message-square"></i> Réclamation n° <?= (int) $r['id'] ?></h1>
    <p class="sous-titre">Envoyée le <?= $date($r['date_reclamation']) ?></p>
  </div>
  <span class="badge <?= $classe ?>"><?= $e($libelleStatut) ?></span>
</header>

<section class="carte">
  <p><strong>Client :</strong> <?= $e($r['prenom'] . ' ' . $r['nom']) ?> (<?= $e($r['email']) ?>)</p>
  <p><strong>Type :</strong> <?= $e($types[$r['type']] ?? $r['type']) ?>
    <?php if ($r['type'] === 'autre' && $r['autre_type'] !== null): ?> : <?= $e($r['autre_type']) ?><?php endif; ?>
  </p>
  <?php if (!empty($r['id_contrat'])): ?>
    <p><strong>Contrat :</strong> #<?= (int) $r['id_contrat'] ?> · <?= $e($r['quartier'] ?? '') ?></p>
  <?php endif; ?>
  <?php if (!empty($r['facture_numero'])): ?>
    <p><strong>Facture :</strong>
      <a href="<?= BASE_URL ?>Facture/pdf/<?= (int) $r['id_facture'] ?>"><?= $e($r['facture_numero']) ?></a>
      (<?= $e($r['facture_periode']) ?>)
    </p>
  <?php endif; ?>
  <p><strong>Description :</strong></p>
  <p><?= nl2br($e($r['description'])) ?></p>
</section>

<?php if ($r['statut'] === 'traitee'): ?>
  <section class="carte">
    <p><strong>Réponse envoyée</strong> <small>(<?= $date($r['date_reponse']) ?>)</small></p>
    <p><?= nl2br($e($r['reponse'])) ?></p>
  </section>
<?php else: ?>
  <form class="carte formulaire" method="post" action="<?= BASE_URL ?>Reclamation/one/<?= (int) $r['id'] ?>">
    <?php if (!empty($r['date_reponse'])): ?>
      <p class="note-info">Dernière réponse le <?= $date($r['date_reponse']) ?>. Vous pouvez la compléter.</p>
    <?php endif; ?>

    <label for="reponse">Réponse au client *</label>
    <textarea class="champ" id="reponse" name="reponse" rows="5" maxlength="2000" required><?= $e($old['reponse']) ?></textarea>
    <?php if (isset($erreurs['reponse'])): ?>
      <p class="erreur-champ"><?= $e($erreurs['reponse']) ?></p>
    <?php endif; ?>

    <label for="statut">Nouveau statut *</label>
    <select class="champ" id="statut" name="statut">
      <option value="en_cours" <?= $old['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours (le client est informé, le dossier reste ouvert)</option>
      <option value="traitee" <?= $old['statut'] === 'traitee' ? 'selected' : '' ?>>Traitée (réponse définitive)</option>
    </select>
    <?php if (isset($erreurs['statut'])): ?>
      <p class="erreur-champ"><?= $e($erreurs['statut']) ?></p>
    <?php endif; ?>

    <p>
      <button type="submit" class="btn-principal">Envoyer la réponse</button>
      <a class="btn-secondaire" href="<?= BASE_URL ?>Reclamation/admin">Annuler</a>
    </p>
  </form>
<?php endif; ?>
