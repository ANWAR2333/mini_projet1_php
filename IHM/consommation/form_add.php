<?php

$e    = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$base = defined('URL_BASE') ? URL_BASE : '';
$nb   = fn($v) => number_format((float) $v, 2, ',', ' ');
$moisCourt = mb_substr($nomMois, 0, 3, 'UTF-8') . '.';

$compteurs = [
    'eau'  => ['titre' => 'Eau',         'icone' => 'droplet', 'unite' => 'm³',  'index' => 'index_eau',  'photo' => 'photo_eau'],
    'elec' => ['titre' => 'Électricité', 'icone' => 'zap',     'unite' => 'kWh', 'index' => 'index_elec', 'photo' => 'photo_elec'],
];
?>
<div class="carte-titre">
    <div class="carte-titre-texte">
        <h1><i data-lucide="gauge"></i> Ma consommation</h1>
        <p class="sous-titre">Saisie ouverte du 18 au <?= (int) $dernierJour ?> <?= $e(mb_strtolower($nomMois, 'UTF-8')) ?></p>
    </div>
    <div class="cercle"><span class="cercle-valeur"><?= $e($moisCourt) ?></span><small class="cercle-legende"><?= (int) $annee ?></small></div>
</div>

<?php foreach ($erreurs as $msg): ?>
    <div class="message message-erreur"><?= $e($msg) ?></div>
<?php endforeach; ?>

<?php if (!$ouverte): ?>
    <div class="carte">
        <p>La saisie est ouverte du 18 au <?= (int) $dernierJour ?> <?= $e(mb_strtolower($nomMois, 'UTF-8')) ?>.
           Revenez à partir du 18.</p>
    </div>
    <?php return; ?>
<?php endif; ?>

<?php if ($dejaSaisi): ?>
    <div class="carte">
        <p>Vous avez déjà saisi votre consommation pour ce mois</p>
        <a class="btn-principal" href="<?= $e($base) ?>Consommation/all">Voir mon historique</a>
    </div>
    <?php return; ?>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data" id="form-saisie" novalidate>
    <div class="grille-2">
        <?php foreach ($compteurs as $cle => $c): ?>
            <div class="carte">
                <h2><i data-lucide="<?= $e($c['icone']) ?>"></i> <?= $e($c['titre']) ?></h2>

                <div class="dernier-releve">
                    <div class="photo-precedente">
                        <?php if ($prec['photo_' . $cle]): ?>
                            <img src="<?= $e($base . $prec['photo_' . $cle]) ?>" alt="Photo du compteur du mois dernier">
                        <?php else: ?>
                            <i data-lucide="camera"></i>
                            <span><?= $prec['initial'] ? 'Aucune photo' : $e($nomMoisPrec) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <small>Index du mois dernier</small>
                        <strong class="gros-chiffre"><?= $nb($prec[$c['index']]) ?> <?= $e($c['unite']) ?></strong>
                        <small>
                            <?= $prec['initial']
                                ? 'Index initial du compteur'
                                : 'Relevé le ' . $e(date('d/m/Y', strtotime($prec['date_saisie']))) ?>
                        </small>
                    </div>
                </div>

                <label for="<?= $e($c['index']) ?>">Nouvel index (<?= $e($c['unite']) ?>)</label>
                <input type="text" inputmode="decimal" class="champ" id="<?= $e($c['index']) ?>" name="<?= $e($c['index']) ?>"
                       value="<?= $e($old[$c['index']]) ?>" placeholder="Ex : <?= $cle === 'eau' ? '1260' : '8550' ?>"
                       data-precedent="<?= $e($prec[$c['index']]) ?>" data-unite="<?= $e($c['unite']) ?>" data-type="<?= $e($cle) ?>" required>

                <label class="btn-upload" for="<?= $e($c['photo']) ?>">
                    <i data-lucide="upload"></i> <span id="nom_<?= $e($c['photo']) ?>">Téléverser la photo du compteur</span>
                </label>
                <input type="file" class="input-cache" id="<?= $e($c['photo']) ?>" name="<?= $e($c['photo']) ?>"
                       accept="image/jpeg,image/png" required>
                <small class="aide">JPG ou PNG, 5 Mo maximum</small>

                <div class="ligne-conso">
                    <span>Consommation du mois</span>
                    <strong id="conso_<?= $e($cle) ?>">—</strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="note-info"><i data-lucide="info"></i>
        La saisie sera vérifiée par le fournisseur si le nouvel index est inférieur au mois dernier, identique au mois dernier
        (consommation nulle) ou s'il dépasse le seuil (<?= $e($nb($param['seuil_eau'])) ?> m³ d'eau, <?= $e($nb($param['seuil_elec'])) ?> kWh d'électricité).
    </p>

    <div class="barre-estimation">
        <div><small>Montant HT</small><strong id="est_ht">0,00 DH</strong></div>
        <div><small>TVA <?= $e(rtrim(rtrim(number_format($param['taux_tva'] * 100, 2, ',', ''), '0'), ',')) ?> %</small><strong id="est_tva">0,00 DH</strong></div>
        <div><small>Total TTC</small><strong id="est_ttc" class="gros-chiffre">0,00 DH</strong></div>
        <button type="submit" class="btn-principal">Valider ma saisie</button>
    </div>
</form>

<script>
// Estimation affichée à titre indicatif : le calcul qui compte est fait en PHP (R6, R7).
(function () {
    var TAUX = <?= json_encode((float) $param['taux_tva']) ?>;
    var TARIFS = {                       // [consommation maximale de la tranche, prix unitaire]
        eau:  [[6, 2.37], [20, 7.39], [35, 10.98], [Infinity, 11.03]],
        elec: [[100, 0.82], [150, 0.92], [Infinity, 1.10]]
    };
    var arrondi = function (x) { return Math.round(x * 100) / 100; };
    var fmt = function (x) { return x.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };

    function montant(conso, type) {
        if (conso <= 0) { return 0; }
        for (var i = 0; i < TARIFS[type].length; i++) {
            if (conso <= TARIFS[type][i][0]) { return arrondi(conso * TARIFS[type][i][1]); }
        }
        return 0;
    }

    function maj() {
        var ht = 0;
        document.querySelectorAll('input[data-type]').forEach(function (input) {
            var type = input.dataset.type;
            var saisi = parseFloat(input.value.replace(/\s/g, '').replace(',', '.'));
            var sortie = document.getElementById('conso_' + type);
            if (isNaN(saisi)) { sortie.textContent = '—'; return; }
            var conso = arrondi(saisi - parseFloat(input.dataset.precedent));
            sortie.textContent = fmt(conso) + ' ' + input.dataset.unite;
            ht += montant(conso, type);
        });
        var tva = arrondi(ht * TAUX);
        document.getElementById('est_ht').textContent  = fmt(ht) + ' DH';
        document.getElementById('est_tva').textContent = fmt(tva) + ' DH';
        document.getElementById('est_ttc').textContent = fmt(arrondi(ht + tva)) + ' DH';
    }

    document.querySelectorAll('input[data-type]').forEach(function (i) { i.addEventListener('input', maj); });
    ['photo_eau', 'photo_elec'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', function () {
            document.getElementById('nom_' + id).textContent = this.files.length ? this.files[0].name : 'Téléverser la photo du compteur';
        });
    });
    maj();
})();
</script>
