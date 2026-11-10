<?php
/**
 *CONTROLLER — Analyse financière
 * Évolution, répartition des dépenses, taux d'épargne,
 * comparaison de périodes, observations automatiques.
*/

require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../core/pdf.php';

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

        $series       = Transaction::dailySeriesForPeriod($userId, $from, $to);
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

    public function exportPdf(): void
    {
        require_login();
        $userId = current_user_id();
        [$from, $to] = nexus_date_period(date('Y-m-01'), date('Y-m-d'));
        $periodDays = (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days + 1;
        $previousTo = date('Y-m-d', strtotime($from . ' -1 day'));
        $previousFrom = date('Y-m-d', strtotime($previousTo . ' -' . ($periodDays - 1) . ' days'));

        $current = Transaction::totalsForPeriod($userId, $from, $to);
        $previous = Transaction::totalsForPeriod($userId, $previousFrom, $previousTo);
        $daily = Transaction::dailySeriesForPeriod($userId, $from, $to);
        $categories = Transaction::categorySummaryForPeriod($userId, $from, $to);
        $currency = currency_symbol($_SESSION['user_currency'] ?? 'EUR');
        $observations = [];

        if ($current['expense'] > $current['income']) {
            $observations[] = t('analytics.alert_net', [
                '{amount}' => nexus_pdf_format_amount($current['expense'] - $current['income'], $currency),
            ]);
        }
        if ($previous['expense'] > 0 && $current['expense'] >= $previous['expense'] * 1.1) {
            $increase = round((($current['expense'] - $previous['expense']) / $previous['expense']) * 100);
            $observations[] = t('analytics.alert_increase', ['{percent}' => (string)$increase]);
        }
        if ($current['expense'] > 0 && !empty($categories)) {
            $topCategory = $categories[0];
            $share = round(((float)$topCategory['expense'] / $current['expense']) * 100);
            if ($share >= 30) {
                $observations[] = t('analytics.alert_category', [
                    '{category}' => $topCategory['name'],
                    '{percent}' => (string)$share,
                ]);
            }
        }

        $dailyTotals = [];
        foreach ($daily as $point) {
            $day = $point['day'];
            if (!isset($dailyTotals[$day])) {
                $dailyTotals[$day] = ['day' => $day, 'income' => 0.0, 'expense' => 0.0];
            }
            $dailyTotals[$day][$point['type']] = (float)$point['total'];
        }
        $dailySeries = [];
        $dayCursor = new DateTimeImmutable($from);
        $lastDay = new DateTimeImmutable($to);
        while ($dayCursor <= $lastDay) {
            $day = $dayCursor->format('Y-m-d');
            $dailySeries[] = $dailyTotals[$day] ?? ['day' => $day, 'income' => 0.0, 'expense' => 0.0];
            $dayCursor = $dayCursor->modify('+1 day');
        }
        if ($current['expense'] > 0 && $periodDays > 0 && !empty($dailyTotals)) {
            $averageDailyExpense = $current['expense'] / $periodDays;
            foreach ($dailyTotals as $point) {
                if ($point['expense'] > $averageDailyExpense * 1.5) {
                    $observations[] = t('analytics.alert_peak', [
                        '{date}' => date('d/m/Y', strtotime($point['day'])),
                        '{amount}' => nexus_pdf_format_amount($point['expense'], $currency),
                    ]);
                    break;
                }
            }
        }

        $pdf = nexus_analytics_pdf([
            'income' => $current['income'],
            'expense' => $current['expense'],
            'daily' => $dailySeries,
            'categories' => $categories,
            'observations' => $observations,
            'period_from' => $from,
            'period_to' => $to,
            'previous_from' => $previousFrom,
            'previous_to' => $previousTo,
            'generated_by' => $_SESSION['user_name'] ?? '',
            'currency' => $currency,
        ], [
            'title' => t('analytics.report_title'),
            'period' => t('analytics.report_period'),
            'generated' => t('analytics.report_generated'),
            'income' => t('analytics.income'),
            'expense' => t('analytics.expense'),
            'net' => t('finance.pdf_net'),
            'daily_chart' => t('analytics.daily_chart'),
            'category_chart' => t('analytics.category_chart'),
            'observations' => t('analytics.observations'),
            'no_observations' => t('analytics.no_observations'),
            'next_period' => t('analytics.next_period'),
            'category' => t('finance.category'),
            'income_label' => t('finance.income'),
            'expense_label' => t('finance.expense'),
            'net_label' => t('finance.pdf_net'),
            'empty_categories' => t('analytics.no_expense_cat'),
            'footer' => t('finance.pdf_footer'),
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="nexus-analyse-' . date('Y-m-d') . '.pdf"');
        header('Cache-Control: no-cache');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}
