<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dallas Premiere Hotel - IT Management</title>

    <!-- Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="<?php echo BASE_URL; ?>/assets/css/custom.css" rel="stylesheet">
    <script src="<?php echo BASE_URL; ?>/assets/js/live-search.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fbf8f1',
                            100: '#f4ecd9',
                            500: '#b59454', // Gold
                            600: '#a3834a', // Darker Gold
                            700: '#8c7040',
                            800: '#6e5832',
                            900: '#4d3d23',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('modal', {
                show: false,
                type: 'confirm', // 'confirm' or 'success'
                title: 'Confirm Action',
                message: 'Are you sure you want to proceed?',
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                formId: null,

                trigger(formId, message = null, title = null) {
                    this.type = 'confirm';
                    this.formId = formId;
                    if (message) this.message = message;
                    if (title) this.title = title;
                    this.show = true;
                },

                success(message, title = 'Success!') {
                    this.type = 'success';
                    this.message = message;
                    this.title = title;
                    this.show = true;
                },

                confirm() {
                    if (this.type === 'confirm' && this.formId) {
                        document.getElementById(this.formId).submit();
                    }
                    this.show = false;
                }
            })
        })
    </script>
</head>

<body class="bg-gray-50 text-slate-800 font-sans antialiased" x-data="{ sidebarOpen: false }">

    <!-- Mobile Header -->
    <div
        class="md:hidden flex items-center justify-between bg-white shadow-sm p-4 border-b border-gray-200 z-20 relative">
        <div class="flex items-center space-x-2">
            <div
                class="w-8 h-8 flex items-center justify-center bg-primary-50 rounded-lg text-primary-600 border border-primary-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                    </path>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-slate-800">Dallas Premiere</h1>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <?php
            // Run global alarm checks (throttled in functions.php)
            checkAlarms($pdo);

            $recentNotifs = [];
            $unreadCountFull = 0;
            if (isAdmin()) {
                // Get all unread count for the badge and sound trigger
                $countStmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
                $unreadCountFull = (int) $countStmt->fetchColumn();

                // Get recent notifications for the dropdown
                $notifStmt = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5");
                $recentNotifs = $notifStmt->fetchAll();
            }
            ?>
            <button @click="sidebarOpen = !sidebarOpen"
                class="text-slate-500 focus:outline-none hover:text-primary-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>
        </div>
    </div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 w-64 bg-slate-900 transition-transform duration-300 md:relative md:translate-x-0 flex flex-col shadow-xl">

            <!-- Logo -->
            <div class="p-6 border-b border-slate-800 flex items-center space-x-3">
                <div
                    class="w-10 h-10 flex items-center justify-center bg-primary-500 rounded-xl text-white shadow-lg shadow-primary-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-white uppercase">Dallas Premiere</h1>
                    <p class="text-[9px] uppercase tracking-widest text-slate-400 font-bold">IT Operations</p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 custom-scrollbar">
                <a href="<?php echo BASE_URL; ?>/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive(['index.php', 'dashboard']); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                        </path>
                    </svg>
                    <span class="text-sm">Dashboard</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/modules/department/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/department'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                    <span class="text-sm">Department Overview</span>
                    <span
                        class="inline-flex items-center justify-center px-2 ml-auto text-xs font-medium text-emerald-800 bg-emerald-100 rounded-full">New</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/modules/handover/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/handover'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4">
                        </path>
                    </svg>
                    <span class="text-sm">Shift Handover</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/portal/index.php" target="_blank"
                    class="flex items-center space-x-3 px-3 py-2.5 text-slate-400 hover:bg-slate-800 hover:text-white border-l-2 border-transparent transition-all duration-200">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    <span class="text-sm">Service Portal</span>
                </a>

                <div class="pt-6 pb-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Management
                </div>

                <?php if (hasRole(['admin', 'manager'])): ?>
                    <a href="<?php echo BASE_URL; ?>/modules/reports/index.php"
                        class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/reports'); ?>">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        <span class="text-sm">Analytics & Reports</span>
                    </a>
                <?php endif; ?>

                <a href="<?php echo BASE_URL; ?>/modules/renewals/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/renewals'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span class="text-sm">Renewals</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/modules/vendors/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/vendors'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                    <span class="text-sm">Vendors & SLA</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/modules/hardware/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/hardware'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z">
                        </path>
                    </svg>
                    <span class="text-sm">Hardware</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/modules/hardware/asset_requests.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/asset_requests'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span class="text-sm">Asset Requests</span>
                </a>


                <div class="pt-6 pb-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Security &
                    Ops</div>

                <a href="<?php echo BASE_URL; ?>/modules/external_links/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/external_links'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                        </path>
                    </svg>
                    <span class="text-sm">External Systems</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/modules/credentials/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive(['/vault', '/credentials']); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                    <span class="text-sm">Vault</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/modules/knowledgebase/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive(['/incidents', '/knowledgebase']); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                        </path>
                    </svg>
                    <span class="text-sm">Incidents & KB</span>
                </a>

                <?php if (hasRole(['admin'])): ?>
                    <a href="/ict/modules/maintenance/index.php"
                        class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/maintenance'); ?>">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4">
                            </path>
                        </svg>
                        <span class="text-sm">System Maintenance</span>
                    </a>
                <?php endif; ?>


                <div class="pt-6 pb-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Assets</div>

                <a href="<?php echo BASE_URL; ?>/modules/networks/static_devices.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/static_devices'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z">
                        </path>
                    </svg>
                    <span class="text-sm">Static Devices</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/modules/networks/diagnostic.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/diagnostic'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span class="text-sm">Network Diagnostics</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/modules/inventory/index.php"
                    class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/inventory'); ?>">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span class="text-sm">Inventory</span>
                </a>

                <?php if (isAdmin()): ?>
                    <a href="/ict/modules/users/index.php"
                        class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/users'); ?>">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        <span class="text-sm">Users</span>
                    </a>
                    <a href="/ict/modules/settings/index.php"
                        class="flex items-center space-x-3 px-3 py-2.5 transition-all duration-200 <?php echo isActive('/settings'); ?>">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                            </path>
                        </svg>
                        <span class="text-sm">Settings</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="p-4 border-t border-slate-800 bg-slate-900">
                <a href="/ict/logout.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                        </path>
                    </svg>
                    <span class="text-sm">Sign Out</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 h-full" x-data="{ notificationsOpen: false }"
            x-init="<?php if (isset($_SESSION['success'])): ?>
                        $nextTick(() => { 
                            $store.modal.success('<?php echo htmlspecialchars($_SESSION['success']); ?>');
                        });
                        <?php unset($_SESSION['success']); ?>
                      <?php endif; ?>">

            <!-- Desktop Top Bar -->
            <header
                class="hidden md:flex items-center justify-between bg-white border-b border-gray-200 px-8 py-4 sticky top-0 z-20">
                <div class="flex-1 max-w-xl">
                    <div class="relative group">
                        <span
                            class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 group-focus-within:text-primary-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text" placeholder="Search assets, tickets, or users..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white outline-none transition-all">
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <!-- On Duty Toggle -->
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">On
                            Duty</span>
                        <button onclick="toggleDutyInHeader()" class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none 
                            <?php echo isUserOnDuty($pdo) ? 'bg-emerald-500' : 'bg-slate-200'; ?>">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out 
                                <?php echo isUserOnDuty($pdo) ? 'translate-x-5' : 'translate-x-0'; ?>"></span>
                        </button>
                    </div>

                    <script>
                        async function toggleDutyInHeader() {
                            try {
                                const response = await fetch('/ict/modules/handover/toggle_duty.php', { method: 'POST' });
                                const result = await response.json();
                                if (result.success) {
                                    window.location.reload();
                                }
                            } catch (err) { console.error(err); }
                        }
                    </script>
                    <!-- Notification Bell -->
                    <div class="relative" @click.away="notificationsOpen = false">
                        <button @click="notificationsOpen = !notificationsOpen"
                            class="relative p-2 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                </path>
                            </svg>
                            <?php if ($unreadCountFull > 0): ?>
                                <span
                                    class="absolute top-1.5 right-1.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full border-2 border-white ring-1 ring-red-500/20">
                                    <?php echo $unreadCountFull; ?>
                                </span>
                            <?php endif; ?>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="notificationsOpen" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            class="absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-30"
                            style="display: none;">
                            <div class="p-4 border-b border-gray-50 flex justify-between items-center bg-slate-50/50">
                                <h3 class="text-sm font-bold text-slate-800">Notifications</h3>
                                <?php if ($unreadCountFull > 0): ?>
                                    <button onclick="markAllRead()"
                                        class="text-[11px] font-bold text-primary-600 hover:text-primary-700">Mark all as
                                        read</button>
                                <?php endif; ?>
                            </div>
                            <div class="max-h-[400px] overflow-y-auto custom-scrollbar">
                                <?php if (empty($recentNotifs)): ?>
                                    <div class="p-8 text-center">
                                        <div
                                            class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-300">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                                </path>
                                            </svg>
                                        </div>
                                        <p class="text-sm text-slate-400">All caught up!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentNotifs as $n): ?>
                                        <a href="<?php echo $n['link_url'] ?: '#'; ?>"
                                            onclick="markRead(<?php echo $n['id']; ?>)"
                                            class="block p-4 hover:bg-slate-50 transition-colors border-b border-gray-50 last:border-0 group">
                                            <div class="flex gap-3">
                                                <div
                                                    class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center 
                                                    <?php echo $n['type'] === 'warning' ? 'bg-amber-100 text-amber-600' : ($n['type'] === 'alert' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'); ?>">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <?php if ($n['type'] === 'warning'): ?>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                                            </path>
                                                        <?php elseif ($n['type'] === 'alert'): ?>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        <?php else: ?>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                            </path>
                                                        <?php endif; ?>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <p
                                                        class="text-xs font-semibold text-slate-700 leading-tight group-hover:text-primary-600 transition-colors">
                                                        <?php echo htmlspecialchars($n['message']); ?>
                                                    </p>
                                                    <span class="text-[10px] text-slate-400 mt-1 block">
                                                        <?php echo time_elapsed_string($n['created_at']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <a href="/ict/modules/notifications/index.php"
                                class="block p-3 text-center text-[11px] font-bold text-slate-500 hover:bg-slate-50 transition-colors border-t border-gray-50">
                                View all notifications
                            </a>
                        </div>
                    </div>

                    <div class="h-8 w-px bg-gray-200"></div>

                    <!-- User Profile -->
                    <div class="flex items-center space-x-3">
                        <div class="text-right hidden lg:block">
                            <span
                                class="block text-sm font-bold text-slate-800"><?php echo $_SESSION['username'] ?? 'User'; ?></span>
                            <span
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?php echo $_SESSION['role'] ?? 'Staff'; ?></span>
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-primary-500 text-white flex items-center justify-center font-bold shadow-lg shadow-primary-500/20">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <a href="/ict/logout.php" title="Sign Out"
                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                </path>
                            </svg>
                        </a>
                    </div>
                </div>
            </header>

            <script>
                let lastUnreadCount = <?php echo $unreadCountFull ?? 0; ?>;

                function markRead(id) {
                    fetch('/ict/includes/api/mark_read.php?id=' + id);
                }
                function markAllRead() {
                    fetch('/ict/includes/api/mark_read.php?id=all').then(() => {
                        window.location.reload();
                    });
                }

                function checkNewNotifications() {
                    fetch('/ict/includes/api/get_unread_count.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.count > lastUnreadCount) {
                                const sound = document.getElementById('notificationSound');
                                if (sound) {
                                    sound.play().catch(e => console.log('Audio blocked or failed:', e));
                                }
                            }
                            lastUnreadCount = data.count;
                        })
                        .catch(err => console.error('Error checking notifications:', err));
                }

                // Poll for new notifications every 60 seconds
                setInterval(checkNewNotifications, 60000);
            </script>
            <audio id="notificationSound" preload="auto">
                <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
            </audio>
            <div class="container mx-auto px-6 py-8 h-full">