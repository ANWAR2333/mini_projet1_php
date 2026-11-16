<?php
// Variables reçues de reclamationAdd() : $types, $contrats (actifs), $factures, $old, $erreurs
$e        = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$moisNoms = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$dh       = fn($x) => number_format((float) $x, 2, ',', ' ') . ' DH';
$erreur   = function (string $champ) use ($erreurs, $e): string {
    return isset($erreurs[$champ]) ? '<p class="erreur-champ">' . $e($erreurs[$champ]) . '</p>' : '';
};
?>
<p><a href="<?= BASE_URL ?>Reclamation/all">← Retour à mes réclamations</a></p>

<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="message-square"></i> Nouvelle réclamation</h1>
    <p class="sous-titre">Signaler une fuite, contester une facture ou poser une question</p>
  </div>
</header>

<form class="carte formulaire" method="post" action="<?= BASE_URL ?>Reclamation/add">
  <label for="type">Type de réclamation *</label>
  <select class="champ" id="type" name="type" required>
    <option value="">— Choisir —</option>
    <?php foreach ($types as $valeur => $libelle): ?>
      <option value="<?= $valeur ?>" <?= $old['type'] === $valeur ? 'selected' : '' ?>><?= $libelle ?></option>
    <?php endforeach; ?>
  </select>
  <?= $erreur('type') ?>

  <div data-pour="fuite_externe fuite_interne">
    <label for="id_contrat">Contrat concerné *</label>
    <?php if (empty($contrats)): ?>
      <p class="note-info">Vous n'avez aucun contrat actif.</p>
    <?php else: ?>
      <select class="champ" id="id_contrat" name="id_contrat">
        <option value="">— Choisir —</option>
        <?php foreach ($contrats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $old['id_contrat'] === (string) $c['id'] ? 'selected' : '' ?>>
            Contrat #<?= (int) $c['id'] ?> · <?= $e($c['quartier']) ?> (<?= $e($c['type_activite']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <?= $erreur('id_contrat') ?>
  </div>

  <div data-pour="facture">
    <label for="id_facture">Facture concernée *</label>
    <?php if (empty($factures)): ?>
      <p class="note-info">Vous n'avez aucune facture.</p>
    <?php else: ?>
      <select class="champ" id="id_facture" name="id_facture">
        <option value="">— Choisir —</option>
        <?php foreach ($factures as $f):
            [$m, $a] = explode('/', $f['periode']); ?>
          <option value="<?= (int) $f['id'] ?>" <?= $old['id_facture'] === (string) $f['id'] ? 'selected' : '' ?>>
            <?= $e($f['numero']) ?> · <?= $moisNoms[(int) $m - 1] . ' ' . $e($a) ?> · <?= $dh($f['montant_ttc']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <?= $erreur('id_facture') ?>
  </div>

  <div data-pour="autre">
    <label for="autre_type">Objet *</label>
    <input class="champ" type="text" id="autre_type" name="autre_type" maxlength="100" value="<?= $e($old['autre_type']) ?>">
    <?= $erreur('autre_type') ?>
  </div>

  <label for="description">Description *</label>
  <textarea class="champ" id="description" name="description" rows="5" minlength="10" maxlength="2000" required
            placeholder="Décrivez le problème : où, depuis quand, ce que vous constatez…"><?= $e($old['description']) ?></textarea>
  <?= $erreur('description') ?>

  <p>* Champs obligatoires.</p>
  <p>
    <button type="submit" class="btn-principal">Envoyer la réclamation</button>
    <a class="btn-secondaire" href="<?= BASE_URL ?>Reclamation/all">Annuler</a>
  </p>
</form>

<script>
  // Affiche seulement le champ utile au type choisi (le contrôle reste fait côté serveur).
  (function () {
    var type = document.getElementById('type');
    function maj() {
      document.querySelectorAll('[data-pour]').forEach(function (bloc) {
        bloc.hidden = bloc.getAttribute('data-pour').split(' ').indexOf(type.value) === -1;
      });
    }
    type.addEventListener('change', maj);
    maj();
  })();
</script>
