<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// NO requireLogin() here - this is a public portal for hotel staff
// However, we can add a check for a "Staff Access Code" if the user wants later.

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $system_affected = sanitize($_POST['system_affected']);
    $priority = sanitize($_POST['priority']);
    $symptoms = "REPORTED BY STAFF: " . sanitize($_POST['staff_name']) . " (Dept: " . sanitize($_POST['staff_dept']) . ")\n\n" . sanitize($_POST['symptoms']);
    $status = 'open';
    $technician_name = 'Staff Portal';
    $incident_date = date('Y-m-d');

    if (empty($title) || empty($system_affected) || empty($_POST['staff_name'])) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO troubleshooting_logs (title, system_affected, priority, status, symptoms, technician_name, incident_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $system_affected, $priority, $status, $symptoms, $technician_name, $incident_date]);

            $success = "Your request has been submitted. The IT team has been notified.";
        } catch (PDOException $e) {
            $error = "System Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Help Desk - Dallas Premiere Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
        }

        .saas-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .bg-primary-500 {
            background-color: #9d174d;
        }

        /* Using the hotel theme color */
        .text-primary-500 {
            color: #9d174d;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <div class="max-w-xl w-full">
        <div class="text-center mb-10">
            <div
                class="inline-flex items-center justify-center w-20 h-20 bg-primary-500 rounded-3xl shadow-2xl shadow-primary-500/30 mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">IT Help Desk</h1>
            <p class="text-slate-500 mt-2 font-medium uppercase tracking-widest text-[10px]">Staff Support Portal</p>
        </div>

        <?php if ($success): ?>
            <div class="saas-card p-10 text-center animate-bounce">
                <div
                    class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Ticket Submitted!</h2>
                <p class="text-slate-500 mb-8">
                    <?php echo $success; ?>
                </p>
                <a href="helpdesk.php"
                    class="inline-flex items-center px-6 py-3 bg-primary-500 text-white rounded-xl font-bold uppercase tracking-widest text-xs">New
                    Ticket</a>
            </div>
        <?php else: ?>
            <div class="saas-card overflow-hidden">
                <div class="p-6 bg-slate-50 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800">Report an IT Incident</h3>
                    <p class="text-xs text-slate-500">Please describe the problem clearly for the IT team.</p>
                </div>

                <form action="" method="POST" class="p-6 space-y-5">
                    <?php if ($error): ?>
                        <div class="bg-red-50 text-red-600 p-3 rounded-lg text-xs font-bold border border-red-100">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Your
                                Name</label>
                            <input type="text" name="staff_name" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/20 outline-none">
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Department</label>
                            <input type="text" name="staff_dept" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/20 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Issue
                            Summary</label>
                        <input type="text" name="title" placeholder="e.g. WiFi not working in Room 302" required
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/20 outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">System
                                Affected</label>
                            <select name="system_affected"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/20 outline-none bg-white">
                                <option value="WiFi / LAN">WiFi / LAN</option>
                                <option value="PMS / Opera">PMS / Opera</option>
                                <option value="Telephone / PABX">Telephone / PABX</option>
                                <option value="CCTV / Security">CCTV / Security</option>
                                <option value="Printer / Hardware">Printer / Hardware</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Impact
                                Level</label>
                            <select name="priority"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/20 outline-none bg-white">
                                <option value="low">Low - Minor</option>
                                <option value="medium" selected>Medium - Normal</option>
                                <option value="high">High - Critical Issue</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Describe
                            the Problem</label>
                        <textarea name="symptoms" rows="4" required
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/20 outline-none"
                            placeholder="What happened? Any error messages?"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-4 bg-primary-500 hover:bg-primary-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-xl shadow-primary-500/30 transition-all flex items-center justify-center">
                        Submit Support Request
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <div class="p-6 bg-amber-50 rounded-b-xl border-t border-amber-100 flex items-start space-x-3">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-[10px] text-amber-800 font-medium">For emergency IT outages (Main Server, PMS Down),
                        please also call the internal IT extension <strong class="text-amber-900 font-bold">#555</strong>
                        immediately after submitting.</p>
                </div>
            </div>

            <p class="text-center mt-8 text-slate-400 text-[10px] font-bold uppercase tracking-widest">&copy;
                <?php echo date('Y'); ?> Dallas Premiere Hotel IT Management
            </p>
        <?php endif; ?>

    </div>

</body>

</html>