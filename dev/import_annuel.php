<?php
declare(strict_types=1);
/**
 * dev/import_annuel.php — import du fichier annuel SANS navigateur (tâche planifiée).
 * Lancer : php dev/import_annuel.php
 * Windows (Planificateur de tâches) : C:\xampp\php\php.exe C:\xampp\htdocs\gestion_factures\dev\import_annuel.php
 */
require __DIR__ . '/../BD/Connexion.php';
require __DIR__ . '/../BD/Contrat.php';
require __DIR__ . '/../BD/Annuelle.php';
require __DIR__ . '/../Traitement/Annuelle.php';

$r = annuelleImporterAuto();
echo $r === null ? "Aucun fichier à importer.\n" : annuelleMessage($r)[0] . "\n";
foreach ($r['erreurs'] ?? [] as $erreur) {
    echo "  - $erreur\n";
}
