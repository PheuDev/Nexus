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

        [$from, $to] = nexus_date_period(date('Y-m-01'), date('Y-m-d'));
        $periodDays = (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days + 1;
        $previousTo = date('Y-m-d', strtotime($from . ' -1 day'));
        $previousFrom = date('Y-m-d', strtotime($previousTo . ' -' . ($periodDays - 1) . ' days'));

        $series       = Transaction::monthlySeriesForPeriod($userId, $from, $to);
        $expenseByCat = Transaction::byCategory($userId, 'expense', $from, $to);
        $incomeByCat  = Transaction::byCategory($userId, 'income', $from, $to);

        $current  = Transaction::totalsForPeriod($userId, $from, $to);
        $previous = Transaction::totalsForPeriod($userId, $previousFrom, $previousTo);

        $savingsRateCurrent  = $current['income'] > 0 ? round((($current['income'] - $current['expense']) / $current['income']) * 100, 1) : 0;
        $savingsRatePrevious = $previous['income'] > 0 ? round((($previous['income'] - $previous['expense']) / $previous['income']) * 100, 1) : 0;

        render('analytics', [
            'title'         => 'Analyse',
            'periodFrom'    => $from,
            'periodTo'      => $to,
            'previousFrom'  => $previousFrom,
            'previousTo'    => $previousTo,
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
