<?php
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$modification = $mode === 'update';
$action = $modification ? BASE_URL . 'Quartier/update/' . (int) $quartierId : BASE_URL . 'Quartier/add';
?>
<header class="carte carte-titre">
  <div>
    <h1><?= $modification ? 'Modifier un quartier' : 'Ajouter un quartier' ?></h1>
    <p class="sous-titre">Le type d’activité détermine les tarifs et les seuils des contrats du quartier</p>
  </div>
</header>

<?php foreach ($erreurs as $erreur): ?>
  <div class="bande bande-erreur"><?= $e($erreur) ?></div>
<?php endforeach; ?>

<form class="carte formulaire" method="post" action="<?= $e($action) ?>">
  <div class="grille-2">
    <div>
      <label for="nom">Nom du quartier *</label>
      <input class="champ" type="text" id="nom" name="nom" maxlength="100" required value="<?= $e($quartier['nom']) ?>">
    </div>
    <div>
      <label for="id_type_activite">Type d’activité *</label>
      <select class="champ" id="id_type_activite" name="id_type_activite" required>
        <option value="">— Choisir —</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= (int) $t['id'] ?>"<?= (string) $quartier['id_type_activite'] === (string) $t['id'] ? ' selected' : '' ?>>
            <?= $e(ucfirst($t['nom'])) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <p>* Champs obligatoires.</p>
  <button class="btn-principal" type="submit"><?= $modification ? 'Enregistrer les modifications' : 'Créer le quartier' ?></button>
  <a class="btn-secondaire" href="<?= BASE_URL ?>Quartier/all">Annuler</a>
</form>
