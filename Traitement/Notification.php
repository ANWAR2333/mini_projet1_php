<?php
declare(strict_types=1);

/** Route Notification/all — liste des notifications du client connecté. */
function notificationAll(): void
{
    verifierRole('client');
    afficherVue('notification/index.php', [
        'titre'         => 'Notifications',
        'notifications' => bdNotificationByClient((int) $_SESSION['id']),
    ]);
}

/** Route Notification/read/{id} — marque une notification comme lue, puis revient à la liste. */
function notificationRead(int $id): void
{
    verifierRole('client');
    bdNotificationMarquerLue($id, (int) $_SESSION['id']);
    rediriger('Notification/all');
}

/**
 * Fonction PARTAGÉE : Amal (anomalie corrigée) et Chaimaa (réclamation traitée) l'appelleront.
 * Pas de verifierRole() ici : elle est appelée depuis du code fournisseur, pas une route cliente.
 */
function notificationAdd(int $idClient, string $message, ?int $idReclamation = null): void
{
    bdNotificationInsert($idClient, $message, $idReclamation);
}
