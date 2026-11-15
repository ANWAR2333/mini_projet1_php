<?php

$e    = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$base = BASE_URL;
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
        <p><a class="btn-secondaire" href="<?= $e($base) ?>Consommation/all">Voir mes saisies</a></p>
    </div>
    <div class="cercle"><span class="cercle-valeur"><?= $e($moisCourt) ?></span><small class="cercle-legende"><?= (int) $annee ?></small></div>
</div>

<?php foreach ($erreurs as $msg): ?>
    <div class="message message-erreur"><?= $e($msg) ?></div>
<?php endforeach; ?>

<?php if (!$contrats): ?>
    <div class="carte">
        <p>Vous n'avez aucun contrat actif : la saisie n'est pas possible.
           Contactez le fournisseur pour activer un contrat.</p>
        <a class="btn-secondaire" href="<?= $e($base) ?>Contrat/all">Voir mes contrats</a>
    </div>
    <?php return; ?>
<?php endif; ?>

<?php if (!$ouverte): ?>
    <div class="carte">
        <p>La saisie est ouverte du 18 au <?= (int) $dernierJour ?> <?= $e(mb_strtolower($nomMois, 'UTF-8')) ?>.
           Revenez à partir du 18.</p>
    </div>
    <?php return; ?>
<?php endif; ?>

<?php /* Un client peut avoir plusieurs contrats : on choisit celui sur lequel on saisit. */ ?>
<?php if (count($contrats) > 1): ?>
    <form method="get" action="<?= $e($base) ?>Consommation/add" class="carte formulaire">
        <label for="id_contrat">Contrat à relever *</label>
        <select class="champ" id="id_contrat" name="id_contrat" required onchange="this.form.submit()">
            <?php foreach ($contrats as $ct): ?>
                <option value="<?= (int) $ct['id'] ?>"<?= (int) $ct['id'] === (int) $contrat['id'] ? ' selected' : '' ?>>
                    Contrat n°<?= (int) $ct['id'] ?> — <?= $e($ct['quartier']) ?>
                    · Eau <?= $e($ct['num_compteur_eau'] ?: '—') ?> · Électricité <?= $e($ct['num_compteur_elec'] ?: '—') ?>
                    <?= !empty($saisiMois[(int) $ct['id']]) ? ' ✓ saisi ce mois-ci' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn-secondaire" type="submit">Choisir ce contrat</button></noscript>
    </form>
<?php endif; ?>
<div class="carte">
    <p>
        <strong>Contrat n°<?= (int) $contrat['id'] ?></strong> —
        quartier <?= $e($contrat['quartier']) ?> (<?= $e($contrat['type_activite']) ?>)
        · compteurs <?= $e($contrat['num_compteur_eau']) ?> / <?= $e($contrat['num_compteur_elec']) ?>
    </p>
</div>

<?php if ($dejaSaisi): ?>
    <div class="carte">
        <p>Vous avez déjà saisi votre consommation pour ce mois (contrat n°<?= (int) $contrat['id'] ?>)</p>
        <a class="btn-principal" href="<?= $e($base) ?>Consommation/all">Voir mon historique</a>
    </div>
    <?php return; ?>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data" id="form-saisie" novalidate>
    <input type="hidden" name="id_contrat" value="<?= (int) $contrat['id'] ?>">
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
                       value="<?= $e($old[$c['index']]) ?>" placeholder="Ex : <?= $e($nb($prec[$c['index']] + ($cle === 'eau' ? 15 : 120))) ?>"
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
        (consommation nulle) ou s'il dépasse le seuil de ce contrat (<?= $e($nb($param['seuil_eau'])) ?> m³ d'eau, <?= $e($nb($param['seuil_elec'])) ?> kWh d'électricité).
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
// Le barème est celui du CONTRAT choisi (type d'activité de son quartier), fourni par PHP.
(function () {
    var TAUX = <?= json_encode((float) $param['taux_tva']) ?>;
    var PROGRESSIF = <?= json_encode(defined('CALCUL_PROGRESSIF') && CALCUL_PROGRESSIF) ?>;
    var BAREME = <?= json_encode($tranches, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;     // {eau: [[borneMax|null, prix], ...], elec: [...]}
    var TARIFS = {};
    ['eau', 'elec'].forEach(function (t) {
        TARIFS[t] = (BAREME[t] || []).map(function (l) { return [l[0] === null ? Infinity : l[0], l[1]]; });
    });

    // Arrondi à 2 décimales comme PHP round() : toPrecision(12) absorbe l'erreur binaire (ex. 39,825 -> 39,83).
    var arrondi = function (x) { return Math.round(parseFloat((x * 100).toPrecision(12))) / 100; };
    var fmt = function (x) { return x.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };

    function montant(conso, type) {
        if (conso <= 0 || !TARIFS[type].length) { return 0; }
        var t = TARIFS[type], i;
        if (!PROGRESSIF) {                          // R6 : prix de la tranche appliqué à toute la consommation
            for (i = 0; i < t.length; i++) {
                if (conso <= t[i][0]) { return arrondi(conso * t[i][1]); }
            }
            return 0;
        }
        var total = 0, min = 0;                     // calcul progressif : chaque tranche à son prix
        for (i = 0; i < t.length; i++) {
            var haut = Math.min(conso, t[i][0]);
            if (haut > min) { total += (haut - min) * t[i][1]; }
            if (conso <= t[i][0]) { break; }
            min = t[i][0];
        }
        return arrondi(total);
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
        ht = arrondi(ht);
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
