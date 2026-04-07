<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Network Uptime Center";

// Fetch all static devices + static IP assignments grouped by network
$stmt = $pdo->query("
    (SELECT sd.id, sd.device_name, sd.device_type, sd.ip_address, sd.location, sd.network_id, 
            sd.status, sd.last_seen, n.name as network_name, n.vlan_tag, 'device' as source
     FROM static_devices sd 
     LEFT JOIN networks n ON sd.network_id = n.id)
    UNION 
    (SELECT ia.id, ia.device_name, 'Other' as device_type, ia.ip_address, ia.notes as location, ia.network_id, 
            ia.connectivity_status as status, ia.last_seen, n.name as network_name, n.vlan_tag, 'assignment' as source
     FROM ip_assignments ia
     LEFT JOIN networks n ON ia.network_id = n.id
     WHERE ia.status = 'static' 
     AND ia.ip_address NOT IN (SELECT ip_address FROM static_devices))
    ORDER BY vlan_tag ASC, ip_address ASC
");
$devices = $stmt->fetchAll();

// Statistics
$online_count = 0;
$offline_count = 0;
foreach ($devices as $d) {
    if (($d['status'] ?? '') === 'online')
        $online_count++;
    elseif (($d['status'] ?? '') === 'offline')
        $offline_count++;
}

include '../../includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-end mb-6 fade-in-up">
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Uptime Center</h1>
        <p class="text-slate-500 mt-2">Real-time connectivity monitoring for all assigned IP devices.</p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-2">
        <button id="refresh-all"
            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-xs font-bold uppercase tracking-widest rounded-lg shadow-lg shadow-primary-500/20 transition-all">
            <svg class="w-4 h-4 mr-2" id="sync-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                </path>
            </svg>
            Sync All Devices
        </button>
    </div>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="saas-card p-5 border-l-4 border-l-emerald-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Online Devices</p>
        <p class="text-3xl font-black text-slate-800">
            <?php echo $online_count; ?>
        </p>
    </div>
    <div class="saas-card p-5 border-l-4 border-l-red-500">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Offline / At Risk</p>
        <p class="text-3xl font-black text-slate-800">
            <?php echo $offline_count; ?>
        </p>
    </div>
    <div class="saas-card p-5 border-l-4 border-l-slate-300">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Monitored</p>
        <p class="text-3xl font-black text-slate-800">
            <?php echo count($devices); ?>
        </p>
    </div>
</div>

<div class="saas-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Network / VLAN</th>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Device Name</th>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">IP
                        Address</th>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        Status</th>
                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last
                        Seen</th>
                    <th class="px-6 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($devices as $d): ?>
                    <tr class="hover:bg-slate-50 transition-colors" 
                        data-ip-id="<?php echo $d['id']; ?>" 
                        data-source="<?php echo $d['source']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700">
                                    <?php echo htmlspecialchars($d['network_name'] ?: 'Unassigned'); ?>
                                </span>
                                <?php if ($d['vlan_tag']): ?>
                                    <span class="text-[10px] text-slate-400 uppercase">VLAN
                                        <?php echo $d['vlan_tag']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-slate-800">
                                <?php echo htmlspecialchars($d['device_name']); ?>
                            </span>
                            <span class="ml-2 text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                                <?php echo htmlspecialchars($d['device_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-xs font-bold text-primary-600 bg-primary-50 px-2 py-1 rounded">
                                <?php echo $d['ip_address']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap status-cell">
                            <div class="flex items-center">
                                <span class="status-pip w-2.5 h-2.5 rounded-full mr-2 <?php
                                echo $d['status'] === 'online' ? 'bg-emerald-500 animate-pulse' : ($d['status'] === 'offline' ? 'bg-red-500' : 'bg-slate-300');
                                ?>"></span>
                                <span class="status-text text-xs font-bold uppercase tracking-tighter <?php
                                echo $d['status'] === 'online' ? 'text-emerald-600' : ($d['status'] === 'offline' ? 'text-red-600' : 'text-slate-500');
                                ?>">
                                    <?php echo $d['status'] ?: 'Unknown'; ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap last-seen-cell">
                            <span class="text-[10px] text-slate-500 font-mono">
                                <?php echo $d['last_seen'] ? time_elapsed_string($d['last_seen']) : 'Never'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <button
                                class="check-individual p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all"
                                data-id="<?php echo $d['id']; ?>" 
                                data-source="<?php echo $d['source']; ?>" 
                                title="Check Now">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const refreshAllBtn = document.getElementById('refresh-all');
        const syncIcon = document.getElementById('sync-icon');

        async function checkDevice(ipId) {
            const row = document.querySelector(`tr[data-ip-id="${ipId}"]`);
            const pip = row.querySelector('.status-pip');
            const text = row.querySelector('.status-text');

            // Visual feedback
            text.innerText = 'Pinging...';
            text.className = 'status-text text-[10px] font-bold uppercase tracking-tighter text-slate-400';
            pip.className = 'status-pip w-2.5 h-2.5 rounded-full mr-2 bg-slate-200 animate-spin';

            try {
                const source = row.dataset.source;
                const response = await fetch(`check_status.php?id=${ipId}&source=${source}`);
                const data = await response.json();

                // Update UI
                if (data.status === 'online') {
                    pip.className = 'status-pip w-2.5 h-2.5 rounded-full mr-2 bg-emerald-500 animate-pulse';
                    text.className = 'status-text text-xs font-bold uppercase tracking-tighter text-emerald-600';
                    text.innerText = 'ONLINE';
                } else {
                    pip.className = 'status-pip w-2.5 h-2.5 rounded-full mr-2 bg-red-500';
                    text.className = 'status-text text-xs font-bold uppercase tracking-tighter text-red-600';
                    text.innerText = 'OFFLINE';
                }
                row.querySelector('.last-seen-cell span').innerText = 'Just now';
            } catch (e) {
                text.innerText = 'Error';
            }
        }

        document.querySelectorAll('.check-individual').forEach(btn => {
            btn.onclick = () => checkDevice(btn.dataset.id);
        });

        refreshAllBtn.onclick = async function () {
            syncIcon.classList.add('animate-spin');
            refreshAllBtn.disabled = true;

            const ids = [...document.querySelectorAll('tr[data-ip-id]')].map(tr => ({
                id: tr.dataset.ipId,
                source: tr.dataset.source
            }));

            // Sequence checking to avoid server overload
            for (const item of ids) {
                await checkDevice(item.id);
            }

            syncIcon.classList.remove('animate-spin');
            refreshAllBtn.disabled = false;
            location.reload(); // Hard refresh to update stats cards
        };
    });
</script>

<?php include '../../includes/footer.php'; ?>