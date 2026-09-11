<?php
/** 
 * CONTROLLER — Dashboard
 * KPIs, score financier, prévisions, graphiques, tâches, insights.
 */
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Budget.php';

class DashboardController
{
    public function index(): void
    {
        require_login();
        $userId = current_user_id();

        $firstOfMonth = date('Y-m-01');
        $today        = date('Y-m-d');

        $totals      = Transaction::totalsForPeriod($userId, $firstOfMonth, $today);
        $income      = $totals['income'];
        $expense     = $totals['expense'];
        $balance     = $income - $expense;
        $savingsRate = $income > 0 ? round((($income - $expense) / $income) * 100, 1) : 0;

        $goals        = Goal::allForUser($userId);
        $activeGoals  = array_filter($goals, fn($g) => $g['status'] === 'active');
        $recentTx     = Transaction::allForUser($userId, ['limit' => 6]);
        $todayTasks   = Task::todayForUser($userId);
        $pendingCount = Task::countPending($userId);

        $months = $this->sanitizeMonths($_GET['months'] ?? 6);
        $series = Transaction::monthlySeries($userId, $months);

        // #9 Prévisions basées sur les 3 derniers mois
        $forecast = Transaction::forecast($userId, 3);

        // #10 Score financier
        $score = $this->computeScore($savingsRate, $income, $expense, $activeGoals);

        // #2 Budgets du mois
        $budgetSummary = Budget::summaryForMonth($userId);

        $insights = $this->buildInsights($income, $expense, $savingsRate, $activeGoals, $forecast, $budgetSummary);

        render('dashboard', [
            'title'         => 'Control Center',
            'income'        => $income,
            'expense'       => $expense,
            'balance'       => $balance,
            'savingsRate'   => $savingsRate,
            'goals'         => $goals,
            'activeGoals'   => $activeGoals,
            'recentTx'      => $recentTx,
            'todayTasks'    => $todayTasks,
            'pendingCount'  => $pendingCount,
            'series'        => $series,
            'months'        => $months,
            'insights'      => $insights,
            'forecast'      => $forecast,
            'score'         => $score,
            'budgetSummary' => $budgetSummary,
        ]);
    }

    public function series(): void
    {
        require_login();
        $userId = current_user_id();
        $months = $this->sanitizeMonths($_GET['months'] ?? 6);
        json_response([
            'success'  => true,
            'series'   => Transaction::monthlySeries($userId, $months),
            'months'   => $months,
            'forecast' => Transaction::forecast($userId, 3),
        ]);
    }

    /**
     * #10 — Score financier 0–100.
     * Pondération : épargne 40%, budget équilibré 30%, objectifs 30%.
     */
    private function computeScore(float $savingsRate, float $income, float $expense, array $goals): int
    {
        // Composante épargne (0-40)
        $savingsScore = min(40, ($savingsRate / 30) * 40);

        // Composante équilibre (0-30) : 30 si dépenses < revenus, dégressif sinon
        $balanceScore = 0;
        if ($income > 0) {
            $ratio = $expense / $income;
            $balanceScore = $ratio <= 1 ? max(0, 30 - ($ratio * 10)) : 0;
        }

        // Composante objectifs (0-30) : moyenne d'avancement des objectifs actifs
        $goalsScore = 0;
        if (!empty($goals)) {
            $avgPct = array_sum(array_map(function ($g) {
                return $g['target_amount'] > 0 ? min(100, ($g['current_amount'] / $g['target_amount']) * 100) : 0;
            }, $goals)) / count($goals);
            $goalsScore = ($avgPct / 100) * 30;
        }

        return (int)round($savingsScore + $balanceScore + $goalsScore);
    }

    private function sanitizeMonths(mixed $v): int
    {
        $allowed = [1, 3, 6, 12];
        $v = (int)$v;
        return in_array($v, $allowed, true) ? $v : 6;
    }

    private function buildInsights(float $income, float $expense, float $savingsRate, array $activeGoals, array $forecast, array $budgets): array
    {
        $insights = [];

        if ($income > 0 && $expense > $income) {
            $insights[] = ['type' => 'alert', 'text' => 'Vos dépenses dépassent vos revenus ce mois-ci.'];
        } elseif ($savingsRate >= 30) {
            $insights[] = ['type' => 'success', 'text' => "Excellent taux d'épargne : {$savingsRate}% ce mois-ci."];
        } elseif ($income > 0) {
            $insights[] = ['type' => 'info', 'text' => "Taux d'épargne actuel : {$savingsRate}%."];
        }

        // Alerte budgets dépassés
        foreach ($budgets as $b) {
            if ($b['over']) {
                $insights[] = ['type' => 'alert', 'text' => "Budget « {$b['category_name']} » dépassé ({$b['pct']}%)."];
            }
        }

        // Prévisions
        if ($forecast['expense'] > 0 && $forecast['income'] > 0) {
            $forecastRate = round((($forecast['income'] - $forecast['expense']) / $forecast['income']) * 100, 1);
            if ($forecastRate < 10) {
                $insights[] = ['type' => 'alert', 'text' => "Prévision mois prochain : épargne estimée à {$forecastRate}%."];
            }
        }

        foreach ($activeGoals as $g) {
            $pct = $g['target_amount'] > 0 ? round(($g['current_amount'] / $g['target_amount']) * 100) : 0;
            if ($pct >= 80) {
                $insights[] = ['type' => 'success', 'text' => "Objectif « {$g['title']} » presque atteint ({$pct}%)."];
            }
            if (!empty($g['target_date']) && strtotime($g['target_date']) < time() && $g['status'] === 'active') {
                $insights[] = ['type' => 'alert', 'text' => "Objectif « {$g['title']} » a dépassé sa date cible."];
            }
        }

        if (empty($insights)) {
            $insights[] = ['type' => 'info', 'text' => 'Aucune anomalie détectée. Tout est sous contrôle.'];
        }

        return $insights;
    }
}
