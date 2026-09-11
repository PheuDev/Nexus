<?php
/** CONTROLLER — Settings
 * Thème, devise, notifications, suppression définitive du compte. */

require_once __DIR__ . '/../models/User.php';

class SettingsController
{
    public function index(): void
    {
        require_login();
        $user = User::findById(current_user_id());
        render('settings', ['title' => 'Réglages', 'user' => $user]);
    }

    public function update(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();

        $newCurrency = clean_str($_POST['currency'] ?? 'EUR');
        $theme       = in_array($_POST['theme'] ?? '', ['dark', 'light'], true) ? $_POST['theme'] : 'dark';
        $notifs      = !empty($_POST['notifications_enabled']);
        $locale      = in_array($_POST['locale'] ?? '', NEXUS_LOCALES, true) ? $_POST['locale'] : nexus_locale();

        $oldCurrency = $_SESSION['user_currency'] ?? 'EUR';
        $conversionInfo = null;

        if ($newCurrency !== $oldCurrency) {
            $rate = exchange_rate($oldCurrency, $newCurrency);
            $this->convertAllAmounts($userId, $rate);
            $conversionInfo = [
                'from' => $oldCurrency,
                'to'   => $newCurrency,
                'rate' => round($rate, 6),
            ];
        }

        User::updatePreferences($userId, $newCurrency, $theme, $notifs, $locale);
        $_SESSION['user_currency'] = $newCurrency;
        $_SESSION['user_theme']    = $theme;

        // Applique la nouvelle locale immédiatement en session
        nexus_set_locale($locale);

        json_response([
            'success'    => true,
            'theme'      => $theme,
            'locale'     => $locale,
            'conversion' => $conversionInfo,
        ]);
    }

    /** Applique un taux de conversion sur tous les montants de l'utilisateur */
    private function convertAllAmounts(int $userId, float $rate): void
    {
        $db = db();
        // Transactions
        $db->prepare('UPDATE transactions SET amount = ROUND(amount * ?, 2) WHERE user_id = ?')
           ->execute([$rate, $userId]);
        // Objectifs
        $db->prepare('UPDATE goals SET target_amount = ROUND(target_amount * ?, 2), current_amount = ROUND(current_amount * ?, 2) WHERE user_id = ?')
           ->execute([$rate, $rate, $userId]);
        // Dépôts d'objectifs (via JOIN pour isoler par user_id)
        $db->prepare('UPDATE goal_deposits gd JOIN goals g ON gd.goal_id = g.id SET gd.amount = ROUND(gd.amount * ?, 2) WHERE g.user_id = ?')
           ->execute([$rate, $userId]);
    }

    public function deleteAccount(): void
    {
        require_login();
        csrf_require();
        $confirm = clean_str($_POST['confirm'] ?? '');
        if ($confirm !== 'SUPPRIMER') {
            json_response(['success' => false, 'error' => 'Confirmation requise (taper SUPPRIMER).'], 422);
        }
        User::deleteAccount(current_user_id());
        logout_user();
        json_response(['success' => true, 'redirect' => 'index.php?page=login']);
    }
}
