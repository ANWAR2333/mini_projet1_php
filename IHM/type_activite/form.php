<?php
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$modification = $mode === 'update';
$action = $modification
    ? BASE_URL . 'TypeActivite/update/' . (int) $typeId
    : BASE_URL . 'TypeActivite/add';
?>
<header class="carte carte-titre">
  <div>
    <h1><?= $modification ? 'Modifier un type d’activité' : 'Ajouter un type d’activité' ?></h1>
    <p class="sous-titre">Les seuils s’appliquent aux contrats des quartiers associés à ce type.</p>
  </div>
</header>

<?php foreach ($erreurs as $erreur): ?>
  <div class="bande bande-erreur"><?= $e($erreur) ?></div>
<?php endforeach; ?>

<form class="carte formulaire" method="post" action="<?= $e($action) ?>">
  <div class="grille-2">
    <div>
      <label for="nom">Nom du type *</label>
      <input class="champ" type="text" id="nom" name="nom" maxlength="50" required value="<?= $e($type['nom']) ?>">
    </div>
    <div>
      <label for="seuil_eau">Seuil eau (m³/mois) *</label>
      <input class="champ" type="number" id="seuil_eau" name="seuil_eau" min="0.01" max="99999999.99" step="0.01" required value="<?= $e($type['seuil_eau']) ?>">
    </div>
    <div>
      <label for="seuil_elec">Seuil électricité (kWh/mois) *</label>
      <input class="champ" type="number" id="seuil_elec" name="seuil_elec" min="0.01" max="99999999.99" step="0.01" required value="<?= $e($type['seuil_elec']) ?>">
    </div>
  </div>
  <p>* Champs obligatoires. Les barèmes tarifaires sont configurés séparément pour chaque type.</p>
  <button class="btn-principal" type="submit"><?= $modification ? 'Enregistrer les modifications' : 'Créer le type' ?></button>
  <a class="btn-secondaire" href="<?= $modification ? BASE_URL . 'TypeActivite/one/' . (int) $typeId : BASE_URL . 'TypeActivite/all' ?>">Annuler</a>
</form>
