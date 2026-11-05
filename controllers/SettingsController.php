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
        if (!in_array($newCurrency, ['EUR', 'USD', 'GBP', 'XOF'], true)) {
            json_response(['success' => false, 'error' => 'Devise invalide.'], 422);
        }
        $theme       = in_array($_POST['theme'] ?? '', ['dark', 'light'], true) ? $_POST['theme'] : 'dark';
        $notifs      = !empty($_POST['notifications_enabled']);
        $locale      = in_array($_POST['locale'] ?? '', NEXUS_LOCALES, true) ? $_POST['locale'] : nexus_locale();

        $user = User::findById($userId);
        if (!$user) {
            json_response(['success' => false, 'error' => 'Compte introuvable.'], 404);
        }
        $oldCurrency = $user['currency'] ?? 'EUR';
        $conversionInfo = null;
        $rate = 1.0;

        if ($newCurrency !== $oldCurrency) {
            $rate = exchange_rate($oldCurrency, $newCurrency);
            if (!is_finite($rate) || $rate <= 0) {
                throw new RuntimeException('Taux de change indisponible ou invalide.');
            }
            $conversionInfo = [
                'from' => $oldCurrency,
                'to'   => $newCurrency,
                'rate' => round($rate, 6),
            ];
        }

        $db = db();
        $db->beginTransaction();
        try {
            if ($newCurrency !== $oldCurrency) {
                $this->convertAllAmounts($userId, $rate);
            }
            User::updatePreferences($userId, $newCurrency, $theme, $notifs, $locale);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

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

    /** Convertit les montants enregistrés, dans la même transaction que le changement de devise. */
    private function convertAllAmounts(int $userId, float $rate): void
    {
        $db = db();
        $db->prepare('UPDATE transactions SET amount = ROUND(amount * ?, 2) WHERE user_id = ?')
           ->execute([$rate, $userId]);
        $db->prepare('UPDATE goals SET target_amount = ROUND(target_amount * ?, 2), current_amount = ROUND(current_amount * ?, 2) WHERE user_id = ?')
           ->execute([$rate, $rate, $userId]);
        $db->prepare('UPDATE goal_deposits AS gd SET amount = ROUND(gd.amount * ?, 2) FROM goals AS g WHERE gd.goal_id = g.id AND g.user_id = ?')
           ->execute([$rate, $userId]);
        $db->prepare('UPDATE budgets SET amount = ROUND(amount * ?, 2) WHERE user_id = ?')
           ->execute([$rate, $userId]);
        $db->prepare('UPDATE monthly_targets SET target_amount = ROUND(target_amount * ?, 2) WHERE user_id = ?')
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
