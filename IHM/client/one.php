<?php
// Échappe les informations du client avant de les afficher dans la page.
$e = fn($valeur) => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>
<!-- Présente les informations principales du client sélectionné. -->
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="user-round"></i> <?= $e($client['prenom'] . ' ' . $client['nom']) ?></h1>
    <p class="sous-titre">Détails du compte client</p>
  </div>
</header>

<!-- Détails personnels et informations des compteurs. -->
<section class="carte">
  <div class="grille-2">
    <p><strong>Email :</strong> <?= $e($client['email']) ?></p>
    <p><strong>Téléphone :</strong> <?= $e($client['telephone'] ?: '—') ?></p>
    <p><strong>Adresse :</strong> <?= $e($client['adresse'] ?: '—') ?></p>
    <p><strong>Créé le :</strong> <?= $e($client['date_creation'] ?: '—') ?></p>
    <p><strong>Compteur eau :</strong> <?= $e($client['num_compteur_eau'] ?: '—') ?></p>
    <p><strong>Compteur électricité :</strong> <?= $e($client['num_compteur_elec'] ?: '—') ?></p>
    <p><strong>Index initial eau :</strong> <?= $e($client['index_initial_eau']) ?> m³</p>
    <p><strong>Index initial électricité :</strong> <?= $e($client['index_initial_elec']) ?> kWh</p>
  </div>
  <p>
    <a class="btn-principal" href="<?= BASE_URL ?>Client/update/<?= (int) $client['id'] ?>">Modifier</a>
    <a class="btn-secondaire" href="<?= BASE_URL ?>Client/all">Retour à la liste</a>
  </p>
</section>

<!-- La suppression est envoyée en POST après confirmation. -->
<section class="carte">
  <h2>Supprimer ce client</h2>
  <p>La suppression est impossible si des consommations, factures ou autres données sont associées à ce compte.</p>
  <form method="post" action="<?= BASE_URL ?>Client/delete/<?= (int) $client['id'] ?>"
        onsubmit="return confirm('Confirmer la suppression de ce client ?');">
    <button class="btn-principal" type="submit">Supprimer le client</button>
  </form>
</section>
