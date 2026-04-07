<?php
/**
 * Renders the System Status Widget
 * used in Portal Dashboard
 */
function renderSystemStatus($pdo)
{
    try {
        // Check if table exists (cache this check in production if possible)
        $tableExists = $pdo->query("SHOW TABLES LIKE 'system_status'")->rowCount() > 0;
        if ($tableExists) {
            $statusStmt = $pdo->query("SELECT * FROM system_status WHERE id = 1");
            $sysStatus = $statusStmt->fetch();
        } else {
            $sysStatus = ['status' => 'operational', 'message' => 'Network and core services are running normally.'];
        }
    } catch (PDOException $e) {
        $sysStatus = ['status' => 'operational', 'message' => 'Network and core services are running normally.'];
    }

    $statusTheme = [
        'operational' => [
            'bg' => 'emerald-50',
            'border' => 'emerald-100',
            'icon_bg' => 'emerald-100',
            'icon_text' => 'emerald-600',
            'title_text' => 'emerald-800',
            'body_text' => 'emerald-600',
            'label' => 'All Systems Operational',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
        ],
        'partial_outage' => [
            'bg' => 'amber-50',
            'border' => 'amber-100',
            'icon_bg' => 'amber-100',
            'icon_text' => 'amber-600',
            'title_text' => 'amber-800',
            'body_text' => 'amber-600',
            'label' => 'Service Advisory',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>'
        ],
        'major_outage' => [
            'bg' => 'red-50',
            'border' => 'red-100',
            'icon_bg' => 'red-100',
            'icon_text' => 'red-600',
            'title_text' => 'red-800',
            'body_text' => 'red-600',
            'label' => 'Service Outage',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>'
        ]
    ];

    $currentTheme = $statusTheme[$sysStatus['status'] ?? 'operational'];
    ?>
    <div
        class="bg-<?= $currentTheme['bg'] ?> border border-<?= $currentTheme['border'] ?> rounded-xl p-4 flex items-start gap-4 shadow-sm">
        <div class="bg-<?= $currentTheme['icon_bg'] ?> text-<?= $currentTheme['icon_text'] ?> p-2.5 rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <?= $currentTheme['icon'] ?>
            </svg>
        </div>
        <div>
            <h3 class="font-bold text-<?= $currentTheme['title_text'] ?> text-base uppercase tracking-tight">
                <?= $sysStatus['status'] == 'operational' ? $currentTheme['label'] : htmlspecialchars($sysStatus['label'] ?? $currentTheme['label']) ?>
            </h3>
            <p class="text-sm text-<?= $currentTheme['body_text'] ?> mt-0.5 opacity-90">
                <?= htmlspecialchars($sysStatus['message']) ?>
            </p>
        </div>
    </div>
    <?php
}
?>