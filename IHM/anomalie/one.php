<?php
$e  = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$nb = fn($v) => number_format((float) $v, 2, ',', ' ');
$photoDe = ['eau' => 'photo_eau', 'electricite' => 'photo_elec'];
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="circle-alert"></i> Saisie en anomalie</h1>
    <p class="sous-titre">
      <?= $e($saisie['prenom'] . ' ' . $saisie['nom']) ?> · contrat n°<?= (int) $saisie['id_contrat'] ?> ·
      <?= $e($saisie['quartier']) ?> (<?= $e($saisie['type_activite']) ?>) · <?= $e($periode) ?>
    </p>
    <p><a class="btn-secondaire" href="<?= BASE_URL ?>Anomalie/all">← Retour aux anomalies</a></p>
  </div>
  <div class="cercle">
    <?= count($reste) ?>
    <small>à traiter</small>
  </div>
</header>

<?php foreach ($erreurs as $msg): ?>
  <div class="message message-erreur"><?= $e($msg) ?></div>
<?php endforeach; ?>

<form method="post" action="">
  <div class="grille-2">
    <?php foreach ($anomalies as $a): $traitee = $a['statut'] === 'corrigee'; $photo = $saisie[$photoDe[$a['service']]] ?? null; ?>
      <div class="carte">
        <h2><?= $e(ANOMALIE_LIB_SERVICE[$a['service']]) ?> — <?= $e(ANOMALIE_LIB_TYPE[$a['type']]) ?></h2>

        <p>
          <small>Index précédent</small> <strong><?= $e($nb($a['index_precedent'])) ?> <?= $e(ANOMALIE_UNITE[$a['service']]) ?></strong><br>
          <small>Index saisi par le client</small> <strong><?= $e($nb($a['index_saisi'])) ?> <?= $e(ANOMALIE_UNITE[$a['service']]) ?></strong>
          <?php if ($a['type'] === 'depasse_seuil'): ?>
            <br><small>Seuil du type « <?= $e($saisie['type_activite']) ?> »</small>
            <strong><?= $e($nb($a['service'] === 'eau' ? $saisie['seuil_eau'] : $saisie['seuil_elec'])) ?> <?= $e(ANOMALIE_UNITE[$a['service']]) ?></strong>
          <?php endif; ?>
        </p>

        <?php if ($photo): ?>
          <p><small>Photo du compteur envoyée par le client</small><br>
             <img src="<?= $e(BASE_URL . $photo) ?>" alt="Photo du compteur"
                  style="max-width:100%;max-height:220px;border-radius:8px"></p>
        <?php endif; ?>

        <?php if ($traitee): ?>
          <p><span class="badge badge-vert">Corrigée</span>
             Index retenu : <strong><?= $e($nb($a['index_corrige'])) ?> <?= $e(ANOMALIE_UNITE[$a['service']]) ?></strong>
             <?php if ($a['date_correction']): ?>
               <small>(le <?= $e(date('d/m/Y', strtotime($a['date_correction']))) ?>)</small>
             <?php endif; ?></p>
        <?php else: ?>
          <label for="index_<?= (int) $a['id'] ?>">Index à retenir (<?= $e(ANOMALIE_UNITE[$a['service']]) ?>)</label>
          <input type="text" inputmode="decimal" class="champ" id="index_<?= (int) $a['id'] ?>"
                 name="index_<?= (int) $a['id'] ?>" value="<?= $e($old[$a['id']]) ?>" required>
          <small class="aide">Gardez l'index saisi s'il est correct, sinon corrigez-le d'après la photo. Il ne peut pas être inférieur à l'index précédent.</small>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($reste): ?>
    <p><button type="submit" class="btn-principal">Valider et générer la facture</button></p>
  <?php elseif ($idFacture): ?>
    <div class="carte">
      <p>La facture de cette saisie est générée.</p>
      <a class="btn-secondaire" href="<?= BASE_URL ?>Facture/pdf/<?= (int) $idFacture ?>">Télécharger la facture (PDF)</a>
    </div>
  <?php else: ?>
    <div class="carte">
      <p>Les anomalies sont corrigées mais la facture n'a pas encore été générée.</p>
      <button type="submit" class="btn-principal">Générer la facture</button>
    </div>
  <?php endif; ?>
</form>
