<?php
function renderGuestWifi($guestWifi)
{
    ?>
    <!-- Guest WiFi Widget -->
    <div class="saas-card p-5 text-left bg-gradient-to-br from-primary-50 to-white border-primary-100">
        <div class="flex justify-between items-start mb-4 text-left">
            <div class="flex items-center text-left">
                <div
                    class="w-10 h-10 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center mr-3 text-left">
                    <svg class="w-6 h-6 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 text-left">Guest WiFi</h3>
                    <p class="text-[10px] text-slate-500 text-left">
                        <?php
                        if ($guestWifi['password_last_changed']) {
                            echo 'Updated ' . time_elapsed_string($guestWifi['password_last_changed']);
                        } else {
                            echo 'Last updated: Never';
                        }
                        ?>
                    </p>
                </div>
            </div>
            <button onclick="document.getElementById('guestWifiModal').classList.remove('hidden')"
                class="p-2 text-primary-600 hover:bg-primary-100 rounded-lg transition-colors text-left">
                <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                    </path>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-2 text-left">
            <div class="bg-white/60 p-3 rounded-lg border border-primary-100 text-left">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Network
                    Name (SSID)</p>
                <p class="font-bold text-slate-800 text-lg text-left">
                    <?php echo htmlspecialchars($guestWifi['hotspot_ssid'] ?: 'Not Set'); ?>
                </p>
            </div>
            <div class="bg-white/60 p-3 rounded-lg border border-primary-100 text-left">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-left">Password
                </p>
                <div class="flex items-center justify-between text-left">
                    <p class="font-mono font-bold text-primary-600 text-lg text-left">
                        <?php echo htmlspecialchars($guestWifi['wifi_password'] ?: '---'); ?>
                    </p>
                    <button onclick="copyToClipboard('<?php echo addslashes($guestWifi['wifi_password']); ?>')"
                        class="text-slate-400 hover:text-primary-600 text-left">
                        <svg class="w-4 h-4 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Guest WiFi Modal -->
    <div id="guestWifiModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-96 p-6 transform transition-all scale-100">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-800">Update Guest WiFi</h3>
                <button onclick="document.getElementById('guestWifiModal').classList.add('hidden')"
                    class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <form action="index.php" method="POST">
                <input type="hidden" name="update_guest_wifi" value="1">
                <input type="hidden" name="network_id" value="<?php echo $guestWifi['id']; ?>">

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">SSID
                            (Name)</label>
                        <input type="text" name="wifi_ssid"
                            value="<?php echo htmlspecialchars($guestWifi['hotspot_ssid']); ?>"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-primary-500 font-bold text-slate-700">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Password</label>
                        <div class="relative">
                            <input type="text" name="wifi_password" id="newWifiPass"
                                value="<?php echo htmlspecialchars($guestWifi['wifi_password']); ?>"
                                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-primary-500 font-mono text-primary-600 font-bold">
                            <button type="button" onclick="generatePassword()"
                                class="absolute right-2 top-2 text-xs font-bold text-primary-600 hover:text-primary-700">
                                Generate
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('guestWifiModal').classList.add('hidden')"
                        class="px-4 py-2 text-slate-500 hover:text-slate-700 font-bold text-sm">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-lg shadow-sm text-sm">Update
                        Network</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function generatePassword() {
            const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
            let pass = "";
            for (let i = 0; i < 8; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('newWifiPass').value = pass;
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // You could show a toast notification here
                alert("Password copied to clipboard!");
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
    </script>
    <?php
}
?>