<?php $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="settings"></i> Paramètres</h1>
    <p class="sous-titre">TVA et seuils d’anomalie définis par le fournisseur</p>
  </div>
</header>

<?php foreach ($erreurs as $erreur): ?>
  <div class="bande bande-erreur"><?= $e($erreur) ?></div>
<?php endforeach; ?>

<form class="carte formulaire" method="post" action="<?= BASE_URL ?>Parametre/update">
  <h2>TVA</h2>
  <label for="tva">Taux de TVA (%) *</label>
  <input class="champ" type="number" id="tva" name="tva" min="0" max="100" step="1" required value="<?= $e($tvaPct) ?>">

  <h2>Seuils d’anomalie par type d’activité</h2>
  <p class="aide">Une consommation mensuelle supérieure au seuil déclenche une anomalie « dépasse le seuil ».</p>
  <div class="scroll-x">
    <table class="tableau">
      <thead><tr><th>Type d’activité</th><th>Seuil eau (m³)</th><th>Seuil électricité (kWh)</th></tr></thead>
      <tbody>
        <?php foreach ($types as $t): $id = (int) $t['id']; ?>
          <tr>
            <td><?= $e(ucfirst($t['nom'])) ?></td>
            <td><input class="champ" type="number" name="seuil_eau[<?= $id ?>]" min="0.01" step="0.01" required value="<?= $e($seuils[$id]['eau']) ?>"></td>
            <td><input class="champ" type="number" name="seuil_elec[<?= $id ?>]" min="0.01" step="0.01" required value="<?= $e($seuils[$id]['elec']) ?>"></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <button class="btn-principal" type="submit">Enregistrer</button>
</form>
