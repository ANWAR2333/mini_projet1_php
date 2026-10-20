<?php
$entiteCourante = $GLOBALS['routeCourante']['entite'] ?? '';
$actionCourante = $GLOBALS['routeCourante']['action'] ?? '';
$menu = [
    ['Tableau de Bord','layout-dashboard','Auth/bientot/tableau-de-bord', false],
    ['Clients',        'users',           'Client/all',                   $entiteCourante === 'Client'],
    ['Anomalies',      'circle-alert',    'Auth/bientot/anomalies',       false],
    ['Réclamations',   'message-square',  'Auth/bientot/reclamations',    false],
    ['Factures',       'receipt',         'Facture/admin',                in_array($entiteCourante, ['Facture', 'Paiement'], true)],
];
?>
<aside class="sidebar">
  <a class="logo" href="<?= BASE_URL ?>Client/all"><img src="<?= BASE_URL ?>assets/img/logo.svg" alt="eco house."></a>
  <nav>
    <?php foreach ($menu as [$libelle, $icone, $route, $actif]): ?>
      <a class="menu-btn<?= $actif ? ' actif' : '' ?>" href="<?= BASE_URL . $route ?>">
        <i data-lucide="<?= $icone ?>"></i><span><?= htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8') ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <a class="deconnexion" href="<?= BASE_URL ?>Auth/logout"><span>Déconnexion</span><i data-lucide="log-out"></i></a>
</aside>
