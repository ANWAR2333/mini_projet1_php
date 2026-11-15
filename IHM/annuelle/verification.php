<?php
$e  = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$dh = fn($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', ' '), '0'), ',');
$badges = [
    'conforme'      => ['badge-vert',  'Conforme'],
    'ecart'         => ['badge-rouge', 'Écart à vérifier'],
    'pas_de_releve' => ['badge-jaune', 'Pas de relevé annuel'],
    'pas_de_saisie' => ['badge-jaune', 'Aucune saisie mensuelle'],
];
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="calendar-check"></i> Vérification annuelle <?= (int) $annee ?></h1>
    <p class="sous-titre">Relevé de l’agent comparé à la somme des saisies mensuelles des clients (tolérance <?= (int) $tolerance ?> %)</p>
  </div>
  <div class="cercle"><?= (int) $nbEcarts ?><small>écart(s)</small></div>
</header>

<section class="carte">
  <form class="formulaire" method="get" action="<?= BASE_URL ?>Annuelle/verification">
    <label for="annee">Année</label>
    <input class="champ" type="number" id="annee" name="annee" min="2000" max="2100" value="<?= (int) $annee ?>">
    <button class="btn-secondaire" type="submit">Afficher</button>
    <a class="btn-secondaire" href="<?= BASE_URL ?>Annuelle/import">Import du fichier</a>
  </form>
  <div class="scroll-x">
    <table class="tableau">
      <thead>
        <tr>
          <th>Contrat</th><th>Client</th><th>Quartier</th><th>Mois saisis</th>
          <th>Eau saisie / relevée</th><th>Élec. saisie / relevée</th><th>Résultat</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lignes as $l): [$classe, $libelle] = $badges[$l['statut_verif']]; ?>
          <tr>
            <td>#<?= (int) $l['id_contrat'] ?><?= $l['statut'] === 'resilie' ? ' (résilié)' : '' ?></td>
            <td><?= $e($l['prenom'] . ' ' . $l['nom']) ?></td>
            <td><?= $e($l['quartier']) ?> <small><?= $e($l['type_activite']) ?></small></td>
            <td><?= (int) $l['nb_mois'] ?></td>
            <td><?= $dh($l['mensuel_eau']) ?> / <?= $dh($l['annuel_eau']) ?></td>
            <td><?= $dh($l['mensuel_elec']) ?> / <?= $dh($l['annuel_elec']) ?></td>
            <td><span class="badge <?= $classe ?>"><?= $libelle ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
