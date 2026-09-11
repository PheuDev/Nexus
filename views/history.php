<?php
$actionLabels = [
    'create' => ['label' => t('history.create'), 'color' => 'var(--success)', 'bg' => 'var(--success-dim)'],
    'update' => ['label' => t('history.update'), 'color' => 'var(--cyan)',    'bg' => 'var(--cyan-glow)'],
    'delete' => ['label' => t('history.delete'), 'color' => 'var(--danger)',  'bg' => 'var(--danger-dim)'],
    'login'  => ['label' => t('history.login'),  'color' => 'var(--electric)','bg' => 'rgba(91,140,255,0.12)'],
    'logout' => ['label' => t('history.logout'), 'color' => 'var(--text-faint)', 'bg' => 'rgba(255,255,255,0.05)'],
];
$entityLabels = [
    'transaction' => t('history.transaction'),
    'goal'        => t('history.goal'),
    'task'        => t('history.task'),
    'category'    => t('history.category'),
    'budget'      => t('history.budget'),
    'user'        => t('history.account'),
];
?>
<div class="panel panel-static">
    <div class="panel-title-row" style="margin-bottom:20px;">
        <div class="section-title"><?= th('history.activity') ?></div>
        <span style="font-size:12px;color:var(--text-faint);"><?= count($logs) ?> <?= th('history.entries') ?></span>
    </div>
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <?= th('history.no_activity') ?>
        </div>
    <?php else: ?>
    <div class="nexus-list">
        <?php foreach ($logs as $log):
            $a = $actionLabels[$log['action']] ?? ['label' => $log['action'], 'color' => 'var(--text-dim)', 'bg' => 'rgba(255,255,255,0.04)'];
            $e = $entityLabels[$log['entity']] ?? $log['entity'];
        ?>
        <div class="list-row">
            <div class="list-row-main" style="gap:12px;">
                <span class="badge" style="color:<?= $a['color'] ?>;background:<?= $a['bg'] ?>;border-color:<?= $a['color'] ?>33;flex-shrink:0;">
                    <?= $a['label'] ?>
                </span>
                <span style="font-size:11px;color:var(--text-faint);font-family:var(--font-mono);flex-shrink:0;">
                    <?= htmlspecialchars($e) ?>
                </span>
                <span style="font-size:13px;color:var(--text-main);">
                    <?= htmlspecialchars($log['summary']) ?>
                </span>
            </div>
            <span style="font-size:11px;color:var(--text-faint);font-family:var(--font-mono);white-space:nowrap;flex-shrink:0;">
                <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
