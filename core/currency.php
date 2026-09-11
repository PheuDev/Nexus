<?php
/**
 * ============================================================
 * NEXUS — CURRENCY
 * Taux de change avec cache fichier 1h.
 * Source : open.er-api.com (gratuit, sans clé API).
 * Fallback sur des taux fixes si l'API est indisponible.
 * ============================================================
 */

/**
 * Retourne les taux de change depuis EUR (base).
 * Résultat mis en cache dans /tmp pendant 1 heure.
 */
function fetch_exchange_rates(): array
{
    $cacheFile = sys_get_temp_dir() . '/nexus_rates.json';
    $cacheTtl  = 3600; // 1 heure

    // Utilise le cache si encore valide
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached['rates'])) {
            return $cached['rates'];
        }
    }

    // Tentative d'appel API (open.er-api.com, gratuit, sans clé)
    $rates = null;
    if (function_exists('curl_init')) {
        $ch = curl_init('https://open.er-api.com/v6/latest/EUR');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['rates'])) {
                $rates = $data['rates'];
                // Sauvegarde en cache
                file_put_contents($cacheFile, json_encode(['rates' => $rates, 'ts' => time()]));
            }
        }
    }

    // Fallback : taux fixes si API indisponible
    if ($rates === null) {
        $rates = fallback_rates();
    }

    return $rates;
}

/**
 * Taux fixes de secours (mis à jour manuellement si besoin).
 * Base : 1 EUR.
 */
function fallback_rates(): array
{
    return [
        'EUR' => 1.0,
        'USD' => 1.08,
        'GBP' => 0.85,
        'XOF' => 655.957, // Taux fixe légal (FCFA arrimé à l'EUR)
        'JPY' => 160.0,
        'CHF' => 0.95,
        'CAD' => 1.48,
    ];
}

/**
 * Convertit un montant d'une devise vers une autre.
 * Toutes les conversions passent par EUR comme devise pivot.
 * @param float  $amount   Montant à convertir
 * @param string $from     Devise source (ex: 'USD')
 * @param string $to       Devise cible (ex: 'XOF')
 * @return float           Montant converti
 */
function convert_currency(float $amount, string $from, string $to): float
{
    if ($from === $to) return $amount;

    $rates = fetch_exchange_rates();

    // Taux depuis EUR
    $rateFrom = $rates[$from] ?? 1.0;
    $rateTo   = $rates[$to]   ?? 1.0;

    // Convertit : montant → EUR → devise cible
    return $amount / $rateFrom * $rateTo;
}

/** Retourne le taux de change entre deux devises. */
function exchange_rate(string $from, string $to): float
{
    if ($from === $to) return 1.0;
    $rates    = fetch_exchange_rates();
    $rateFrom = $rates[$from] ?? 1.0;
    $rateTo   = $rates[$to]   ?? 1.0;
    return $rateTo / $rateFrom;
}
