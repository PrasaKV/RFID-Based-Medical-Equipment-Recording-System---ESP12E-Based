<?php
// ============================================================================
// Automatic Database Seeder for RFID Medical Equipment System (MedTrack)
// Can be run via CLI: php System/setup/seed.php [--fresh]
// Can be run via Web: http://localhost/setup/seed.php
// Can be included as a function: seedDatabase($pdo, $fresh = false)
// ============================================================================

$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'rfid-system';

/**
 * Main seeding function.
 *
 * @param PDO  $pdo   Connected PDO instance
 * @param bool $fresh If true, clears existing data before re-seeding
 * @return array Summary of seeded rows
 */
function seedDatabase(PDO $pdo, bool $fresh = false): array {
    $summary = [];

    // 1. Ensure auto-migrations have run
    $migrateFile = __DIR__ . '/migrate.php';
    if (file_exists($migrateFile)) {
        require_once $migrateFile;
        runMigrations($pdo);
    }

    // 2. Relative date helpers (keeps data fresh whenever seeded)
    $today      = new DateTime();
    $daysAgo    = fn(int $d) => (clone $today)->modify("-{$d} days")->format('Y-m-d');
    $daysAhead  = fn(int $d) => (clone $today)->modify("+{$d} days")->format('Y-m-d');
    $minutesAgo = fn(int $m) => (clone $today)->modify("-{$m} minutes")->format('Y-m-d H:i:s');
    $hoursAgo   = fn(int $h) => (clone $today)->modify("-{$h} hours")->format('Y-m-d H:i:s');

    // 3. Optional Fresh Wipe
    if ($fresh) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $tables = [
            'part_replacements', 'repair_logs', 'issues_tickets',
            'maintenance_logs', 'location_logs', 'equipment',
            'categories', 'departments', 'users', 'roles'
        ];
        foreach ($tables as $t) {
            $pdo->exec("TRUNCATE TABLE `$t`;");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    // ── 4. Seed Roles ──────────────────────────────────────────────────────────
    $roles = [
        ['admin',      'Full system access, administration, and record management'],
        ['engineer',   'Biomedical Engineer - Equipment maintenance, diagnostics, and repairs'],
        ['technician', 'Field Technician - Equipment inspection, check-in, and scanning'],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO roles (role_name, description) VALUES (?,?)");
    $rc = 0;
    foreach ($roles as $r) {
        $stmt->execute($r);
        $rc += $stmt->rowCount();
    }
    $summary['roles'] = $rc;

    // ── 5. Seed Users ──────────────────────────────────────────────────────────
    $users = [
        ['admin',       'admin@hospital.local',      'admin123', 'admin'],
        ['biomedeng',   'engineer@hospital.local',   'eng123',   'engineer'],
        ['tech_dave',   'dave.tech@hospital.local',  'tech123',  'technician'],
        ['nurse_sarah', 'sarah.icu@hospital.local',  'nurse123', 'technician'],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, role) VALUES (?,?,?,?)");
    $uc = 0;
    foreach ($users as $u) {
        $stmt->execute($u);
        $uc += $stmt->rowCount();
    }
    $summary['users'] = $uc;

    // Map usernames to user_id
    $userMap = [];
    $rows = $pdo->query("SELECT user_id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $rw) {
        $userMap[$rw['username']] = (int)$rw['user_id'];
    }

    // ── 6. Seed Departments ────────────────────────────────────────────────────
    $departments = [
        'ICU', 'Emergency', 'Operating Theatre', 'Ward A', 'Ward B',
        'Radiology', 'Lab', 'Dialysis', 'Surgery', 'Storage'
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO departments (name) VALUES (?)");
    $dc = 0;
    foreach ($departments as $dept) {
        $stmt->execute([$dept]);
        $dc += $stmt->rowCount();
    }
    $summary['departments'] = $dc;

    // ── 7. Seed Categories ─────────────────────────────────────────────────────
    $categories = [
        'Life Support', 'Monitoring', 'Imaging', 'Laboratory',
        'Surgical', 'Dialysis', 'Therapeutic', 'Other'
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
    $cc = 0;
    foreach ($categories as $cat) {
        $stmt->execute([$cat]);
        $cc += $stmt->rowCount();
    }
    $summary['categories'] = $cc;

    // ── 8. Seed Equipment (Master Registry) ────────────────────────────────────
    $equipment = [
        [
            'rfid_tag_id'           => 'TAG-001',
            'name'                  => 'Ventilator Pro X200',
            'serial_number'         => 'VNT-2023-001',
            'model'                 => 'Pro X200 Intensive',
            'description'           => 'Microprocessor-controlled ICU critical care ventilator with invasive and non-invasive ventilation modes.',
            'category'              => 'Life Support',
            'status'                => 'OPERATIONAL',
            'current_location'      => 'ICU',
            'pm_interval_days'      => 90,
            'last_maintenance_date' => $daysAgo(45),
            'next_pm_date'          => $daysAhead(45),
            'procurement_date'      => $daysAgo(700),
            'notes'                 => 'Equipped with dual HEPA inspiratory filters.'
        ],
        [
            'rfid_tag_id'           => 'TAG-002',
            'name'                  => 'ECG Monitor Elite 12-Lead',
            'serial_number'         => 'ECG-2022-045',
            'model'                 => 'Elite 12-Lead Multi',
            'description'           => 'Portable diagnostic 12-lead electrocardiograph with Glasgow algorithm interpretation.',
            'category'              => 'Monitoring',
            'status'                => 'PM_DUE',
            'current_location'      => 'Emergency',
            'pm_interval_days'      => 180,
            'last_maintenance_date' => $daysAgo(176),
            'next_pm_date'          => $daysAhead(4), // Within 7 days -> triggers PM_DUE
            'procurement_date'      => $daysAgo(900),
            'notes'                 => 'Lead cable checked; scheduled for semi-annual calibration.'
        ],
        [
            'rfid_tag_id'           => 'TAG-003',
            'name'                  => 'Mobile Digital X-Ray Unit',
            'serial_number'         => 'XRY-2021-012',
            'model'                 => 'DR-800 Mobile',
            'description'           => 'Motorized mobile radiographic imaging unit with wireless flat-panel detector.',
            'category'              => 'Imaging',
            'status'                => 'OPERATIONAL',
            'current_location'      => 'Radiology',
            'pm_interval_days'      => 365,
            'last_maintenance_date' => $daysAgo(120),
            'next_pm_date'          => $daysAhead(245),
            'procurement_date'      => $daysAgo(1200),
            'notes'                 => 'Radiation safety inspection passed with zero leakage.'
        ],
        [
            'rfid_tag_id'           => 'TAG-004',
            'name'                  => 'Smart Infusion Pump V3',
            'serial_number'         => 'INF-2023-089',
            'model'                 => 'SmartPump V3 Dose',
            'description'           => 'Volumetric infusion pump with integrated Dose Error Reduction System (DERS).',
            'category'              => 'Life Support',
            'status'                => 'IN_REPAIR',
            'current_location'      => 'Ward A',
            'pm_interval_days'      => 90,
            'last_maintenance_date' => $daysAgo(110),
            'next_pm_date'          => $daysAhead(30),
            'procurement_date'      => $daysAgo(500),
            'notes'                 => 'Intake logged for occlusion alarm calibration and sticky keypad.'
        ],
        [
            'rfid_tag_id'           => 'TAG-005',
            'name'                  => 'Pulse Oximeter OxyPro 500',
            'serial_number'         => 'POX-2024-003',
            'model'                 => 'OxyPro 500 Bedside',
            'description'           => 'Continuous bedside SpO2 and pulse rate monitor with plethysmograph waveform display.',
            'category'              => 'Monitoring',
            'status'                => 'OPERATIONAL',
            'current_location'      => 'Emergency',
            'pm_interval_days'      => 180,
            'last_maintenance_date' => $daysAgo(30),
            'next_pm_date'          => $daysAhead(150),
            'procurement_date'      => $daysAgo(300),
            'notes'                 => 'Finger probe replaced last month.'
        ],
        [
            'rfid_tag_id'           => 'TAG-006',
            'name'                  => 'Ultrasound Scanner SonoVision',
            'serial_number'         => 'ULT-2020-007',
            'model'                 => 'SonoVision 4D Matrix',
            'description'           => 'Multi-discipline diagnostic ultrasound machine with cardiac and vascular transducers.',
            'category'              => 'Imaging',
            'status'                => 'NEEDS_ATTENTION',
            'current_location'      => 'Radiology',
            'pm_interval_days'      => 365,
            'last_maintenance_date' => $daysAgo(350),
            'next_pm_date'          => $daysAhead(15),
            'procurement_date'      => $daysAgo(1400),
            'notes'                 => 'Intermittent connection on probe port 2; scheduled for connector pin cleaning.'
        ],
        [
            'rfid_tag_id'           => 'TAG-007',
            'name'                  => 'Defibrillator HeartStart AED+',
            'serial_number'         => 'DEF-2022-034',
            'model'                 => 'HeartStart AED Plus',
            'description'           => 'Biphasic manual and automated external defibrillator with CPR real-time feedback sensor.',
            'category'              => 'Life Support',
            'status'                => 'OPERATIONAL',
            'current_location'      => 'Operating Theatre',
            'pm_interval_days'      => 90,
            'last_maintenance_date' => $daysAgo(20),
            'next_pm_date'          => $daysAhead(70),
            'procurement_date'      => $daysAgo(850),
            'notes'                 => 'Battery capacity tested at 98% charge health.'
        ],
        [
            'rfid_tag_id'           => 'TAG-008',
            'name'                  => 'Automated Blood Gas Analyzer BA-200',
            'serial_number'         => 'BLD-2019-011',
            'model'                 => 'StatProfile pHOx Ultra',
            'description'           => 'Benchtop blood gas, electrolyte, and metabolite analyzer with automated quality control.',
            'category'              => 'Laboratory',
            'status'                => 'OPERATIONAL',
            'current_location'      => 'Lab',
            'pm_interval_days'      => 180,
            'last_maintenance_date' => $daysAgo(40),
            'next_pm_date'          => $daysAhead(140),
            'procurement_date'      => $daysAgo(1600),
            'notes'                 => 'Reagents and membrane calibration sensors verified.'
        ],
        [
            'rfid_tag_id'           => 'TAG-009',
            'name'                  => 'Hemodialysis System Dialog+',
            'serial_number'         => 'HMO-2023-055',
            'model'                 => 'Dialog+ Evolution',
            'description'           => 'High-flux dialysis machine with integrated bicarbonate mixing and heparin pump.',
            'category'              => 'Dialysis',
            'status'                => 'OPERATIONAL',
            'current_location'      => 'Dialysis',
            'pm_interval_days'      => 90,
            'last_maintenance_date' => $daysAgo(50),
            'next_pm_date'          => $daysAhead(40),
            'procurement_date'      => $daysAgo(600),
            'notes'                 => 'Disinfection and heating cycle validated.'
        ],
        [
            'rfid_tag_id'           => 'TAG-010',
            'name'                  => 'Electrosurgical Generator ForceTriad',
            'serial_number'         => 'ESU-2022-019',
            'model'                 => 'ForceTriad Energy Platform',
            'description'           => 'Surgical diathermy electrosurgical unit with monopolar, bipolar, and vessel sealing functions.',
            'category'              => 'Surgical',
            'status'                => 'OPERATIONAL',
            'current_location'      => 'Operating Theatre',
            'pm_interval_days'      => 180,
            'last_maintenance_date' => $daysAgo(75),
            'next_pm_date'          => $daysAhead(105),
            'procurement_date'      => $daysAgo(920),
            'notes'                 => 'Footswitch connectors and grounding pad return monitor tested.'
        ],
        [
            'rfid_tag_id'           => 'TAG-011',
            'name'                  => 'Syringe Infusion Driver SP-900',
            'serial_number'         => 'SYR-2024-102',
            'model'                 => 'Perfusor Space Drive',
            'description'           => 'Precision syringe pump for neonatal and intensive care intravenous micro-dosing.',
            'category'              => 'Life Support',
            'status'                => 'OPERATIONAL',
            'current_location'      => 'ICU',
            'pm_interval_days'      => 90,
            'last_maintenance_date' => $daysAgo(10),
            'next_pm_date'          => $daysAhead(80),
            'procurement_date'      => $daysAgo(180),
            'notes'                 => 'Calibrated with standard 50ml BD syringes.'
        ],
        [
            'rfid_tag_id'           => 'TAG-012',
            'name'                  => 'Benchtop Laboratory Centrifuge',
            'serial_number'         => 'CEN-2018-004',
            'model'                 => 'CentriSpin 24 Rotor',
            'description'           => 'Refrigerated laboratory centrifuge, retired from clinical service and awaiting recycling.',
            'category'              => 'Laboratory',
            'status'                => 'DECOMMISSIONED',
            'current_location'      => 'Storage',
            'pm_interval_days'      => 180,
            'last_maintenance_date' => $daysAgo(400),
            'next_pm_date'          => null,
            'procurement_date'      => $daysAgo(2200),
            'notes'                 => 'Decommissioned due to bearing failure. Parts salvaged for training.'
        ],
    ];

    $stmt = $pdo->prepare("INSERT INTO equipment 
        (rfid_tag_id, name, serial_number, model, description, category, status, current_location, 
         pm_interval_days, last_maintenance_date, next_pm_date, procurement_date, notes)
        VALUES (:rfid_tag_id, :name, :serial_number, :model, :description, :category, :status, :current_location,
         :pm_interval_days, :last_maintenance_date, :next_pm_date, :procurement_date, :notes)
        ON DUPLICATE KEY UPDATE 
         name=VALUES(name), model=VALUES(model), description=VALUES(description), category=VALUES(category),
         status=VALUES(status), current_location=VALUES(current_location), pm_interval_days=VALUES(pm_interval_days),
         last_maintenance_date=VALUES(last_maintenance_date), next_pm_date=VALUES(next_pm_date)");

    $eqCount = 0;
    foreach ($equipment as $eq) {
        $stmt->execute($eq);
        $eqCount++;
    }
    $summary['equipment'] = $eqCount;

    // Map RFID tags to equipment_id
    $eqMap = [];
    $rows = $pdo->query("SELECT equipment_id, rfid_tag_id, name, serial_number, current_location FROM equipment")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $rw) {
        if ($rw['rfid_tag_id']) {
            $eqMap[$rw['rfid_tag_id']] = $rw;
        }
    }

    // ── 9. Seed Location Movement Logs ─────────────────────────────────────────
    $locationLogs = [
        ['TAG-001', 'READER-STORAGE-01', 'Storage',          $daysAgo(15) . ' 08:30:00'],
        ['TAG-001', 'READER-ICU-01',     'ICU',              $hoursAgo(3)],
        ['TAG-002', 'READER-WARDB-01',   'Ward B',           $daysAgo(5) . ' 11:15:00'],
        ['TAG-002', 'READER-ER-01',      'Emergency',        $hoursAgo(5)],
        ['TAG-003', 'READER-RAD-01',     'Radiology',        $daysAgo(2) . ' 09:40:00'],
        ['TAG-004', 'READER-WARDA-01',   'Ward A',           $daysAgo(1) . ' 14:00:00'],
        ['TAG-005', 'READER-ER-01',      'Emergency',        $hoursAgo(8)],
        ['TAG-007', 'READER-OR-01',      'Operating Theatre', $daysAgo(3) . ' 07:15:00'],
        ['TAG-009', 'READER-DIALYSIS-01', 'Dialysis',        $hoursAgo(12)],
        ['TAG-010', 'READER-OR-01',      'Operating Theatre', $daysAgo(4) . ' 16:30:00'],
        ['TAG-011', 'READER-ICU-01',     'ICU',              $minutesAgo(45)],
    ];

    $stmt = $pdo->prepare("INSERT INTO location_logs (equipment_id, reader_id, location, scanned_at) VALUES (?,?,?,?)");
    $lc = 0;
    foreach ($locationLogs as [$tag, $rId, $loc, $ts]) {
        if (!isset($eqMap[$tag])) continue;
        $stmt->execute([$eqMap[$tag]['equipment_id'], $rId, $loc, $ts]);
        $lc++;
    }
    $summary['location_logs'] = $lc;

    // ── 10. Seed Maintenance Logs ──────────────────────────────────────────────
    $maintenanceLogs = [
        ['TAG-001', 'PM',     $daysAgo(45),  'Carlos Reyes',  'Full calibration, HEPA inspiratory filter replacement, flow sensor test passed.', $daysAhead(45)],
        ['TAG-002', 'PM',     $daysAgo(176), 'Maria Cruz',    '12-lead frequency response checked, noise filter updated, paper drive cleaned.',  $daysAhead(4)],
        ['TAG-003', 'PM',     $daysAgo(120), 'Ana Gonzales',  'Annual tube radiation calibration, collision sensor test, beam collimator aligned.', $daysAhead(245)],
        ['TAG-004', 'REPAIR', $daysAgo(110), 'Carlos Reyes',  'Pressure transducer recalibrated; motor stepping test passed.',                   null],
        ['TAG-005', 'PM',     $daysAgo(30),  'David Tech',    'Optical emitter sensor check, SpO2 simulator test: 98% accuracy.',                $daysAhead(150)],
        ['TAG-007', 'PM',     $daysAgo(20),  'Carlos Reyes',  'Energy delivery verified into 50-ohm dummy load at 200J; paddle safety check.',   $daysAhead(70)],
        ['TAG-008', 'PM',     $daysAgo(40),  'Maria Cruz',    'Sample chamber de-proteinized, peristaltic tubing flushed, zero gas calibration.', $daysAhead(140)],
        ['TAG-009', 'PM',     $daysAgo(50),  'Carlos Reyes',  'Ultrafiltration flow meter calibration, blood leak detector sensitivity verified.', $daysAhead(40)],
        ['TAG-010', 'PM',     $daysAgo(75),  'Ana Gonzales',  'High-frequency output power measured across all coagulation and cutting modes.',   $daysAhead(105)],
        ['TAG-011', 'PM',     $daysAgo(10),  'David Tech',    'Flow rate accuracy measured with 50ml syringe gravimetric test.',                 $daysAhead(80)],
    ];

    $stmt = $pdo->prepare("INSERT INTO maintenance_logs (equipment_id, type, service_date, technician, remarks, next_pm_date) VALUES (?,?,?,?,?,?)");
    $mc = 0;
    foreach ($maintenanceLogs as [$tag, $type, $date, $tech, $rem, $npm]) {
        if (!isset($eqMap[$tag])) continue;
        $stmt->execute([$eqMap[$tag]['equipment_id'], $type, $date, $tech, $rem, $npm]);
        $mc++;
    }
    $summary['maintenance_logs'] = $mc;

    // ── 11. Seed Issue Tickets ─────────────────────────────────────────────────
    $issues = [
        [
            'tag'         => 'TAG-006',
            'title'       => 'Ultrasound Probe Port 2 Loose Connection',
            'reporter'    => 'nurse_sarah',
            'rep_name'    => 'Nurse Sarah Jenkins',
            'desc'        => 'Image flickers and loses vascular Doppler signal when the probe cable is angled past 45 degrees.',
            'status'      => 'OPEN',
            'notes'       => null,
            'method'      => null,
        ],
        [
            'tag'         => 'TAG-004',
            'title'       => 'Infusion Pump Occlusion Alarm False Positives',
            'reporter'    => 'nurse_sarah',
            'rep_name'    => 'Nurse Sarah Jenkins',
            'desc'        => 'Alarm sound triggers during normal saline 100ml/hr delivery without kinked tubing. Keypad digit 4 is also sluggish.',
            'status'      => 'IN_PROGRESS',
            'notes'       => 'Bench tested with saline bag. Pressure sensor baseline voltage drifting under load.',
            'method'      => 'Replaced pump head assembly and cleaned membrane switch pads.',
        ],
        [
            'tag'         => 'TAG-007',
            'title'       => 'Defibrillator Battery Indicator Blinking Amber',
            'reporter'    => 'biomedeng',
            'rep_name'    => 'Eng. Carlos Reyes',
            'desc'        => 'Self-test reported degraded backup lithium pack during weekly ward inspection.',
            'status'      => 'RESOLVED',
            'notes'       => 'Replaced internal Li-ion secondary battery pack with new OEM unit.',
            'method'      => 'Full discharge/charge cycle completed and self-test passed.',
        ],
    ];

    $stmt = $pdo->prepare("INSERT INTO issues_tickets 
        (equipment_id, title, reported_by, reporter_name, issue_description, status, resolution_notes, repair_method)
        VALUES (?,?,?,?,?,?,?,?)");
    $ic = 0;
    $ticketIds = [];
    foreach ($issues as $iss) {
        if (!isset($eqMap[$iss['tag']])) continue;
        $repId = $userMap[$iss['reporter']] ?? null;
        $stmt->execute([
            $eqMap[$iss['tag']]['equipment_id'],
            $iss['title'],
            $repId,
            $iss['rep_name'],
            $iss['desc'],
            $iss['status'],
            $iss['notes'],
            $iss['method']
        ]);
        $ticketIds[$iss['tag']] = (int)$pdo->lastInsertId();
        $ic++;
    }
    $summary['issues_tickets'] = $ic;

    // ── 12. Seed Repair Logs (Intake & Returns) ─────────────────────────────────
    $repairs = [
        [
            'tag'             => 'TAG-004',
            'machine_name'    => 'Smart Infusion Pump V3',
            'serial_number'   => 'INF-2023-089',
            'department'      => 'Ward A',
            'brought_by'      => 'Nurse Sarah Jenkins',
            'intake_date'     => $daysAgo(3),
            'machine_problem' => 'Occlusion alarm fault during delivery and sticky numeric keypad.',
            'status'          => 'IN_REPAIR',
            'return_date'     => null,
            'received_by'     => null,
            'del_dept'        => null,
            'del_rem'         => null,
        ],
        [
            'tag'             => 'TAG-002',
            'machine_name'    => 'ECG Monitor Elite 12-Lead',
            'serial_number'   => 'ECG-2022-045',
            'department'      => 'Emergency',
            'brought_by'      => 'Dr. Robert Chen',
            'intake_date'     => $daysAgo(14),
            'machine_problem' => 'Display flickering and lead V2 connector intermittent contact.',
            'status'          => 'RETURNED',
            'return_date'     => $daysAgo(11),
            'received_by'     => 'Tech David Reynolds',
            'del_dept'        => 'Emergency',
            'del_rem'         => 'Replaced 12-lead display panel and lead connector block. Calibration report signed.',
        ],
        [
            'tag'             => 'TAG-006',
            'machine_name'    => 'Ultrasound Scanner SonoVision',
            'serial_number'   => 'ULT-2020-007',
            'department'      => 'Radiology',
            'brought_by'      => 'Dr. Emily Vance',
            'intake_date'     => $daysAgo(1),
            'machine_problem' => 'Probe port 2 pin damage causing image dropouts during abdominal scans.',
            'status'          => 'IN_REPAIR',
            'return_date'     => null,
            'received_by'     => null,
            'del_dept'        => null,
            'del_rem'         => null,
        ],
    ];

    $stmt = $pdo->prepare("INSERT INTO repair_logs 
        (equipment_id, machine_name, serial_number, department, brought_by, intake_date, 
         machine_problem, status, return_date, received_by, delivery_department, delivery_remarks)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $rpc = 0;
    $repairIds = [];
    foreach ($repairs as $rep) {
        $eqId = isset($eqMap[$rep['tag']]) ? $eqMap[$rep['tag']]['equipment_id'] : null;
        $stmt->execute([
            $eqId,
            $rep['machine_name'],
            $rep['serial_number'],
            $rep['department'],
            $rep['brought_by'],
            $rep['intake_date'],
            $rep['machine_problem'],
            $rep['status'],
            $rep['return_date'],
            $rep['received_by'],
            $rep['del_dept'],
            $rep['del_rem']
        ]);
        $repairIds[$rep['tag']] = (int)$pdo->lastInsertId();
        $rpc++;
    }
    $summary['repair_logs'] = $rpc;

    // ── 13. Seed Replaced Parts Log ────────────────────────────────────────────
    $parts = [
        [
            'tag'          => 'TAG-004',
            'repair_tag'   => 'TAG-004',
            'ticket_tag'   => 'TAG-004',
            'department'   => 'Ward A',
            'part_serial'  => 'PRT-PUMP-HD88',
            'part_name'    => 'Volumetric Pump Head Assembly',
            'desc'         => 'Complete motor and peristaltic rotor assembly with pressure strain gauge.',
            'date'         => $daysAgo(2),
            'notes'        => 'Fitted OEM pump head. Occlusion pressure calibrated to 10.5 PSI baseline.',
        ],
        [
            'tag'          => 'TAG-002',
            'repair_tag'   => 'TAG-002',
            'ticket_tag'   => null,
            'department'   => 'Emergency',
            'part_serial'  => 'PRT-DSP-912A',
            'part_name'    => '10.4-inch High-Brightness Color TFT Panel',
            'desc'         => 'Replacement LCD display panel and LED backlight inverter module.',
            'date'         => $daysAgo(12),
            'notes'        => 'Eliminated screen flicker; 100-hour burn-in completed successfully.',
        ],
        [
            'tag'          => 'TAG-007',
            'repair_tag'   => null,
            'ticket_tag'   => 'TAG-007',
            'department'   => 'Operating Theatre',
            'part_serial'  => 'PRT-BAT-2200L',
            'part_name'    => '14.8V 4400mAh Li-ion Medical Battery Pack',
            'desc'         => 'Rechargeable internal defibrillator power module with smart fuel gauge.',
            'date'         => $daysAgo(18),
            'notes'        => 'Full discharge/charge cycle completed. Capacity verified at 99.4%.',
        ],
    ];

    $stmt = $pdo->prepare("INSERT INTO part_replacements 
        (repair_id, ticket_id, equipment_id, department, part_serial_number, part_name, description, replacement_date, notes)
        VALUES (?,?,?,?,?,?,?,?,?)");
    $prc = 0;
    foreach ($parts as $p) {
        $eqId  = isset($eqMap[$p['tag']]) ? $eqMap[$p['tag']]['equipment_id'] : null;
        $repId = isset($p['repair_tag'], $repairIds[$p['repair_tag']]) ? $repairIds[$p['repair_tag']] : null;
        $tktId = isset($p['ticket_tag'], $ticketIds[$p['ticket_tag']]) ? $ticketIds[$p['ticket_tag']] : null;
        $stmt->execute([
            $repId,
            $tktId,
            $eqId,
            $p['department'],
            $p['part_serial'],
            $p['part_name'],
            $p['desc'],
            $p['date'],
            $p['notes']
        ]);
        $prc++;
    }
    $summary['part_replacements'] = $prc;

    return $summary;
}

// ============================================================================
// Execution Handler: CLI or Web Browser
// ============================================================================

$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    // ── CLI Mode ───────────────────────────────────────────────────────────
    $fresh = in_array('--fresh', $argv ?? []);
    echo "=== RFID Medical Equipment System — Database Seeder ===\n";
    echo "Connecting to MySQL ($user@$host)...\n";

    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$dbName`;");

        // Run setup.sql if tables do not exist
        $tablesExist = $pdo->query("SHOW TABLES LIKE 'equipment'")->rowCount() > 0;
        if (!$tablesExist) {
            echo "Tables not found. Running setup.sql schema...\n";
            $sql = file_get_contents(__DIR__ . '/setup.sql');
            $stmts = array_filter(array_map('trim', explode(';', $sql)), fn($s) => $s !== '' && $s !== '--');
            foreach ($stmts as $s) {
                try { $pdo->exec($s); } catch (Throwable $e) {}
            }
        }

        echo "Seeding data" . ($fresh ? " (fresh wipe)" : "") . "...\n";
        $results = seedDatabase($pdo, $fresh);

        echo "\nSeeding completed successfully:\n";
        foreach ($results as $table => $count) {
            echo "  ✓ $table: $count records added/updated\n";
        }
        echo "\nDefault Credentials:\n";
        echo "  Admin:      admin / admin123\n";
        echo "  Engineer:   biomedeng / eng123\n";
        echo "  Technician: tech_dave / tech123\n";
        exit(0);
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// ── Web Browser Mode ────────────────────────────────────────────────────────
$error = '';
$results = null;
$fresh = isset($_POST['fresh']) && $_POST['fresh'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$dbName`;");

        // Run setup.sql if equipment table missing
        $tablesExist = $pdo->query("SHOW TABLES LIKE 'equipment'")->rowCount() > 0;
        if (!$tablesExist) {
            $sql = file_get_contents(__DIR__ . '/setup.sql');
            $stmts = array_filter(array_map('trim', explode(';', $sql)), fn($s) => $s !== '' && $s !== '--');
            foreach ($stmts as $s) {
                try { $pdo->exec($s); } catch (Throwable $e) {}
            }
        }

        $results = seedDatabase($pdo, $fresh);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Database Seeder — MedTrack RFID</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    body { font-family:'Inter',sans-serif; background:#060B18; color:#e2e8f0; }
    .glass { background: rgba(255,255,255,0.03); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
<div class="w-full max-w-3xl">

  <!-- Header -->
  <div class="text-center mb-8">
    <div class="w-16 h-16 rounded-2xl bg-teal-600/20 border border-teal-500/30 flex items-center justify-center mx-auto mb-4">
      <svg class="w-8 h-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/>
      </svg>
    </div>
    <h1 class="text-2xl font-bold text-white">Automatic Database Seeder</h1>
    <p class="text-slate-400 text-sm mt-1">Populate <code class="text-teal-300 font-mono font-semibold">rfid-system</code> with realistic medical equipment, location trails, PM schedules, and repair records.</p>
  </div>

  <?php if ($error): ?>
  <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <?php if ($results !== null): ?>
  <!-- Results Banner -->
  <div class="mb-6 p-5 rounded-2xl glass border border-teal-500/30 bg-teal-500/5">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <span class="w-8 h-8 rounded-full bg-teal-500/20 text-teal-300 flex items-center justify-center font-bold">✓</span>
        <div>
          <h2 class="text-white font-bold">Database Seeded Successfully!</h2>
          <p class="text-xs text-slate-400">All sample records and relationships have been generated.</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a href="/login.php" class="px-4 py-2 rounded-xl text-xs font-semibold bg-teal-600 hover:bg-teal-500 text-white transition-colors">
          Sign In →
        </a>
        <a href="/dashboard.php" class="px-4 py-2 rounded-xl text-xs font-semibold bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10 transition-colors">
          View Dashboard
        </a>
      </div>
    </div>

    <!-- Summary Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
      <?php foreach ($results as $tbl => $cnt): ?>
      <div class="p-3 rounded-xl bg-white/2 border border-white/5">
        <p class="text-slate-500 uppercase tracking-wider text-[10px] font-semibold"><?= htmlspecialchars(str_replace('_', ' ', $tbl)) ?></p>
        <p class="text-lg font-bold text-teal-300 mt-1"><?= (int)$cnt ?> <span class="text-xs font-normal text-slate-500">records</span></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Seeder Action Form -->
  <div class="glass rounded-2xl p-6 border border-white/10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div class="p-4 rounded-xl bg-white/2 border border-white/5">
        <h3 class="text-sm font-semibold text-white mb-1">What this seeds:</h3>
        <ul class="text-xs text-slate-400 space-y-1 mt-2">
          <li>• <strong>Roles & Users:</strong> admin, engineer, technician accounts</li>
          <li>• <strong>Master Data:</strong> 10 hospital departments, 8 categories</li>
          <li>• <strong>Equipment:</strong> 12 realistic machines (Ventilators, ECGs, AEDs)</li>
          <li>• <strong>RFID Tracking:</strong> Location movement trails across readers</li>
          <li>• <strong>Preventive Maintenance:</strong> Next PM dates & servicing logs</li>
          <li>• <strong>Repairs & Parts:</strong> Machine intake, returns, and serial parts</li>
          <li>• <strong>Issues:</strong> Open and in-progress hospital trouble tickets</li>
        </ul>
      </div>

      <div class="p-4 rounded-xl bg-white/2 border border-white/5">
        <h3 class="text-sm font-semibold text-white mb-1">Default Logins:</h3>
        <div class="space-y-2 mt-2 text-xs font-mono">
          <div class="p-2 rounded bg-slate-900/60 border border-white/5">
            <span class="text-teal-400 font-semibold">Admin:</span> admin / admin123
          </div>
          <div class="p-2 rounded bg-slate-900/60 border border-white/5">
            <span class="text-blue-400 font-semibold">Engineer:</span> biomedeng / eng123
          </div>
          <div class="p-2 rounded bg-slate-900/60 border border-white/5">
            <span class="text-amber-400 font-semibold">Technician:</span> tech_dave / tech123
          </div>
        </div>
      </div>
    </div>

    <form method="POST" class="flex flex-wrap items-center gap-3">
      <button type="submit" name="fresh" value="0"
        class="flex-1 min-w-[200px] py-3 px-4 rounded-xl font-semibold bg-teal-600 hover:bg-teal-500 text-white transition-all text-sm flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Seed Database (Safe / Insert Missing)
      </button>

      <button type="submit" name="fresh" value="1"
        onclick="return confirm('Reset all equipment and maintenance data to fresh seed state?');"
        class="py-3 px-4 rounded-xl font-semibold bg-red-600/20 hover:bg-red-600/30 text-red-300 border border-red-500/30 transition-all text-sm flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
        </svg>
        Fresh Re-seed (Wipe & Re-populate)
      </button>
    </form>
  </div>

  <!-- CLI Instruction footer -->
  <p class="text-center text-slate-500 text-xs mt-6 font-mono">
    CLI Command: <span class="text-slate-400">php System/setup/seed.php [--fresh]</span>
  </p>
</div>
</body>
</html>
