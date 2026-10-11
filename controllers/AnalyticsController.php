<?php
/**
 *CONTROLLER — Analyse financière
 * Évolution, répartition des dépenses, taux d'épargne,
 * comparaison de périodes, observations automatiques.
*/

require_once __DIR__ . '/../models/Transaction.php';

class AnalyticsController
{
    public function index(): void
    {
        require_login();
        $userId = current_user_id();

        $thisMonthStart = date('Y-m-01');
        $today          = date('Y-m-d');
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd   = date('Y-m-t', strtotime('-1 month'));

        $series      = Transaction::monthlySeries($userId, 6);
        $expenseByCat = Transaction::byCategory($userId, 'expense', $thisMonthStart, $today);
        $incomeByCat  = Transaction::byCategory($userId, 'income', $thisMonthStart, $today);

        $current = Transaction::totalsForPeriod($userId, $thisMonthStart, $today);
        $previous = Transaction::totalsForPeriod($userId, $lastMonthStart, $lastMonthEnd);

        $savingsRateCurrent  = $current['income'] > 0 ? round((($current['income'] - $current['expense']) / $current['income']) * 100, 1) : 0;
        $savingsRatePrevious = $previous['income'] > 0 ? round((($previous['income'] - $previous['expense']) / $previous['income']) * 100, 1) : 0;

        render('analytics', [
            'title'         => 'Analyse',
            'series'        => $series,
            'expenseByCat'  => $expenseByCat,
            'incomeByCat'   => $incomeByCat,
            'current'       => $current,
            'previous'      => $previous,
            'savingsRateCurrent'  => $savingsRateCurrent,
            'savingsRatePrevious' => $savingsRatePrevious,
        ]);
    }
}
