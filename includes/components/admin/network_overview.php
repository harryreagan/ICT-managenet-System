<?php
function renderNetworkOverview($data)
{
    ?>
    <!-- Network Quick Access -->
    <div class="saas-card p-4 text-left">
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center text-left">
            <svg class="w-4 h-4 mr-2 text-primary-500 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Network Quick Access
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
            <?php
            foreach ($data['vlans'] as $vlan):
                // Determine icon based on name
                $icon_class = "bg-blue-100 text-blue-600";
                $svg_path = 'd="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"'; // Default LAN icon
        
                if (stripos($vlan['name'], 'VoIP') !== false) {
                    $icon_class = "bg-indigo-100 text-indigo-600";
                    $svg_path = 'd="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"';
                } elseif (stripos($vlan['name'], 'CCTV') !== false || stripos($vlan['name'], 'Camera') !== false) {
                    $icon_class = "bg-rose-100 text-rose-600";
                    $svg_path = 'd="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"';
                } elseif (stripos($vlan['name'], 'Guest') !== false || stripos($vlan['name'], 'WiFi') !== false) {
                    $icon_class = "bg-amber-100 text-amber-600";
                    $svg_path = 'd="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071a10 10 0 0114.142 0M2.121 8.586a15 15 0 0121.214 0"';
                }
                ?>
                <a href="modules/networks/view.php?id=<?php echo $vlan['id']; ?>"
                    class="flex items-center p-3 bg-slate-50 hover:bg-white border border-slate-100 hover:border-primary-200 rounded-lg transition-all group shadow-sm hover:shadow-md text-left">
                    <div
                        class="w-10 h-10 rounded-lg <?php echo $icon_class; ?> flex items-center justify-center mr-3 text-left">
                        <svg class="w-5 h-5 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" <?php echo $svg_path; ?>>
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-700 text-sm group-hover:text-primary-600 text-left">
                            <?php echo htmlspecialchars($vlan['name']); ?>
                        </h4>
                        <p class="text-[10px] text-slate-400 font-mono text-left">
                            <?php echo htmlspecialchars($vlan['gateway'] ?: $vlan['subnet']); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- External Systems Quick Access -->
    <?php if (!empty($data['externalLinks'])): ?>
        <div class="saas-card p-4 text-left">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center text-left">
                    <svg class="w-4 h-4 mr-2 text-primary-500 text-left" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                        </path>
                    </svg>
                    External Systems
                </h3>
                <a href="modules/external_links/index.php"
                    class="text-[10px] font-bold text-primary-600 hover:text-primary-700">Manage</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
                <?php foreach ($data['externalLinks'] as $link): ?>
                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"
                        class="flex items-center p-3 bg-slate-50 hover:bg-white border border-slate-100 hover:border-primary-200 rounded-lg transition-all group shadow-sm hover:shadow-md text-left">
                        <div
                            class="w-10 h-10 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center mr-3 text-left group-hover:bg-primary-100 group-hover:text-primary-600 transition-colors">
                            <!-- Simple Icon Logic based on name/category -->
                            <?php if (stripos($link['name'], 'wifi') !== false || $link['category'] == 'Network'): ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0">
                                    </path>
                                </svg>
                            <?php elseif (stripos($link['name'], 'camera') !== false || $link['category'] == 'Security'): ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                                    </path>
                                </svg>
                            <?php else: ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="overflow-hidden">
                            <h4 class="font-bold text-slate-700 text-sm group-hover:text-primary-600 text-left truncate"
                                title="<?php echo htmlspecialchars($link['name']); ?>">
                                <?php echo htmlspecialchars($link['name']); ?>
                            </h4>
                            <p class="text-[10px] text-slate-400 font-mono text-left truncate">
                                <?php echo htmlspecialchars(parse_url($link['url'], PHP_URL_HOST)); ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php
}
?>