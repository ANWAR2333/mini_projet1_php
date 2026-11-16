<?php
// Prépare l'échappement et adapte l'action du formulaire au mode choisi.
$e = fn($valeur) => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
$modification = $mode === 'update';
$action = $modification
    ? BASE_URL . 'Client/update/' . (int) $clientId
    : BASE_URL . 'Client/add';
?>
<!-- Affiche le titre et les éventuelles erreurs de validation. -->
<header class="carte carte-titre">
  <div>
    <h1><?= $modification ? 'Modifier un client' : 'Ajouter un client' ?></h1>
    <p class="sous-titre">
      Renseignez les informations du compte client.
      Les compteurs et index initiaux se définissent dans chaque contrat.
    </p>
  </div>
</header>

<?php foreach ($erreurs as $erreur): ?>
  <div class="bande bande-erreur"><?= $e($erreur) ?></div>
<?php endforeach; ?>

<!-- Le même formulaire sert à créer et à modifier un client. -->
<form class="carte formulaire" method="post" action="<?= $e($action) ?>">
  <div class="grille-2">
    <div>
      <label for="prenom">Prénom *</label>
      <input class="champ" type="text" id="prenom" name="prenom" maxlength="100" required value="<?= $e($client['prenom']) ?>">
    </div>
    <div>
      <label for="nom">Nom *</label>
      <input class="champ" type="text" id="nom" name="nom" maxlength="100" required value="<?= $e($client['nom']) ?>">
    </div>
    <div>
      <label for="email">Email *</label>
      <input class="champ" type="email" id="email" name="email" maxlength="150" required value="<?= $e($client['email']) ?>">
    </div>
    <?php if (!$modification): ?>
      <!-- Le mot de passe est demandé uniquement lors de la création. -->
      <div>
        <label for="mot_de_passe">Mot de passe *</label>
        <input class="champ" type="password" id="mot_de_passe" name="mot_de_passe" minlength="8" required autocomplete="new-password">
      </div>
    <?php endif; ?>
    <div>
      <label for="telephone">Téléphone</label>
      <input class="champ" type="tel" id="telephone" name="telephone" maxlength="20" value="<?= $e($client['telephone']) ?>">
    </div>
    <div>
      <label for="adresse">Adresse</label>
      <input class="champ" type="text" id="adresse" name="adresse" maxlength="255" value="<?= $e($client['adresse']) ?>">
    </div>
  </div>
  <p>* Champs obligatoires.</p>
  <button class="btn-principal" type="submit"><?= $modification ? 'Enregistrer les modifications' : 'Créer le client' ?></button>
  <a class="btn-secondaire" href="<?= BASE_URL ?>Client/all">Annuler</a>
</form>
