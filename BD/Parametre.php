<?php

function bdParametreGet(): array
{
    $st = getConnexion()->query('SELECT seuil_eau, seuil_elec, taux_tva FROM parametre WHERE id = 1');
    $r = $st->fetch();

    return [
        'seuil_eau'  => (float) ($r['seuil_eau']  ?? 50),
        'seuil_elec' => (float) ($r['seuil_elec'] ?? 500),
        'taux_tva'   => (float) ($r['taux_tva']   ?? 0.18),
    ];
}
