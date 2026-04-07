<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'staff') {
                redirect('/ict/portal/index.php');
            } else {
                redirect('/ict/index.php');
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dallas Premiere Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-slate-950 flex items-center justify-center h-screen overflow-hidden">

    <!-- Deep Gold Background Gradients -->
    <div
        class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(181,148,84,0.1),transparent),radial-gradient(circle_at_bottom_left,rgba(163,131,74,0.1),transparent)]">
    </div>
    <div class="absolute -top-48 -left-48 w-full h-full bg-primary-900/10 rounded-full blur-[120px] animate-pulse">
    </div>
    <div class="absolute -bottom-48 -right-48 w-full h-full bg-primary-600/5 rounded-full blur-[120px] animate-pulse"
        style="animation-delay: 3s"></div>

    <div
        class="relative bg-white p-10 rounded-2xl shadow-2xl shadow-primary-950/20 w-full max-w-md border border-primary-100/50 backdrop-blur-sm">
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center justify-center w-20 h-20 mb-6 bg-primary-50 rounded-2xl border border-primary-100/50 shadow-inner">
                <svg class="w-10 h-10 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                    </path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Dallas Premiere</h1>
            <p class="text-primary-600 mt-2 text-sm font-bold uppercase tracking-widest italic">ICT Management System
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-200 px-4 py-3 rounded-lg relative mb-6 flex items-center text-sm"
                role="alert">
                <svg class="w-5 h-5 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
                <span class="block sm:inline">
                    <?php echo $error; ?>
                </span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-6">
            <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2" for="username">
                    Username
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input
                        class="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-3 placeholder-slate-400 transition-all shadow-sm"
                        id="username" name="username" type="text" placeholder="Enter your username" required>
                </div>
            </div>
            <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2" for="password">
                    Password
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                            </path>
                        </svg>
                    </div>
                    <input
                        class="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-3 placeholder-slate-400 transition-all shadow-sm"
                        id="password" name="password" type="password" placeholder="••••••••" required>
                </div>
            </div>

            <div class="pt-2">
                <button
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-bold text-white bg-primary-500 hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-primary-500/20 hover:shadow-primary-500/40 transform hover:-translate-y-0.5"
                    type="submit">
                    Sign In
                </button>
            </div>
        </form>
    </div>

    <div class="absolute bottom-6 text-center text-xs text-slate-400">
        &copy; <?php echo date('Y'); ?> Dallas Premiere Hotel. Secured System.
    </div>

</body>

</html>