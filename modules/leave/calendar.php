<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Leave Calendar";

// Get current year/month
$year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : date('n');

// Navigation
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Calendar Variables
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$dateComponents = getdate($firstDayOfMonth);
$monthName = $dateComponents['month'];
$dayOfWeek = $dateComponents['wday']; // 0 (Sun) - 6 (Sat)
// Adjust for ISO-8601 (Mon-Sun) if desired, but standard US/PHP Sunday-start is fine for now or adjust logic.
// Let's stick to Sun=0 start for simplicity with standard calendar layouts.

// Fetch Approved Leaves for this Month
$startDate = "$year-$month-01";
$endDate = "$year-$month-$numberDays";

// Query events that overlap with this month
$stmt = $pdo->prepare("
    SELECT l.*, u.full_name, u.department 
    FROM ict_leave_requests l 
    JOIN users u ON l.user_id = u.id 
    WHERE l.status = 'approved' 
    AND (
        (l.start_date BETWEEN :start1 AND :end1) OR 
        (l.end_date BETWEEN :start2 AND :end2) OR
        (l.start_date <= :start3 AND l.end_date >= :end3)
    )
    ORDER BY l.start_date ASC
");
$stmt->execute([
    'start1' => $startDate,
    'end1' => $endDate,
    'start2' => $startDate,
    'end2' => $endDate,
    'start3' => $startDate,
    'end3' => $endDate
]);
$leaves = $stmt->fetchAll();

// Index leaves by day for easier rendering
$dailyLeaves = [];
for ($i = 1; $i <= $numberDays; $i++) {
    $currentDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($i, 2, '0', STR_PAD_LEFT);
    foreach ($leaves as $leave) {
        if ($currentDate >= $leave['start_date'] && $currentDate <= $leave['end_date']) {
            $dailyLeaves[$i][] = $leave;
        }
    }
}

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Leave Calendar</h1>
        <p class="text-slate-500 mt-2">Team availability overview.</p>
    </div>
    <div class="flex space-x-3">
        <a href="index.php?view=all_requests"
            class="px-4 py-2 text-sm font-medium border border-gray-200 rounded-lg hover:bg-gray-100 text-slate-600 bg-white">
            Back to List
        </a>
        <a href="create.php"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-primary-500/30 transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Request Leave
        </a>
    </div>
</div>

<div class="saas-card overflow-hidden fade-in-up" style="animation-delay: 0.1s">
    <!-- Calendar Header -->
    <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>"
            class="p-2 hover:bg-slate-200 rounded-lg text-slate-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <h2 class="text-xl font-bold text-slate-800">
            <?php echo $monthName . " " . $year; ?>
        </h2>
        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>"
            class="p-2 hover:bg-slate-200 rounded-lg text-slate-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center">
        <div class="py-2 text-xs font-bold text-slate-500 uppercase">Sun</div>
        <div class="py-2 text-xs font-bold text-slate-500 uppercase">Mon</div>
        <div class="py-2 text-xs font-bold text-slate-500 uppercase">Tue</div>
        <div class="py-2 text-xs font-bold text-slate-500 uppercase">Wed</div>
        <div class="py-2 text-xs font-bold text-slate-500 uppercase">Thu</div>
        <div class="py-2 text-xs font-bold text-slate-500 uppercase">Fri</div>
        <div class="py-2 text-xs font-bold text-slate-500 uppercase">Sat</div>
    </div>

    <div class="grid grid-cols-7 auto-rows-fr bg-white border-l border-slate-200">
        <?php
        // Empty slots for days before start of month
        for ($i = 0; $i < $dayOfWeek; $i++) {
            echo '<div class="h-32 border-b border-r border-slate-200 bg-slate-50/20"></div>';
        }

        // Days of month
        for ($day = 1; $day <= $numberDays; $day++) {
            $isToday = ($day == date('j') && $month == date('n') && $year == date('Y'));
            $class = "h-32 border-b border-r border-slate-200 p-2 relative group hover:bg-slate-50 transition-colors";
            if ($isToday)
                $class .= " bg-primary-50/30";

            echo "<div class='$class'>";
            echo "<span class='text-sm font-semibold " . ($isToday ? "text-primary-600 bg-primary-100 px-2 py-0.5 rounded-full" : "text-slate-700") . "'>$day</span>";

            // Render Leaves
            if (isset($dailyLeaves[$day])) {
                echo "<div class='mt-1 space-y-1 overflow-y-auto max-h-[90px] custom-scrollbar'>";
                foreach ($dailyLeaves[$day] as $leave) {
                    $color = 'bg-primary-100 text-primary-700 border-primary-200';
                    if ($leave['leave_type'] === 'sick')
                        $color = 'bg-red-100 text-red-700 border-red-200';

                    echo "<div class='text-[10px] px-1.5 py-0.5 rounded border $color truncate' title='" . htmlspecialchars($leave['full_name'] . ' - ' . $leave['leave_type']) . "'>";
                    echo htmlspecialchars(explode(' ', $leave['full_name'])[0]);
                    echo "</div>";
                }
                echo "</div>";
            }

            echo "</div>";

            // New row if Saturday (6)
            $dayOfWeek++;
            if ($dayOfWeek == 7) {
                $dayOfWeek = 0;
            }
        }

        // Remaining empty slots
        if ($dayOfWeek != 0) {
            $remainingDays = 7 - $dayOfWeek;
            for ($i = 0; $i < $remainingDays; $i++) {
                echo '<div class="h-32 border-b border-r border-slate-200 bg-slate-50/20"></div>';
            }
        }
        ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>