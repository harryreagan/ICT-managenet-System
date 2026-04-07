<?php foreach ($logs as $log): ?>
    <tr class="hover:bg-white/50 transition-all duration-300 group ring-inset hover:ring-1 hover:ring-primary-100 values-row"
        data-id="<?php echo $log['id']; ?>">
        <td class="pl-6 py-6 whitespace-nowrap w-4 align-top pt-8">
            <input type="checkbox"
                class="issue-checkbox w-4 h-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500 cursor-pointer accent-primary-600 transition-transform hover:scale-110"
                value="<?php echo $log['id']; ?>">
        </td>
        <td class="px-4 py-6 whitespace-nowrap">
            <div class="flex flex-col space-y-2">
                <span class="px-3 py-1 inline-flex text-[9px] uppercase font-black tracking-widest rounded-full w-max shadow-sm <?php
                echo match ($log['priority']) {
                    'critical' => 'bg-red-500 text-white shadow-red-500/20',
                    'high' => 'bg-orange-500 text-white shadow-orange-500/20',
                    'medium' => 'bg-amber-500 text-white shadow-amber-500/20',
                    default => 'bg-primary-500 text-white shadow-primary-500/20'
                };
                ?>">
                    <?php echo $log['priority']; ?>
                </span>
                <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest pl-1">
                    <?php echo str_replace('_', ' ', $log['status']); ?>
                </span>
            </div>
        </td>
        <td class="px-8 py-6 max-w-md">
            <div class="text-sm font-black text-slate-900 group-hover:text-primary-600 transition-colors leading-tight">
                <?php echo htmlspecialchars($log['title']); ?>
            </div>
            <div class="text-[10px] text-slate-500 mt-2 flex items-center space-x-2">
                <span
                    class="bg-slate-900 text-slate-400 px-2 py-0.5 rounded-md font-black text-[9px] uppercase tracking-widest">
                    <?php echo htmlspecialchars($log['system_affected']); ?>
                </span>
            </div>
        </td>
        <td class="px-8 py-6 whitespace-nowrap">
            <?php if ($log['assigned_to']): ?>
                <div class="flex items-center">
                    <div
                        class="w-8 h-8 rounded-xl bg-primary-50 flex items-center justify-center text-[10px] font-black text-primary-600 mr-3 border border-primary-100 uppercase shadow-sm">
                        <?php echo substr($log['assigned_to'], 0, 1); ?>
                    </div>
                    <span
                        class="text-[10px] font-black text-slate-700 uppercase tracking-tight"><?php echo htmlspecialchars($log['assigned_to']); ?></span>
                </div>
            <?php else: ?>
                <span class="text-slate-400 text-[10px] font-black uppercase tracking-widest opacity-50">Unassigned</span>
            <?php endif; ?>
        </td>
        <td class="px-8 py-6 whitespace-nowrap">
            <div class="flex flex-col">
                <span class="text-[10px] font-black text-slate-900 uppercase tracking-tight">
                    <?php echo date('M j, Y', strtotime($log['created_at'])); ?>
                </span>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                    <?php if ($log['technician_name']): ?>
                        by <?php echo htmlspecialchars($log['technician_name']); ?>
                    <?php elseif ($log['requester_username']): ?>
                        from <?php echo htmlspecialchars($log['requester_username']); ?>
                    <?php else: ?>
                        System
                    <?php endif; ?>
                </span>
            </div>
        </td>
        <td class="px-8 py-6 whitespace-nowrap text-right">
            <div
                class="flex items-center justify-end space-x-3 opacity-0 group-hover:opacity-100 transition-all transform scale-95 group-hover:scale-100">
                <a href="view.php?id=<?php echo $log['id']; ?>"
                    class="w-9 h-9 flex items-center justify-center bg-slate-900 text-white rounded-xl hover:bg-primary-500 transition-all shadow-lg hover:shadow-primary-500/20"
                    title="Analyze Incident">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </a>
                <a href="/ict/modules/knowledgebase/edit.php?id=<?php echo $log['id']; ?>"
                    class="w-9 h-9 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-xl hover:text-primary-600 hover:border-primary-100 hover:bg-primary-50 transition-all shadow-sm"
                    title="Modify Protocol">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                </a>
                <form id="delete-form-<?php echo $log['id']; ?>" action="" method="POST" class="inline">
                    <input type="hidden" name="delete_id" value="<?php echo $log['id']; ?>">
                    <button type="button"
                        @click="$store.modal.trigger('delete-form-<?php echo $log['id']; ?>', 'Are you sure you want to delete this incident?', 'Delete Incident')"
                        class="w-9 h-9 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-xl hover:text-red-600 hover:border-red-100 hover:bg-red-50 transition-all shadow-sm"
                        title="Delete Record">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                </form>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?php if (count($logs) === 0): ?>
    <tr>
        <td colspan="5" class="px-6 py-24 text-center">
            <div class="flex flex-col items-center max-w-sm mx-auto">
                <div
                    class="w-20 h-20 bg-white rounded-[2rem] shadow-xl flex items-center justify-center mb-8 text-slate-200">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-900 uppercase tracking-tighter mb-4">No Active Records</h3>
                <p class="text-slate-500 text-xs font-medium leading-relaxed mb-10">All systems are currently operating
                    within nominal parameters. Security protocols are fully synchronized.</p>
                <a href="create.php"
                    class="inline-flex items-center justify-center px-10 py-4 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-primary-500 transition-all shadow-2xl">
                    Initialize Protocol
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>