<?php
$e  = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$dh = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ' '), '0'), ',');
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="calendar-check"></i> Relevé annuel</h1>
    <p class="sous-titre">Consommation annuelle relevée par l’agent lors de sa tournée</p>
  </div>
</header>

<section class="carte">
  <h2>Import du fichier de l’agent</h2>
  <p>L’agent dépose <strong><?= $e($chemin) ?></strong> sur le serveur : il est importé automatiquement à l’ouverture de cette page ou du tableau de bord, puis archivé.</p>
  <p class="aide">Format, une ligne par contrat : <code>ID_Contrat;ID_Agent;ConsommationEau;ConsommationElect;Annee;Date_Saisie</code></p>
  <form class="formulaire" method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>Annuelle/import">
    <label for="fichier">Ou déposer le fichier ici (.txt)</label>
    <input class="champ" type="file" id="fichier" name="fichier" accept=".txt" required>
    <button class="btn-principal" type="submit">Importer</button>
    <a class="btn-secondaire" href="<?= BASE_URL ?>Annuelle/verification">Vérification annuelle</a>
  </form>
</section>

<?php if ($rapport && $rapport['erreurs']): ?>
  <section class="carte">
    <h2>Lignes en erreur</h2>
    <?php foreach ($rapport['erreurs'] as $erreur): ?>
      <p><?= $e($erreur) ?></p>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<section class="carte">
  <h2>Relevés de <?= (int) $annee ?></h2>
  <form class="formulaire" method="get" action="<?= BASE_URL ?>Annuelle/import">
    <label for="annee">Année</label>
    <input class="champ" type="number" id="annee" name="annee" min="2000" max="2100" value="<?= (int) $annee ?>">
    <button class="btn-secondaire" type="submit">Afficher</button>
  </form>
  <?php if (!$releves): ?>
    <p>Aucun relevé annuel pour cette année.</p>
  <?php else: ?>
    <div class="scroll-x">
      <table class="tableau">
        <thead><tr><th>Contrat</th><th>Client</th><th>Eau (m³)</th><th>Électricité (kWh)</th><th>Agent</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($releves as $r): ?>
            <tr>
              <td>#<?= (int) $r['id_contrat'] ?></td>
              <td><?= $e($r['prenom'] . ' ' . $r['nom']) ?></td>
              <td><?= $dh($r['conso_eau']) ?></td>
              <td><?= $dh($r['conso_elec']) ?></td>
              <td><?= $e($r['agent_prenom'] . ' ' . $r['agent_nom']) ?></td>
              <td><?= $e(date('d/m/Y', strtotime($r['date_saisie']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
