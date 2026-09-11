<?php
/**
 * ============================================================
 * NEXUS — INTERNATIONALISATION (i18n)
 * Langues supportées : fr, en, es
 * Détection automatique depuis le navigateur (HTTP_ACCEPT_LANGUAGE)
 * Stockage en session — aucune permission utilisateur requise.
 * Usage dans les vues : t('key') ou t('key', ['var' => 'value'])
 * ============================================================
 */

define('NEXUS_LOCALES', ['fr', 'en', 'es']);
define('NEXUS_LOCALE_DEFAULT', 'fr');

// Stockage global des traductions chargées
$GLOBALS['_nexus_lang'] = [];

/**
 * Détecte la langue préférée depuis l'en-tête HTTP Accept-Language.
 * Retourne un code de langue supporté ('fr', 'en', 'es').
 * Aucune permission requise — lecture d'un en-tête HTTP standard.
 */
function detect_browser_locale(): string
{
    $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ($header === '') return NEXUS_LOCALE_DEFAULT;

    // Parse "fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7,es;q=0.6"
    $candidates = [];
    foreach (explode(',', $header) as $part) {
        $part = trim($part);
        if (str_contains($part, ';q=')) {
            [$lang, $q] = explode(';q=', $part);
            $q = (float)$q;
        } else {
            $lang = $part;
            $q    = 1.0;
        }
        // Extrait le code de base ('fr' depuis 'fr-FR')
        $base = strtolower(substr(trim($lang), 0, 2));
        if (!isset($candidates[$base]) || $candidates[$base] < $q) {
            $candidates[$base] = $q;
        }
    }

    // Trie par priorité décroissante, prend la première supportée
    arsort($candidates);
    foreach (array_keys($candidates) as $code) {
        if (in_array($code, NEXUS_LOCALES, true)) {
            return $code;
        }
    }

    return NEXUS_LOCALE_DEFAULT;
}

/**
 * Initialise la locale active.
 * Ordre de priorité :
 *   1. Préférence enregistrée en session (choix explicite de l'utilisateur)
 *   2. Langue du navigateur (détection automatique)
 *   3. Français par défaut
 */
function nexus_init_locale(): void
{
    if (!isset($_SESSION['locale'])) {
        $_SESSION['locale'] = detect_browser_locale();
    }
    nexus_load_locale($_SESSION['locale']);
}

/**
 * Change la locale active et recharge les traductions.
 */
function nexus_set_locale(string $locale): void
{
    if (!in_array($locale, NEXUS_LOCALES, true)) {
        $locale = NEXUS_LOCALE_DEFAULT;
    }
    $_SESSION['locale'] = $locale;
    nexus_load_locale($locale);
}

/**
 * Retourne la locale active.
 */
function nexus_locale(): string
{
    return $_SESSION['locale'] ?? NEXUS_LOCALE_DEFAULT;
}

/**
 * Charge le fichier de traduction dans $GLOBALS['_nexus_lang'].
 * Fallback sur le français si le fichier est manquant.
 */
function nexus_load_locale(string $locale): void
{
    $file = __DIR__ . '/../lang/' . $locale . '.php';
    if (!file_exists($file)) {
        $file = __DIR__ . '/../lang/' . NEXUS_LOCALE_DEFAULT . '.php';
    }
    $GLOBALS['_nexus_lang'] = require $file;
}

/**
 * Retourne la traduction d'une clé.
 *
 * Exemples :
 *   t('nav.dashboard')               → "Control Center"
 *   t('finance.balance', ['{cur}' => '€']) → "Solde net (€)"
 *   t('missing.key')                 → "missing.key" (fallback visible)
 */
function t(string $key, array $replace = []): string
{
    $parts = explode('.', $key);
    $value = $GLOBALS['_nexus_lang'];

    foreach ($parts as $part) {
        if (!is_array($value) || !isset($value[$part])) {
            // Clé manquante : retourne la clé pour repérer facilement
            return $key;
        }
        $value = $value[$part];
    }

    if (!is_string($value)) return $key;

    // Remplacements de variables — ex: t('hello', ['{name}' => 'Alex'])
    if (!empty($replace)) {
        $value = str_replace(array_keys($replace), array_values($replace), $value);
    }

    return $value;
}

/**
 * Identique à t() mais échappe le HTML (pour usage dans les attributs HTML).
 */
function th(string $key, array $replace = []): string
{
    return htmlspecialchars(t($key, $replace), ENT_QUOTES, 'UTF-8');
}
