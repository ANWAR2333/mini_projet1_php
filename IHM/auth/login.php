<?php /* Écran 1 : connexion. Variables : $email (valeur à ré-afficher après un échec). */ ?>
<div class="auth-cadre">
  <section class="auth-hero">
    <img class="auth-logo" src="<?= BASE_URL ?>assets/img/logo.svg" alt="eco house.">
    <h1>Vos factures d'eau et d'électricité, en ligne</h1>
    <ul>
      <li>Factures générées automatiquement après validation</li>
      <li>Saisie entre le 18 et la fin de chaque mois</li>
      <li>Réclamations suivies en temps réel</li>
    </ul>
  </section>
  <section class="auth-zone">
    <form class="auth-carte" method="post" action="<?= BASE_URL ?>Auth/login">
      <h2>Se Connecter</h2>
      <label for="email">Adresse Email</label>
      <input class="champ" type="email" id="email" name="email" placeholder="Ex fournisseur: fournisseur@ecohouse.ma"
             value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
      <label for="mdp">Mot de Passe</label>
      <input class="champ" type="password" id="mdp" name="mot_de_passe" placeholder="Ex fournisseur: Four#2026" required>
      <p class="aide">
  Fournisseur : fournisseur@ecohouse.ma / Four#2026<br>
  Client : salma@gmail.com / Client#2026
</p>
      <button class="btn-principal" type="submit">Se Connecter</button>
    </form>
  </section>
</div>
