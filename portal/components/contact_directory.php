<?php
/**
 * Renders the IT Contact Directory Widget
 * used in Portal Dashboard
 */
function renderContactDirectory($pdo, $role)
{
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-6">
        <h3
            class="font-bold text-slate-800 text-sm uppercase tracking-wider mb-4 border-b border-gray-100 pb-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                </path>
            </svg>
            Contact Support
            <div class="ml-auto flex items-center gap-2">
                <a href="contacts.php" title="View IT Directory" class="text-primary-500 hover:text-primary-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </a>
                <?php if ($role === 'admin'): ?>
                    <a href="/ict/modules/settings/index.php" title="Edit Contact Info"
                        class="text-primary-500 hover:text-primary-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                            </path>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </h3>

        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="bg-indigo-50 p-2 rounded-lg text-indigo-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-bold uppercase">IT Duty Mobile</p>
                    <p class="text-sm font-bold text-slate-800">
                        <?= htmlspecialchars(get_setting($pdo, 'contact_duty_mobile', '0743 606 108')) ?>
                    </p>
                    <p class="text-[10px] text-slate-400 italic">
                        <?= htmlspecialchars(get_setting($pdo, 'contact_duty_mobile_note', 'Calls only when unavailable in office')) ?>
                    </p>
                </div>
            </div>

            <hr class="border-slate-50">

            <!-- Quick Feedback -->
            <div>
                <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Rate your experience</h4>
                <div class="flex justify-between gap-2" id="feedback-buttons">
                    <button onclick="submitFeedback('bad')"
                        class="flex-1 py-2 bg-slate-50 hover:bg-red-50 text-2xl rounded-lg hover:scale-110 transition-transform grayscale hover:grayscale-0 text-center"
                        title="Bad">😡</button>
                    <button onclick="submitFeedback('okay')"
                        class="flex-1 py-2 bg-slate-50 hover:bg-yellow-50 text-2xl rounded-lg hover:scale-110 transition-transform grayscale hover:grayscale-0 text-center"
                        title="Okay">😐</button>
                    <button onclick="submitFeedback('great')"
                        class="flex-1 py-2 bg-slate-50 hover:bg-emerald-50 text-2xl rounded-lg hover:scale-110 transition-transform grayscale hover:grayscale-0 text-center"
                        title="Great">😃</button>
                </div>
                <div id="feedback-message"
                    class="hidden text-center py-2 text-xs font-bold text-emerald-600 bg-emerald-50 rounded-lg">
                    Thanks for your feedback!
                </div>

                <script>
                    function submitFeedback(rating) {
                        const buttons = document.getElementById('feedback-buttons');
                        const message = document.getElementById('feedback-message');

                        // Visual immediate feedback
                        buttons.style.opacity = '0.5';
                        buttons.style.pointerEvents = 'none';

                        fetch('record_feedback.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ rating: rating })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    buttons.classList.add('hidden');
                                    message.classList.remove('hidden');
                                } else {
                                    // Reset on error
                                    buttons.style.opacity = '1';
                                    buttons.style.pointerEvents = 'auto';
                                    alert('Error saving feedback. Please try again.');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                buttons.style.opacity = '1';
                                buttons.style.pointerEvents = 'auto';
                            });
                    }
                </script>
            </div>
        </div>
    </div>
    <?php
}
?>