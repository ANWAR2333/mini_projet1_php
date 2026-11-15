<?php
$entiteCourante = $GLOBALS['routeCourante']['entite'] ?? '';
$actionCourante = $GLOBALS['routeCourante']['action'] ?? '';
$menu = [
    ['Tableau de Bord',  'layout-dashboard', 'Dashboard/all',     $entiteCourante === 'Dashboard'],
    ['Clients',          'users',            'Client/all',        in_array($entiteCourante, ['Client', 'Contrat'], true)],
    ['Quartiers',        'map-pin',          'Quartier/all',      $entiteCourante === 'Quartier'],
    ['Types d\'activité','layers',           'TypeActivite/all',  in_array($entiteCourante, ['Typeactivite', 'Tranche'], true)],
    ['Anomalies',        'circle-alert',     'Anomalie/all',      $entiteCourante === 'Anomalie'],
    ['Réclamations',     'message-square',   'Reclamation/admin', $entiteCourante === 'Reclamation'],
    ['Factures',         'receipt',          'Facture/admin',     in_array($entiteCourante, ['Facture', 'Paiement'], true)],
    ['Relevé annuel',    'calendar-check',   'Annuelle/import',   $entiteCourante === 'Annuelle'],
    ['Paramètres',       'settings',         'Parametre/update',  $entiteCourante === 'Parametre'],
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
