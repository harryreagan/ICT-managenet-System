<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /ict/login.php");
    exit;
}

// Basic User Info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

function renderPortalHeader($title = "Service Portal")
{
    global $username, $role;
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - Dallas Premiere Hotel</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                            display: ['Outfit', 'sans-serif']
                        },
                        colors: {
                            primary: {
                                50: '#fbf8f1',
                                100: '#f4ecd9',
                                200: '#e9d9b3',
                                500: '#b59454',
                                600: '#a3834a',
                                700: '#8c703f',
                                800: '#715a33',
                                900: '#4d3d23',
                            },
                            slate: {
                                850: '#111827',
                                950: '#070b14'
                            }
                        },
                        animation: {
                            'vibrant-pop': 'vibrantPop 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                            'blur-in': 'blurIn 1s ease-out forwards',
                            'float': 'float 6s ease-in-out infinite',
                        },
                        keyframes: {
                            vibrantPop: {
                                '0%': { opacity: '0', transform: 'scale(0.9) translateY(20px)' },
                                '100%': { opacity: '1', transform: 'scale(1) translateY(0)' },
                            },
                            blurIn: {
                                '0%': { opacity: '0', filter: 'blur(20px)' },
                                '100%': { opacity: '1', filter: 'blur(0)' },
                            },
                            float: {
                                '0%, 100%': { transform: 'translateY(0)' },
                                '50%': { transform: 'translateY(-10px)' },
                            }
                        }
                    }
                }
            }
        </script>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap');

            body {
                font-family: 'Inter', sans-serif;
            }

            h1,
            h2,
            h3,
            .font-display {
                font-family: 'Outfit', sans-serif;
            }

            .premium-mesh {
                background-color: #fcfaf6;
                background-image:
                    radial-gradient(at 0% 0%, rgba(181, 148, 84, 0.05) 0px, transparent 50%),
                    radial-gradient(at 100% 0%, rgba(181, 148, 84, 0.03) 0px, transparent 50%),
                    radial-gradient(at 100% 100%, rgba(181, 148, 84, 0.05) 0px, transparent 50%),
                    radial-gradient(at 0% 100%, rgba(181, 148, 84, 0.03) 0px, transparent 50%);
            }

            .glass-luxury {
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.4);
                box-shadow: 0 8px 32px 0 rgba(181, 148, 84, 0.05);
            }

            .glass-dark-luxury {
                background: rgba(7, 11, 20, 0.85);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.05);
                box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4);
            }

            .bento-card {
                position: relative;
                overflow: hidden;
                transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .bento-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 25px 50px -12px rgba(181, 148, 84, 0.15);
            }

            .gold-glow {
                box-shadow: 0 0 20px rgba(181, 148, 84, 0.2);
            }

            .quill-content h1 {
                font-size: 2rem;
                font-weight: 900;
                margin-bottom: 1.5rem;
                color: #0f172a;
                border-left: 4px solid #b59454;
                padding-left: 1rem;
            }

            .quill-content h2 {
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
                color: #1e293b;
            }

            .quill-content p {
                font-size: 1.125rem;
                line-height: 1.8;
                margin-bottom: 1rem;
                color: #334155;
            }

            .quill-content ul {
                list-style-type: none;
                margin-bottom: 1.5rem;
            }

            .quill-content ul li::before {
                content: "•";
                color: #b59454;
                font-weight: bold;
                display: inline-block;
                width: 1em;
                margin-left: -1em;
            }
        </style>
    </head>

    <body class="premium-mesh text-slate-850 min-h-screen flex flex-col">

        <!-- Navbar -->
        <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/ict/portal/index.php" class="flex-shrink-0 flex items-center gap-3">
                            <div class="w-8 h-8 flex items-center justify-center bg-primary-50 text-primary-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 text-lg tracking-tight">Dallas Premiere</span>
                                <span class="text-xs text-slate-500 block uppercase tracking-wider font-semibold">Service
                                    Portal</span>
                            </div>
                        </a>
                        <div class="hidden md:ml-6 md:flex md:space-x-8">
                            <a href="/ict/portal/index.php"
                                class="inline-flex items-center px-1 pt-1 text-sm font-medium text-slate-900 border-b-2 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'border-primary-500' : 'border-transparent hover:text-slate-700 hover:border-slate-300'; ?>">Dashboard</a>
                            <a href="/ict/portal/knowledge_base.php"
                                class="inline-flex items-center px-1 pt-1 text-sm font-medium text-slate-900 border-b-2 <?php echo basename($_SERVER['PHP_SELF']) == 'knowledge_base.php' ? 'border-primary-500' : 'border-transparent hover:text-slate-700 hover:border-slate-300'; ?>">Knowledge
                                Base</a>
                            <a href="/ict/portal/maintenance_schedule.php"
                                class="inline-flex items-center px-1 pt-1 text-sm font-medium text-slate-900 border-b-2 <?php echo basename($_SERVER['PHP_SELF']) == 'maintenance_schedule.php' ? 'border-primary-500' : 'border-transparent hover:text-slate-700 hover:border-slate-300'; ?>">Maintenance</a>
                            <a href="/ict/portal/verified_solutions.php"
                                class="inline-flex items-center px-1 pt-1 text-sm font-medium text-slate-900 border-b-2 <?php echo basename($_SERVER['PHP_SELF']) == 'verified_solutions.php' ? 'border-primary-500' : 'border-transparent hover:text-slate-700 hover:border-slate-300'; ?>">Verified
                                Solutions</a>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate-500 hidden sm:block">Welcome, <span
                                class="font-semibold text-slate-700"><?= htmlspecialchars($username) ?></span></span>
                        <a href="/ict/logout.php"
                            class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="flex-grow container max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php
}

function renderPortalFooter()
{
    ?>
        </main>

        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-5xl mx-auto px-4 py-6 text-center">
                <p class="text-xs text-slate-400">&copy; <?= date('Y') ?> Dallas Premiere Hotel. IT Support Portal.</p>
            </div>
        </footer>
    </body>

    </html>
    <?php
}
?>