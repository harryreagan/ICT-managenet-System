<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$pageTitle = "Network Diagnostics";
include '../../includes/header.php';
?>

<div class="mb-6 fade-in-up">
    <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Network Diagnostics</h1>
    <p class="text-slate-500 mt-1">Ping any device across VLANs to verify connectivity and latency.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in-up">
    <!-- Tool Panel -->
    <div class="lg:col-span-1 space-y-6">
        <div class="saas-card p-6">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Ping Tool</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Target IP /
                        Hostname</label>
                    <div class="relative">
                        <input type="text" id="target-ip" placeholder="e.g. 192.168.10.1"
                            class="w-full pl-4 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-sm transition-all focus:bg-white font-mono">
                        <button id="ping-btn"
                            class="absolute right-2 top-1.5 p-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg shadow-lg shadow-primary-500/20 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <p class="text-[10px] text-slate-400 leading-relaxed italic">
                        Note: Ensure ICMP is allowed between the server and the target VLAN. Some devices may not
                        respond to pings even if they are online.
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="saas-card p-6">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Gateways</h3>
            <div class="space-y-2">
                <?php
                $stmt = $pdo->query("SELECT name, gateway FROM networks WHERE gateway IS NOT NULL AND gateway != '' ORDER BY vlan_tag ASC");
                while ($net = $stmt->fetch()):
                    ?>
                    <button onclick="document.getElementById('target-ip').value = '<?php echo $net['gateway']; ?>'"
                        class="w-full flex justify-between items-center p-2 hover:bg-slate-50 rounded-lg group transition-colors">
                        <span class="text-xs text-slate-600 group-hover:text-primary-600">
                            <?php echo htmlspecialchars($net['name']); ?>
                        </span>
                        <span class="font-mono text-[10px] text-slate-400">
                            <?php echo htmlspecialchars($net['gateway']); ?>
                        </span>
                    </button>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Results Panel -->
    <div class="lg:col-span-2">
        <div class="saas-card p-6 h-full flex flex-col">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Execution Results</h3>
                <div id="status-indicator"
                    class="hidden flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                    <span class="indicator-pip w-2 h-2 mr-2 rounded-full"></span>
                    <span class="indicator-text"></span>
                </div>
            </div>

            <div id="results-area"
                class="flex-grow bg-slate-900 rounded-xl p-6 font-mono text-sm text-slate-300 overflow-y-auto space-y-4 min-h-[400px]">
                <div class="text-slate-500 italic">Enter an IP address and click the lightning bolt to start
                    diagnostics...</div>
            </div>

            <!-- Detailed Stats -->
            <div id="stats-panel" class="hidden mt-6 grid grid-cols-2 gap-4">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Latency</p>
                    <p id="stat-latency" class="text-xl font-black text-slate-800">--</p>
                </div>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">TTL</p>
                    <p id="stat-ttl" class="text-xl font-black text-slate-800">--</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pingBtn = document.getElementById('ping-btn');
        const targetInput = document.getElementById('target-ip');
        const resultsArea = document.getElementById('results-area');
        const statusIndicator = document.getElementById('status-indicator');
        const indicatorPip = statusIndicator.querySelector('.indicator-pip');
        const indicatorText = statusIndicator.querySelector('.indicator-text');
        const statsPanel = document.getElementById('stats-panel');
        const statLatency = document.getElementById('stat-latency');
        const statTtl = document.getElementById('stat-ttl');

        async function runPing() {
            const ip = targetInput.value.trim();
            if (!ip) return;

            // Reset UI
            resultsArea.innerHTML = `<div class="text-primary-400 animate-pulse">Pinging ${ip}... Requesting ICMP packet...</div>`;
            statusIndicator.classList.remove('hidden');
            indicatorPip.className = 'indicator-pip w-2 h-2 mr-2 rounded-full bg-slate-500 animate-ping';
            indicatorText.innerText = 'PROCESSING';
            statusIndicator.className = 'flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500';
            statsPanel.classList.add('hidden');
            pingBtn.disabled = true;

            try {
                const response = await fetch(`api/ping.php?ip=${encodeURIComponent(ip)}`);
                const data = await response.json();

                if (data.error) {
                    resultsArea.innerHTML = `<div class="text-red-400">ERROR: ${data.error}</div>`;
                    indicatorPip.className = 'indicator-pip w-2 h-2 mr-2 rounded-full bg-red-500';
                    indicatorText.innerText = 'ERROR';
                    statusIndicator.className = 'flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-600';
                } else {
                    let outputHtml = `<div class="text-slate-500 border-b border-slate-800 pb-2 mb-2">C:\\System32> ping ${data.ip}</div>`;

                    if (data.status === 'online') {
                        outputHtml += `<div class="text-emerald-400">Reply from ${data.ip}: bytes=32 time=${data.latency} TTL=${data.ttl}</div>`;
                        outputHtml += `<div class="mt-4 text-emerald-500 font-bold">DEVICE IS REACHABLE</div>`;

                        indicatorPip.className = 'indicator-pip w-2 h-2 mr-2 rounded-full bg-emerald-500';
                        indicatorText.innerText = 'SUCCESS';
                        statusIndicator.className = 'flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-600';

                        statLatency.innerText = data.latency;
                        statTtl.innerText = data.ttl;
                        statsPanel.classList.remove('hidden');
                    } else {
                        outputHtml += `<div class="text-amber-400">Request timed out.</div>`;
                        outputHtml += `<div class="mt-4 text-amber-500 font-bold">DEVICE UNREACHABLE</div>`;

                        indicatorPip.className = 'indicator-pip w-2 h-2 mr-2 rounded-full bg-amber-500';
                        indicatorText.innerText = 'TIMEOUT';
                        statusIndicator.className = 'flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-600';
                    }

                    outputHtml += `<div class="mt-4 text-slate-600 text-[10px] pt-4 border-t border-slate-800">RAW SYSTEM OUTPUT:</div>`;
                    outputHtml += `<pre class="text-[10px] text-slate-600 whitespace-pre-wrap">${data.raw_output}</pre>`;

                    resultsArea.innerHTML = outputHtml;
                }
            } catch (e) {
                resultsArea.innerHTML = `<div class="text-red-400">ERROR: Failed to connect to diagnostic service.</div>`;
            } finally {
                pingBtn.disabled = false;
            }
        }

        pingBtn.onclick = runPing;
        targetInput.onkeydown = (e) => { if (e.key === 'Enter') runPing(); };
    });
</script>

<?php include '../../includes/footer.php'; ?>