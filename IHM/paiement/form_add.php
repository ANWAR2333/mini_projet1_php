<?php
// Variables reçues de paiementAdd() : $facture, $modes, $date, $mode, $erreurs
$e        = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$moisNoms = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$dh       = fn($x) => number_format((float) $x, 2, ',', ' ') . ' DH';
$f        = $facture;
?>
<p><a href="<?= BASE_URL ?>Facture/admin">← Retour aux factures</a></p>

<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="banknote"></i> Enregistrer un paiement</h1>
    <p class="sous-titre">Facture N° <?= $e($f['numero']) ?></p>
  </div>
</header>

<section class="carte">
  <p><strong>Client :</strong> <?= $e($f['prenom'] . ' ' . $f['nom']) ?></p>
  <p><strong>Période :</strong> <?= $moisNoms[(int) $f['mois'] - 1] . ' ' . (int) $f['annee'] ?></p>

  <form class="formulaire" method="post" action="<?= BASE_URL ?>Paiement/add/<?= (int) $f['id'] ?>">
    <label for="montant">Montant (TTC)</label>
    <input type="text" id="montant" value="<?= $dh($f['montant_ttc']) ?>" readonly>

    <label for="date_paiement">Date du paiement</label>
    <input type="date" id="date_paiement" name="date_paiement" value="<?= $e($date) ?>" max="<?= date('Y-m-d') ?>" required>
    <?php if (isset($erreurs['date'])): ?>
      <p class="erreur-champ"><?= $e($erreurs['date']) ?></p>
    <?php endif; ?>

    <label for="mode">Mode de paiement</label>
    <select id="mode" name="mode">
      <?php foreach ($modes as $valeur => $libelle): ?>
        <option value="<?= $valeur ?>" <?= $mode === $valeur ? 'selected' : '' ?>><?= $libelle ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($erreurs['mode'])): ?>
      <p class="erreur-champ"><?= $e($erreurs['mode']) ?></p>
    <?php endif; ?>

    <p>
      <button type="submit" class="btn-principal">Enregistrer le paiement</button>
      <a class="btn-secondaire" href="<?= BASE_URL ?>Facture/admin">Annuler</a>
    </p>
  </form>
</section>