<?php
// Standalone migration script
$host = '127.0.0.1';
$db = 'hotel_ict';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$title = "ICT Department – Duties & Responsibilities";
$category = "Policy";
$author = "IT Manager";
$version = "1.0";

$content = <<<HTML
<h1>ICT Department – Duties & Responsibilities</h1>

<h2>1. Systems Administration (Oracle Systems)</h2>
<p>Responsible for administration, monitoring, and support of all Oracle-based systems including:</p>
<ul>
    <li><strong>Oracle OPERA PMS</strong></li>
    <li><strong>Oracle MICROS POS</strong></li>
    <li><strong>Oracle Hospitality OPERA Cloud</strong></li>
    <li><strong>Oracle SunSystems</strong></li>
    <li><strong>Oracle Materials Control</strong></li>
</ul>

<h3>Key Responsibilities:</h3>
<ul>
    <li>User account creation, modification, and access control</li>
    <li>System configuration and parameter setup</li>
    <li>Monitoring system integrations (POS ↔ PMS ↔ Finance)</li>
    <li>Troubleshooting posting and interface errors</li>
    <li>Backup monitoring and restoration testing</li>
    <li>System updates and patch management</li>
    <li>Monitoring live transactions</li>
    <li>Ensuring data accuracy across departments</li>
</ul>

<hr>

<h2>2. Server Monitoring & Maintenance</h2>
<h3>Daily Tasks:</h3>
<ul>
    <li>Verify server uptime and performance</li>
    <li>Monitor CPU, RAM, and storage usage</li>
    <li>Confirm backup completion</li>
    <li>Review system and event logs</li>
    <li>Check antivirus status and updates</li>
</ul>

<h3>Weekly / Monthly Tasks:</h3>
<ul>
    <li>Test backup restoration</li>
    <li>Apply Windows updates and security patches</li>
    <li>Hardware inspection (RAID, power supply, disks)</li>
    <li>UPS and power system checks</li>
</ul>

<h3>Responsibilities:</h3>
<ul>
    <li>Active Directory management</li>
    <li>File server and shared folder permissions</li>
    <li>Network drive mapping</li>
    <li>Virtual machine monitoring</li>
</ul>

<hr>

<h2>3. Solar Power & Car Charging System Management</h2>
<p>ICT is responsible for monitoring all renewable and EV charging systems:</p>

<h3>Solar Power:</h3>
<ul>
    <li>Monitor inverter performance and load usage</li>
    <li>Check battery health and voltage levels</li>
    <li>Inspect solar panels and wiring</li>
    <li>Coordinate maintenance with solar vendor</li>
</ul>

<h3>Car Charging Stations:</h3>
<ul>
    <li>Monitor EV charger status and energy usage</li>
    <li>Ensure chargers are operational and accessible</li>
    <li>Report faults or downtime to maintenance team</li>
    <li>Coordinate with electrical/solar systems for power optimization</li>
</ul>

<hr>

<h2>4. Events & Conference Technical Support</h2>
<h3>Projectors & Displays:</h3>
<ul>
    <li>Setup and configuration</li>
    <li>Signal testing (HDMI/VGA)</li>
    <li>Screen calibration</li>
</ul>

<h3>PA Systems:</h3>
<ul>
    <li>Microphone setup (wired/wireless)</li>
    <li>Mixer adjustment</li>
    <li>Speaker positioning</li>
    <li>Audio troubleshooting</li>
</ul>

<h3>Conference Support:</h3>
<ul>
    <li>Video conferencing setup</li>
    <li>Internet bandwidth verification</li>
    <li>Technical standby during events</li>
</ul>

<hr>

<h2>5. End Month & General Stock Taking Support</h2>
<p>ICT provides critical support during monthly closing and stock audits across departments.</p>

<h3>End Month Support:</h3>
<ul>
    <li>Ensure systems are operational before closing</li>
    <li>Verify data synchronization between POS, PMS, and Finance</li>
    <li>Assist Finance team in Oracle SunSystems</li>
    <li>Generate and troubleshoot system reports</li>
    <li>Ensure backups before end-month closure</li>
</ul>

<h3>General Stock Taking Support:</h3>
<ul>
    <li>Provide system access in Oracle Materials Control</li>
    <li>Assist departments with stock sheets and scanners</li>
    <li>Resolve system errors during stock counts</li>
    <li>Help investigate stock variances</li>
    <li>Ensure final stock reports are posted to Finance</li>
</ul>

<h3>Departments Supported:</h3>
<ul>
    <li>Stores</li>
    <li>Food & Beverage</li>
    <li>Housekeeping</li>
    <li>Maintenance</li>
    <li>Front Office</li>
    <li>Finance</li>
</ul>

<hr>

<h2>6. General IT Support Roles</h2>
<h3>User Support:</h3>
<ul>
    <li>Password resets</li>
    <li>Printer troubleshooting</li>
    <li>Email setup and configuration</li>
    <li>Network troubleshooting</li>
</ul>

<h3>Network Management:</h3>
<ul>
    <li>Switch monitoring</li>
    <li>LAN cable testing and crimping</li>
    <li>Access point configuration</li>
    <li>IP management</li>
</ul>

<h3>Hardware Support:</h3>
<ul>
    <li>Desktop/laptop maintenance</li>
    <li>Peripheral setup</li>
    <li>Software installation</li>
</ul>

<h3>Security:</h3>
<ul>
    <li>CCTV support</li>
    <li>Firewall monitoring</li>
    <li>Access control system support</li>
</ul>

<hr>

<h2>7. Documentation & Reporting</h2>
<ul>
    <li>Maintain IT asset register</li>
    <li>Document system configurations</li>
    <li>Record monthly ICT performance reports</li>
    <li>Track incidents and resolutions</li>
    <li>Maintain backup logs and disaster recovery documentation</li>
</ul>

<hr>

<h2>8. Preventive Maintenance Strategy</h2>
<ul>
    <li>Scheduled system audits</li>
    <li>Disaster recovery planning</li>
    <li>Data backup verification</li>
    <li>Network performance review</li>
    <li>IT risk assessment</li>
</ul>
HTML;

try {
    // Check if it already exists
    $check = $pdo->prepare("SELECT id FROM sop_documents WHERE title = ?");
    $check->execute([$title]);
    if ($check->fetch()) {
        echo "Document already exists. Skipping insertion.\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO sop_documents (title, category, content, version, author) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $category, $content, $version, $author]);
        header("Location: modules/department/index.php?success=Documentation Published");
        exit();
    }
} catch (PDOException $e) {
    die("Error inserting document: " . $e->getMessage());
}
?>