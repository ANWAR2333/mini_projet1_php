<?php
/**
 * IHM/notification/index.php — Farah Bel Aali
 * Variables reçues de notificationAll() : $notifications (array)
 */
$e = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
?>
<header class="carte carte-titre">
  <div>
    <h1><i data-lucide="bell"></i> Notifications</h1>
    <p class="sous-titre">Vos alertes et messages du fournisseur</p>
  </div>
</header>

<section class="carte">
  <?php if (empty($notifications)): ?>
    <p>Vous n'avez aucune notification.</p>
  <?php else: ?>
    <?php foreach ($notifications as $n): ?>
      <div class="carte" style="margin-bottom: 0.5rem;">
        <p><?= $e($n['message']) ?></p>
        <small><?= date('d/m/Y H:i', strtotime($n['date_envoi'])) ?></small>
        <?php if (!$n['lue']): ?>
          <form method="post" action="<?= BASE_URL ?>Notification/read/<?= (int) $n['id'] ?>">
            <button type="submit" class="btn-secondaire">Marquer comme lue</button>
          </form>
        <?php else: ?>
          <span class="badge badge-vert">Lue</span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
