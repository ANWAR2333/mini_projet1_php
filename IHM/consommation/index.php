<?php

$e  = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$nb = fn($v) => number_format((float) $v, 2, ',', ' ');
$base = BASE_URL;
?>
<div class="carte-titre">
    <div class="carte-titre-texte">
        <h1><i data-lucide="history"></i> Mes saisies</h1>
        <p class="sous-titre">Historique de vos consommations, contrat par contrat</p>
    </div>
    <div class="cercle"><span class="cercle-valeur"><?= (int) $total ?></span><small class="cercle-legende">saisies</small></div>
</div>

<?php if (!$groupes): ?>
    <div class="carte">
        <p>Aucun contrat pour le moment.</p>
    </div>
<?php endif; ?>

<?php foreach ($groupes as $g): $ct = $g['contrat']; $historique = $g['historique']; ?>
    <div class="carte">
        <h2>
            Contrat n°<?= (int) $ct['id'] ?> — <?= $e($ct['quartier']) ?> (<?= $e($ct['type_activite']) ?>)
            <?php if ($ct['statut'] === 'resilie'): ?>
                <span class="badge badge-rouge">Résilié</span>
            <?php endif; ?>
        </h2>
        <small><?= $e($ct['num_compteur_eau']) ?> / <?= $e($ct['num_compteur_elec']) ?></small>

        <?php if (!$historique): ?>
            <p>Aucune saisie pour ce contrat.</p>
            <?php if ($ct['statut'] === 'actif'): ?>
                <a class="btn-principal" href="<?= $e($base) ?>Consommation/add?id_contrat=<?= (int) $ct['id'] ?>">Saisir ma consommation</a>
            <?php endif; ?>
        <?php else: ?>
            <div class="scroll-x">
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
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
