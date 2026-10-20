<?php /* Page « Bientôt disponible ». Variable : $module (nom du module non livré en alpha). */ ?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="clock"></i> Bientôt disponible</h1>
    <p class="sous-titre"><?= htmlspecialchars($module ?? 'Cette page', ENT_QUOTES, 'UTF-8') ?> sera disponible dans une prochaine version.</p>
  </div>
</header>
