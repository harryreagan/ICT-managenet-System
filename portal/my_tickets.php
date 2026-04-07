<?php
require_once __DIR__ . '/layout.php';

// Fetch all tickets for this user
// Again, assuming matching by Technician Name or just showing all for now as per MVP plan.
// In a real app, we'd filter by `requester_id` or similar.
$stmt = $pdo->query("SELECT * FROM troubleshooting_logs ORDER BY created_at DESC");
$tickets = $stmt->fetchAll();

renderPortalHeader("My Ticket History");
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">My Ticket History</h1>
            <p class="text-sm text-slate-500 mt-1">Status of your support requests.</p>
        </div>
        <a href="submit_ticket.php"
            class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-colors text-sm">
            + New Ticket
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <?php if (count($tickets) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Subject</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Priority</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-mono text-slate-400">#
                                    <?= $ticket['id'] ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-700">
                                        <?= htmlspecialchars($ticket['title']) ?>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        <?= htmlspecialchars($ticket['system_affected'] ?? 'General') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wide
                                        <?php
                                        switch ($ticket['status']) {
                                            case 'open':
                                                echo 'bg-blue-50 text-blue-700';
                                                break;
                                            case 'in_progress':
                                                echo 'bg-amber-50 text-amber-700';
                                                break;
                                            case 'resolved':
                                                echo 'bg-emerald-50 text-emerald-700';
                                                break;
                                            case 'closed':
                                                echo 'bg-gray-100 text-gray-600';
                                                break;
                                            default:
                                                echo 'bg-slate-100 text-slate-600';
                                        }
                                        ?>
                                    ">
                                        <?= htmlspecialchars(str_replace('_', ' ', $ticket['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    <?= date('M j, Y', strtotime($ticket['created_at'])) ?>
                                    <span class="block text-xs text-slate-400">
                                        <?= date('g:i a', strtotime($ticket['created_at'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($ticket['priority'] === 'critical'): ?>
                                        <span class="text-red-600 font-bold flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                                </path>
                                            </svg>
                                            Critical
                                        </span>
                                    <?php elseif ($ticket['priority'] === 'high'): ?>
                                        <span class="text-amber-600 font-semibold">High</span>
                                    <?php else: ?>
                                        <span class="text-slate-500">
                                            <?= ucfirst($ticket['priority']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center">
                <div
                    class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900">No tickets found</h3>
                <p class="text-slate-500 mt-1 mb-6">You haven't submitted any support requests yet.</p>
                <a href="submit_ticket.php" class="text-primary-600 hover:text-primary-700 font-bold">Create your first
                    ticket &rarr;</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
renderPortalFooter();
?>