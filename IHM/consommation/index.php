<?php

$e  = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$nb = fn($v) => number_format((float) $v, 2, ',', ' ');
$base = BASE_URL;
?>
<div class="carte-titre">
    <div class="carte-titre-texte">
        <h1><i data-lucide="history"></i> Mes saisies</h1>
        <p class="sous-titre">Historique de vos consommations</p>
    </div>
    <div class="cercle"><span class="cercle-valeur"><?= count($historique) ?></span><small class="cercle-legende">saisies</small></div>
</div>

<div class="carte">
    <?php if (!$historique): ?>
        <p>Aucune saisie pour le moment.</p>
        <a class="btn-principal" href="<?= $e($base) ?>Consommation/add">Saisir ma consommation</a>
    <?php else: ?>
        <table class="tableau">
            <thead>
                <tr>
                    <th>Mois</th><th>Index eau</th><th>Index élec.</th>
                    <th>Conso. eau</th><th>Conso. élec.</th><th>Statut</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($historique as $l): ?>
                <tr>
                    <td><?= $e($l['periode']) ?></td>
                    <td><?= $e($nb($l['index_eau'])) ?> m³</td>
                    <td><?= $e($nb($l['index_elec'])) ?> kWh</td>
                    <td><?= $e($nb($l['conso_eau'])) ?> m³</td>
                    <td><?= $e($nb($l['conso_elec'])) ?> kWh</td>
                    <td>
                        <span class="badge <?= $e($l['badge']) ?>"><?= $e($l['statut']) ?></span>
                        <?php foreach ($l['motifs'] as $motif): ?>
                            <small class="motif"><?= $e($motif) ?></small>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>