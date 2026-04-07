<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// Fetch ALL renewals (no pagination)
$stmt = $pdo->query("SELECT r.*, v.name as vendor_name 
                     FROM renewals r 
                     LEFT JOIN vendors v ON r.vendor_id = v.id 
                     ORDER BY r.renewal_date ASC");
$renewals = $stmt->fetchAll();

$total_amount = 0;
foreach ($renewals as $r) {
    if ($r['status'] === 'active') {
        $total_amount += $r['amount_paid'];
    }
}

$annual_total = 0;
foreach ($renewals as $r) {
    if ($r['status'] === 'active') {
        $amount = (float) $r['amount_paid'];
        if ($r['billing_cycle'] === 'monthly') {
            $annual_total += ($amount * 12);
        } else {
            $annual_total += $amount;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Registry -
        <?php echo date('Y-m-d'); ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
                padding: 0 !important;
                margin: 0 !important;
            }

            .print-container {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 20px !important;
            }

            tr {
                page-break-inside: avoid;
            }
        }

        body {
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="p-8">
    <div class="max-w-6xl mx-auto bg-white shadow-xl rounded-xl p-8 print-container">
        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-slate-100 pb-8 mb-8">
            <div>
                <p class="text-xs font-black text-primary-500 uppercase tracking-[0.2em] mb-2">Dallas Premiere Hotel</p>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight">IT Subscription Registry</h1>
                <p class="text-slate-500 mt-3 max-w-2xl text-sm leading-relaxed">
                    This report provides a comprehensive audit of all recurring software licenses, service contracts,
                    and vendor subscriptions
                    managed by the ICT Department. It serves as the primary registry for tracking operational expenses,
                    expenditure cycles, and legal commitment timelines.
                </p>
                <div class="flex gap-4 mt-6">
                    <span
                        class="px-3 py-1 bg-slate-100 text-slate-700 rounded-full text-[10px] font-bold uppercase">Inventory
                        Size: <?php echo count($renewals); ?></span>
                    <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-[10px] font-bold uppercase">Report
                        Date: <?php echo date('d M Y, H:i'); ?></span>
                </div>
            </div>
            <div class="text-right">
                <button onclick="window.print()"
                    class="no-print px-6 py-2.5 bg-slate-900 text-white font-bold rounded-lg hover:bg-slate-800 transition-all flex items-center shadow-lg active:scale-95">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2m8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4">
                        </path>
                    </svg>
                    Save as PDF
                </button>
            </div>
        </div>

        <!-- Summary KPI Row -->
        <div class="grid grid-cols-3 gap-6 mb-8 text-center bg-slate-50 p-6 rounded-2xl border border-slate-100">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Active Plans</p>
                <p class="text-2xl font-bold text-slate-800">
                    <?php echo count(array_filter($renewals, fn($r) => $r['status'] === 'active')); ?>
                </p>
            </div>
            <div class="border-x border-slate-200">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pending Unpaid</p>
                <p class="text-2xl font-bold text-amber-600">
                    <?php echo count(array_filter($renewals, fn($r) => $r['payment_status'] === 'unpaid')); ?>
                </p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Annualized Commitment</p>
                <p class="text-2xl font-bold text-slate-800"><?php echo formatCurrency($annual_total); ?></p>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold tracking-wider">
                        <th class="px-4 py-3 rounded-l-lg border-b">Service Name</th>
                        <th class="px-4 py-3 border-b">Vendor</th>
                        <th class="px-4 py-3 border-b text-center">Renewal Date</th>
                        <th class="px-4 py-3 border-b text-center">Status</th>
                        <th class="px-4 py-3 border-b text-center">Billing</th>
                        <th class="px-4 py-3 rounded-r-lg border-b text-right">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 italic-text">
                    <?php foreach ($renewals as $r): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-800 text-sm">
                                    <?php echo htmlspecialchars($r['service_name']); ?>
                                </p>
                                <p class="text-[10px] text-slate-400">
                                    <?php echo $r['is_recurring'] ? 'Recurring Contract' : 'Single License'; ?>
                                </p>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">
                                <?php echo htmlspecialchars($r['vendor_name'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-center font-mono">
                                <?php echo date('d M Y', strtotime($r['renewal_date'])); ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $r['status'] === 'active' ? 'bg-emerald-50 text-emerald-600' : ($r['status'] === 'cancelled' ? 'bg-slate-100 text-slate-400' : 'bg-amber-50 text-amber-600'); ?>">
                                    <?php echo $r['status']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-center uppercase text-slate-500">
                                <?php echo $r['billing_cycle']; ?>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $r['payment_status'] === 'paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'; ?>">
                                    <?php echo $r['payment_status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-8 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-400">ICT Management System - Internal Registry Policy Document</p>
            <p class="text-[10px] text-slate-300 mt-1 uppercase tracking-widest">Confidential & Proprietary</p>
        </div>
    </div>

    <script>
        // Auto trigger print if we want to bypass the button for an even smoother experience
        // window.onload = () => window.print();
    </script>
</body>

</html>