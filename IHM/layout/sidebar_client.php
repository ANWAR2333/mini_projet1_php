<?php
$entiteCourante = $GLOBALS['routeCourante']['entite'] ?? '';
$menu = [
    ['Consommation', 'gauge',          'Consommation/add',            $entiteCourante === 'Consommation'],
    ['Mes Factures', 'file-text',      'Facture/all',                 $entiteCourante === 'Facture'],
    ['Réclamations', 'message-square', 'Auth/bientot/reclamations',   false],
    ['Notifications','bell',           'Auth/bientot/notifications',  false],
];
?>
<aside class="sidebar">
  <a class="logo" href="<?= BASE_URL ?>Consommation/add"><img src="<?= BASE_URL ?>assets/img/logo.svg" alt="eco house."></a>
  <nav>
    <?php foreach ($menu as [$libelle, $icone, $route, $actif]): ?>
      <a class="menu-btn<?= $actif ? ' actif' : '' ?>" href="<?= BASE_URL . $route ?>">
        <i data-lucide="<?= $icone ?>"></i><span><?= htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <a class="deconnexion" href="<?= BASE_URL ?>Auth/logout"><span>Déconnexion</span><i data-lucide="log-out"></i></a>
</aside>
