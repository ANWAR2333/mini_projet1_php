<?php
$e = fn($valeur) => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
$modification = $mode === 'update';
$action = $modification
    ? BASE_URL . 'Contrat/update/' . (int) $contratId
    : BASE_URL . 'Contrat/add/' . (int) $idClient;
$annulerUrl = BASE_URL . 'Client/one/' . (int) $idClient;
?>
<header class="carte carte-titre">
  <div>
    <h1><?= $modification ? 'Modifier un contrat' : 'Ajouter un contrat' ?></h1>
    <p class="sous-titre">Client : <?= $e($client['prenom'] . ' ' . $client['nom']) ?></p>
  </div>
</header>

<?php foreach ($erreurs as $erreur): ?>
  <div class="bande bande-erreur"><?= $e($erreur) ?></div>
<?php endforeach; ?>

<form class="carte formulaire" method="post" action="<?= $e($action) ?>">
  <div class="grille-2">
    <div>
      <label for="id_quartier">Quartier *</label>
      <select class="champ" id="id_quartier" name="id_quartier" required>
        <option value="">— Choisir —</option>
        <?php foreach ($quartiers as $quartier): ?>
          <option value="<?= (int) $quartier['id'] ?>"<?= (string) ($contrat['id_quartier'] ?? '') === (string) $quartier['id'] ? ' selected' : '' ?>>
            <?= $e($quartier['nom']) ?> (<?= $e($quartier['type_activite']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="date_debut">Date de début *</label>
      <input class="champ" type="date" id="date_debut" name="date_debut" required value="<?= $e($contrat['date_debut'] ?? date('Y-m-d')) ?>">
    </div>

    <div>
      <label for="num_compteur_eau">Numéro du compteur d’eau</label>
      <input class="champ" type="text" id="num_compteur_eau" name="num_compteur_eau" maxlength="50" value="<?= $e($contrat['num_compteur_eau'] ?? '') ?>">
    </div>

    <div>
      <label for="num_compteur_elec">Numéro du compteur d’électricité</label>
      <input class="champ" type="text" id="num_compteur_elec" name="num_compteur_elec" maxlength="50" value="<?= $e($contrat['num_compteur_elec'] ?? '') ?>">
    </div>

    <div>
      <label for="index_initial_eau">Index initial eau (m³) *</label>
      <input class="champ" type="number" id="index_initial_eau" name="index_initial_eau" min="0" step="0.01" required value="<?= $e($contrat['index_initial_eau'] ?? '0.00') ?>">
    </div>

    <div>
      <label for="index_initial_elec">Index initial électricité (kWh) *</label>
      <input class="champ" type="number" id="index_initial_elec" name="index_initial_elec" min="0" step="0.01" required value="<?= $e($contrat['index_initial_elec'] ?? '0.00') ?>">
    </div>

    <?php if ($modification): ?>
      <div>
        <label for="date_fin">Date de fin</label>
        <input class="champ" type="date" id="date_fin" name="date_fin" value="<?= $e($contrat['date_fin'] ?? '') ?>">
      </div>

      <div>
        <label for="statut">Statut *</label>
        <select class="champ" id="statut" name="statut" required>
          <option value="actif"<?= ($contrat['statut'] ?? 'actif') === 'actif' ? ' selected' : '' ?>>Actif</option>
          <option value="resilie"<?= ($contrat['statut'] ?? 'actif') === 'resilie' ? ' selected' : '' ?>>Résilié</option>
        </select>
      </div>
    <?php else: ?>
      <div>
        <label for="date_fin">Date de fin</label>
        <input class="champ" type="date" id="date_fin" name="date_fin" value="<?= $e($contrat['date_fin'] ?? '') ?>">
      </div>
      <div></div>
    <?php endif; ?>
  </div>

  <p>* Champs obligatoires.</p>
  <button class="btn-principal" type="submit"><?= $modification ? 'Enregistrer les modifications' : 'Créer le contrat' ?></button>
  <a class="btn-secondaire" href="<?= $e($annulerUrl) ?>">Annuler</a>
</form>
