<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('index.php');

$stmt = $pdo->prepare("SELECT * FROM troubleshooting_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log)
    redirect('index.php');

$pageTitle = "View Log: " . $log['title'];

include '../../includes/header.php';
?>

<div class="space-y-8">
    <div class="max-w-4xl mx-auto">
        <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span
                        class="bg-slate-100 text-slate-700 text-xs font-bold px-3 py-1 rounded-full border border-slate-200">Issue
                        #<?php echo $log['id']; ?></span>
                    <span
                        class="bg-primary-50 text-primary-600 text-xs font-bold px-3 py-1 rounded-full border border-primary-100"><?php echo htmlspecialchars($log['system_affected']); ?></span>
                    <span
                        class="text-xs text-slate-500 font-medium"><?php echo date('M j, Y', strtotime($log['incident_date'])); ?></span>
                </div>
                <h1 class="text-3xl font-bold text-slate-800 mb-6">
                    <?php echo htmlspecialchars($log['title']); ?>
                </h1>
                <div class="flex items-center gap-4">
                    <div class="flex items-center bg-white px-4 py-2 rounded-lg border border-gray-100 shadow-sm">
                        <div
                            class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center text-primary-600 text-sm font-bold mr-3 border border-primary-100">
                            <?php echo substr($log['technician_name'], 0, 1); ?>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium">
                                Handled By</p>
                            <p class="text-sm font-bold text-slate-800">
                                <?php echo htmlspecialchars($log['technician_name']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <a href="index.php"
                    class="flex-1 md:flex-none inline-flex items-center justify-center px-6 py-2.5 bg-white text-slate-600 font-bold rounded-lg border border-gray-200 hover:bg-gray-50 transition-all shadow-sm text-sm">
                    Back to List
                </a>
                <a href="edit.php?id=<?php echo $log['id']; ?>"
                    class="flex-1 md:flex-none inline-flex items-center justify-center px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-lg shadow-sm transition-all text-sm">
                    Edit Issue
                </a>
            </div>
        </header>

        <article class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 md:p-12 mb-8">
            <div class="space-y-12">
                <!-- Symptoms Section -->
                <div>
                    <h2 class="text-xs font-bold text-primary-600 uppercase tracking-wider mb-4 flex items-center">
                        <span class="w-8 h-0.5 bg-primary-600 mr-3 rounded-full"></span>
                        Symptoms Observed
                    </h2>
                    <div class="prose max-w-none">
                        <?php echo $log['symptoms']; ?>
                    </div>
                </div>

                <!-- Root Cause Section -->
                <div>
                    <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center">
                        <span class="w-8 h-0.5 bg-slate-300 mr-3 rounded-full"></span>
                        What Caused It
                    </h2>
                    <div class="prose max-w-none text-slate-600 border-l-4 border-slate-100 pl-6">
                        <?php echo $log['root_cause'] ?: 'Analysis pending detailed investigative report.'; ?>
                    </div>
                </div>

                <!-- Intervention & Resolution -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-slate-50 rounded-xl p-6 border border-slate-100">
                        <h2 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-4">What We Did</h2>
                        <div class="prose prose-sm max-w-none steps-journal">
                            <?php echo $log['steps_taken']; ?>
                        </div>
                        <style>
                            .steps-journal p {
                                position: relative;
                                padding-left: 2rem;
                                margin-bottom: 1.5rem !important;
                                font-style: normal !important;
                                color: #64748b !important;
                            }

                            .steps-journal p::before {
                                content: "➤";
                                position: absolute;
                                left: 0;
                                color: var(--primary-600);
                                font-size: 0.75rem;
                                top: 0.25rem;
                            }
                        </style>
                    </div>

                    <div class="bg-emerald-50 rounded-xl p-6 border border-emerald-200">
                        <h2 class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-4">How It Was Fixed
                        </h2>
                        <div class="prose max-w-none text-emerald-900 font-medium leading-relaxed">
                            <?php echo $log['resolution']; ?>
                        </div>
                    </div>
                </div>

                <?php if ($log['solution_image']): ?>
                    <div class="pt-8 border-t border-slate-100">
                        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-6">Reference Image</h2>
                        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                            <img src="/ict/<?php echo $log['solution_image']; ?>" class="w-full rounded-lg">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <footer
            class="p-6 bg-slate-100 rounded-xl border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-lg bg-white flex items-center justify-center border border-slate-200 shadow-sm">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.040L3 14.535a11.958 11.958 0 005.632 9.987L12 24l3.368-1.478a11.958 11.958 0 005.632-9.987l-.764-8.591z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Deployment Integrity</p>
                    <p class="text-sm font-bold text-slate-800">Verified by System Administrator</p>
                </div>
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Finalized: <?php echo date('F j, Y @ H:i', strtotime($log['created_at'])); ?> UTC
            </div>
        </footer>

        <!-- Activities -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mt-8">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                <svg class="w-5 h-5 mr-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Activity Timeline
            </h3>

            <div id="activityTimeline" class="space-y-3">
                <!-- Activity will be loaded here via JavaScript -->
            </div>
        </div>

        <!-- Related Issues Card -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mt-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                Related Issues
            </h3>

            <div class="relative mb-4">
                <input type="text" id="issueSearchInput"
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none"
                    placeholder="Link issue ID or title..." autocomplete="off">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <div id="issueSearchResults" class="absolute z-10 w-full bg-white border border-gray-100 rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto"></div>
            </div>

            <div id="linkedIssuesList" class="space-y-2">
                <!-- Links will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
    // Load comments and activity on page load
    document.addEventListener('DOMContentLoaded', function () {
        loadCommentsAndActivity();
        loadTimeLogs();
        loadLinkedIssues();
    });

    const issueId = <?php echo $log['id']; ?>;

    // Issue Searching
    const searchInput = document.getElementById('issueSearchInput');
    const searchResults = document.getElementById('issueSearchResults');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value;
        
        if (query.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`api/search_issues.php?q=${encodeURIComponent(query)}&exclude_id=${issueId}`)
                .then(response => response.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if (data.success && data.issues.length > 0) {
                        searchResults.classList.remove('hidden');
                        data.issues.forEach(issue => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-2 hover:bg-slate-50 cursor-pointer text-sm border-b border-gray-50 last:border-0';
                            div.innerHTML = `<span class="font-bold text-slate-700">#${issue.id}</span> <span class="text-slate-600">${escapeHtml(issue.title)}</span>`;
                            div.onclick = () => linkIssue(issue.id);
                            searchResults.appendChild(div);
                        });
                    } else {
                        searchResults.classList.add('hidden');
                    }
                });
        }, 300);
    });

    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });

    function linkIssue(targetId) {
        const formData = new FormData();
        formData.append('source_id', issueId);
        formData.append('target_id', targetId);
        formData.append('type', 'relates_to'); // Default type

        fetch('api/link_issue.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                searchInput.value = '';
                searchResults.classList.add('hidden');
                loadLinkedIssues();
                loadCommentsAndActivity(); // Refresh activity
            } else {
                alert(data.message);
            }
        });
    }

    function loadLinkedIssues() {
        fetch(`api/get_linked_issues.php?issue_id=${issueId}`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('linkedIssuesList');
                if (data.success) {
                    if (data.links.length === 0) {
                        container.innerHTML = '<p class="text-center text-slate-400 text-[10px] py-2">No related issues</p>';
                        return;
                    }
                    container.innerHTML = data.links.map(link => `
                        <div class="flex items-center justify-between p-2 bg-slate-50 rounded-lg border border-slate-100 group">
                            <a href="view.php?id=${link.linked_issue_id}" class="flex-1 min-w-0 flex items-center gap-2 hover:text-primary-600">
                                <span class="text-xs font-bold text-slate-500">#${link.linked_issue_id}</span>
                                <span class="text-xs text-slate-700 truncate">${escapeHtml(link.linked_issue_title)}</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600">${link.linked_issue_status}</span>
                            </a>
                            <button onclick="unlinkIssue(${link.link_id})" class="text-slate-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    `).join('');
                }
            });
    }

    function unlinkIssue(linkId) {
        if (!confirm('Are you sure you want to remove this link?')) return;

        const formData = new FormData();
        formData.append('link_id', linkId);

        fetch('api/unlink_issue.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadLinkedIssues();
                loadCommentsAndActivity();
            }
        });
    }

    // Handle time log form submission
    document.getElementById('timeLogForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('api/log_time.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.reset();
                loadTimeLogs();
                loadCommentsAndActivity(); // Refresh activity timeline
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to log time');
        });
    });

    function loadTimeLogs() {
        const issueId = <?php echo $log['id']; ?>;
        
        fetch(`api/get_time_logs.php?issue_id=${issueId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalTimeDisplay').textContent = data.total_hours + 'h';
                    renderTimeLogs(data.logs);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function renderTimeLogs(logs) {
        const container = document.getElementById('recentTimeLogs');
        
        if (logs.length === 0) {
            container.innerHTML = '<p class="text-center text-slate-400 text-[10px] py-2">No time logged yet</p>';
            return;
        }
        
        container.innerHTML = logs.map(log => {
            const date = new Date(log.logged_at);
            return `
                <div class="flex justify-between items-start text-xs border-b border-gray-50 last:border-0 pb-2 last:pb-0">
                    <div>
                        <span class="font-bold text-slate-700">${log.full_name || log.username}</span>
                        <span class="text-slate-400 mx-1">•</span>
                        <span class="text-slate-500">${formatDate(date)}</span>
                        ${log.description ? `<p class="text-slate-400 mt-0.5">${escapeHtml(log.description)}</p>` : ''}
                    </div>
                    <span class="font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded">${log.hours_spent}h</span>
                </div>
            `;
        }).join('');
    }

    // Handle comment form submission
    document.getElementById('commentForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('api/add_comment.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('commentText').value = '';
                    document.getElementById('isInternal').checked = false;
                    loadCommentsAndActivity();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add comment');
            });
    });

    function loadCommentsAndActivity() {
        const issueId = <?php echo $log['id']; ?>;

        fetch(`api/get_comments.php?issue_id=${issueId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderComments(data.comments);
                    renderActivity(data.activities);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function renderComments(comments) {
        const container = document.getElementById('commentsList');

        if (comments.length === 0) {
            container.innerHTML = '<p class="text-center text-slate-400 text-sm py-8">No comments yet. Be the first to comment!</p>';
            return;
        }

        container.innerHTML = comments.map(comment => {
            const date = new Date(comment.created_at);
            const isInternal = comment.is_internal == 1;

            return `
            <div class="flex gap-4 p-4 rounded-lg border ${isInternal ? 'bg-amber-50 border-amber-200' : 'bg-slate-50 border-slate-200'}">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-bold text-sm">
                        ${comment.full_name ? comment.full_name.charAt(0) : 'U'}
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold text-slate-800 text-sm">${comment.full_name || comment.username}</span>
                        ${isInternal ? '<span class="text-xs bg-amber-600 text-white px-2 py-0.5 rounded-full font-bold">Internal</span>' : ''}
                        <span class="text-xs text-slate-400">${formatDate(date)}</span>
                    </div>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">${escapeHtml(comment.comment_text)}</p>
                </div>
            </div>
        `;
        }).join('');
    }

    function renderActivity(activities) {
        const container = document.getElementById('activityTimeline');

        if (activities.length === 0) {
            container.innerHTML = '<p class="text-center text-slate-400 text-xs py-4">No activity yet</p>';
            return;
        }

        container.innerHTML = activities.slice(0, 10).map(activity => {
            const date = new Date(activity.created_at);
            const icons = {
                'created': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>',
                'updated': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>',
                'status_changed': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'assigned': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>',
                'commented': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>',
                'attachment_added': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>',
                'time_logged': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'linked': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>'
            };

            return `
            <div class="flex gap-3 text-xs">
                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${icons[activity.activity_type] || icons['updated']}
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-slate-700 font-medium">${activity.description}</p>
                    <p class="text-slate-400 text-[10px] mt-0.5">${formatDate(date)}</p>
                </div>
            </div>
        `;
        }).join('');
    }

    function formatDate(date) {
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;

        return date.toLocaleDateString();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php include '../../includes/footer.php'; ?>