<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

date_default_timezone_set('Africa/Blantyre');

header('Content-Type: application/json');

function jq_error($message, $meta = []) {
    http_response_code(200);
    echo json_encode(array_merge(['success' => false, 'message' => $message], $meta));
    exit;
}

// Export: direct PDF download using FPDF
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'report_pdf_download') {
    $start = $_GET['start_date'] ?? null;
    $end = $_GET['end_date'] ?? null;
    $dept = $_GET['department'] ?? null;
    $conn = getDBConnection();

    if ($start && $end) {
        $dateFilter = sprintf("DATE(qt.created_at) BETWEEN '%s' AND '%s'", $conn->real_escape_string($start), $conn->real_escape_string($end));
    } else if ($start) {
        $dateFilter = sprintf("DATE(qt.created_at) >= '%s'", $conn->real_escape_string($start));
    } else {
        $dateFilter = "DATE(qt.created_at)=CURDATE()";
    }
    $deptClause = $dept ? " AND d.code='".$conn->real_escape_string($dept)."'" : '';

    $served = (int)safeQuery($conn, "SELECT COUNT(*) AS served FROM queue_tokens qt WHERE (qt.status IN ('serving','completed')) AND ($dateFilter)")->fetch_assoc()['served'];
    $avgRow = safeQuery($conn, "SELECT AVG(TIMESTAMPDIFF(MINUTE, qt.created_at, qt.called_at)) AS avgm FROM queue_tokens qt WHERE qt.called_at IS NOT NULL AND ($dateFilter)")->fetch_assoc();
    $avgWait = $avgRow && $avgRow['avgm'] !== null ? round((float)$avgRow['avgm'], 1) : 0;

    $rows = [];
    $sqlRows = "SELECT qt.token_number, qt.patient_name, qt.patient_phone, d.name AS department, qt.priority_type, qt.status, qt.created_at, qt.called_at, qt.completed_at
                FROM queue_tokens qt JOIN departments d ON qt.department_id=d.id
                WHERE ($dateFilter)$deptClause ORDER BY qt.created_at DESC LIMIT 2000";
    $res = safeQuery($conn, $sqlRows); while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    $conn->close();

    require_once __DIR__ . '/../lib/fpdf.php';
    // Remove JSON header so FPDF can set PDF headers
    header_remove('Content-Type');

    $pdf = new FPDF('P','mm','A4');
    $pdf->AddPage();
    $left = 10; $top = 12; $pageW = 190; $rowH = 7.5; $footerH = 10;
    $pageNum = 1; $printedHeader = false;
    $range = $start ? ($end ? ($start.' to '.$end) : ($start.' and later')) : 'Today';
    $deptTitle = $dept ? strtoupper($dept) : 'All Departments';

    $headers = ['Token','Patient','Phone','Department','Priority','Status','Created','Called','Completed'];
    // widths must sum to 190 (page content width)
    // [Token, Patient, Phone, Department, Priority, Status, Created, Called, Completed]
    // Wider Patient/Department; totals 190mm
    $widths  = [24,40,22,50,8,10,12,12,12];

    // functions to render header/footer
    $renderHeader = function() use ($pdf,$left,$top,$range,$deptTitle,$served,$avgWait,$headers,$widths,$rowH,$pageW) {
        $pdf->SetXY($left,$top);
        $pdf->SetFont('Arial','',16);
        $pdf->Cell(0,9,'Queue Report',0,1);
        $pdf->SetFont('Arial','',11);
        $pdf->SetXY($left,$pdf->GetY());
        $pdf->Cell(0,6,'Range: '.$range.'    Department: '.$deptTitle,0,1);
        $pdf->Cell(0,6,'Patients served: '.$served.'    Average wait time: '.$avgWait.' min',0,1);
        $pdf->Ln(1.5);
        // header row
        $pdf->SetFont('Arial','',10);
        $y = $pdf->GetY();
        // header background
        $pdf->RectFill($left,$y,$pageW,$rowH+1,0.92);
        $x=$left;
        foreach ($headers as $i=>$h) { $pdf->SetXY($x,$y); $pdf->Cell($widths[$i],$rowH+1,$h,1,0); $x += $widths[$i]; }
        $pdf->Ln($rowH+1);
    };
    $renderFooter = function($n) use ($pdf,$footerH) {
        // footer: date + page number at bottom margin
        $date = date('Y-m-d H:i');
        $pdf->SetXY(10, 297 - $footerH);
        $pdf->SetFont('Arial','',9);
        $pdf->Cell(0,5,'Generated: '.$date.'    Page '.$n,0,1);
    };

    $renderHeader();
    $printedHeader = true;
    // helpers to wrap text to width (approximate, break long chunks, no ellipsis)
    $wrapText = function($text,$w,$fontPt,$maxLines=4){
        $text = (string)$text;
        if ($text === '') return [''];
        $avgCharWmm = ($fontPt/3.0) * 0.3528; // conservative char width budget
        $maxCharsPerLine = max(1, (int)floor(($w-3) / max(0.1,$avgCharWmm)));
        $words = preg_split('/\s+/', $text);
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            // break very long tokens/phones etc.
            $parts = [];
            $len = mb_strlen($word);
            if ($len > $maxCharsPerLine) {
                for ($i=0; $i<$len; $i+=$maxCharsPerLine) { $parts[] = mb_substr($word,$i,$maxCharsPerLine); }
            } else { $parts[] = $word; }
            foreach ($parts as $chunk) {
                $try = $line === '' ? $chunk : ($line.' '.$chunk);
                if (mb_strlen($try) <= $maxCharsPerLine) { $line = $try; }
                else { if ($line!=='') $lines[] = $line; $line = $chunk; if (count($lines) >= $maxLines) break 2; }
            }
        }
        if ($line !== '' && count($lines) < $maxLines) $lines[] = $line;
        return $lines;
    };

    // table rows with alternating background and wrapping
    $fontPt = 8.6; $lineH = 5.2; $i = 0;
    foreach ($rows as $r) {
        // Pre-format timestamps as two lines (date and time)
        $fmt2 = function($v){ if(!$v) return ''; $parts = preg_split('/\s+/', trim($v)); if(count($parts)>=2) return $parts[0]."\n".$parts[1]; return $v; };
        $vals = [
            $r['token_number'] ?? '',
            $r['patient_name'] ?? '',
            $r['patient_phone'] ?? '',
            $r['department'] ?? '',
            $r['priority_type'] ?? '',
            $r['status'] ?? '',
            $fmt2($r['created_at'] ?? ''),
            $fmt2($r['called_at'] ?? ''),
            $fmt2($r['completed_at'] ?? '')
        ];
        // compute wrapped lines per cell and max lines for the row
        $wrapped = [];
        $maxLines = 1;
        // Wrap all columns; allow more lines for date/time
        $perColMax = [2,4,2,6,2,2,3,3,3];
        foreach ($vals as $idx=>$text) {
            if (strpos($text, "\n") !== false) { $wrapped[$idx] = explode("\n", $text); }
            else { $wrapped[$idx] = $wrapText($text,$widths[$idx]-3,$fontPt,$perColMax[$idx]); }
            $maxLines = max($maxLines, count($wrapped[$idx]));
        }
        $rowHeight = $maxLines * $lineH + 7;
        // page break check (reserve footer space)
        if ($pdf->GetY() > (297 - $footerH - $rowHeight - 10)) {
            $renderFooter($pageNum);
            $pdf->AddPage(); $pageNum++; $renderHeader();
        }
        $y = $pdf->GetY();
        if ($i % 2 == 1) { $pdf->RectFill($left,$y,$pageW,$rowHeight,0.975); }
        // draw borders and text
        $x = $left;
        for ($c=0; $c<count($vals); $c++) {
            // border
            $pdf->Rect($x,$y,$widths[$c],$rowHeight);
            // text lines
            $lines = $wrapped[$c];
            for ($li=0; $li<count($lines); $li++) {
                $pdf->SetXY($x+1.5, $y + 1.2 + $li*$lineH);
                $pdf->Cell($widths[$c]-3, $lineH, $lines[$li], 0, 0);
            }
            $x += $widths[$c];
        }
        $pdf->SetXY($left, $y + $rowHeight);
        $i++;
    }
    // final footer
    $renderFooter($pageNum);

    $fname = 'queue_report_'.date('Ymd_His').'.pdf';
    $pdf->Output($fname,'D');
    exit;
}

// Export: printable HTML which the browser can save as PDF (File > Print > Save as PDF)
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'report_pdf') {
    $start = $_GET['start_date'] ?? null;
    $end = $_GET['end_date'] ?? null;
    $dept = $_GET['department'] ?? null;
    $conn = getDBConnection();

    if ($start && $end) {
        $dateFilter = sprintf("DATE(qt.created_at) BETWEEN '%s' AND '%s'", $conn->real_escape_string($start), $conn->real_escape_string($end));
    } else if ($start) {
        $dateFilter = sprintf("DATE(qt.created_at) >= '%s'", $conn->real_escape_string($start));
    } else {
        $dateFilter = "DATE(qt.created_at)=CURDATE()";
    }
    $deptClause = $dept ? " AND d.code='".$conn->real_escape_string($dept)."'" : '';

    $summarySql1 = "SELECT COUNT(*) AS served FROM queue_tokens qt WHERE (qt.status IN ('serving','completed')) AND ($dateFilter)";
    $served = (int)safeQuery($conn, $summarySql1)->fetch_assoc()['served'];
    $summarySql2 = "SELECT AVG(TIMESTAMPDIFF(MINUTE, qt.created_at, qt.called_at)) AS avgm FROM queue_tokens qt WHERE qt.called_at IS NOT NULL AND ($dateFilter)";
    $avgRow = safeQuery($conn, $summarySql2)->fetch_assoc();
    $avgWait = $avgRow && $avgRow['avgm'] !== null ? round((float)$avgRow['avgm'], 1) : 0;

    $byDept = [];
    $sqlByDept = "SELECT d.code, d.name, COUNT(*) AS total FROM queue_tokens qt JOIN departments d ON qt.department_id=d.id WHERE ($dateFilter)$deptClause GROUP BY d.id ORDER BY total DESC";
    $res = safeQuery($conn, $sqlByDept); while ($row = $res->fetch_assoc()) { $byDept[] = $row; }

    $rows = [];
    $sqlRows = "SELECT qt.token_number, qt.patient_name, qt.patient_phone, d.name AS department, qt.priority_type, qt.status, qt.created_at, qt.called_at, qt.completed_at
                FROM queue_tokens qt JOIN departments d ON qt.department_id=d.id
                WHERE ($dateFilter)$deptClause ORDER BY qt.created_at DESC LIMIT 2000";
    $res = safeQuery($conn, $sqlRows); while ($r = $res->fetch_assoc()) { $rows[] = $r; }

    // Override JSON header and render HTML
    header_remove('Content-Type');
    header('Content-Type: text/html; charset=utf-8');
    $title = 'Queue Report';
    $range = $start ? ($end ? ($start.' to '.$end) : ($start.' and later')) : 'Today';
    $deptTitle = $dept ? strtoupper($dept) : 'All Departments';
    echo "<!doctype html><html><head><meta charset='utf-8'><title>{$title}</title>
    <style>
      body{font-family:Arial,Helvetica,sans-serif;color:#111;margin:24px;}
      h1{font-size:20px;margin:0 0 6px;}
      .meta{color:#334155;margin:0 0 16px;}
      table{width:100%;border-collapse:collapse;font-size:12px;}
      th,td{border:1px solid #e5e7eb;padding:6px 8px;text-align:left;}
      th{background:#f8fafc;font-weight:600;}
      .summary{margin:12px 0 16px;}
      .summary span{display:inline-block;margin-right:16px;}
      @media print { .noprint{display:none} }
      .footer{margin-top:16px;color:#475569;font-size:11px;}
    </style>
    </head><body>
      <div class='noprint' style='text-align:right;margin-bottom:8px;'>
        <button onclick='window.print()'>Print / Save as PDF</button>
      </div>
      <h1>{$title}</h1>
      <div class='meta'>Range: {$range} &middot; Department: {$deptTitle}</div>
      <div class='summary'>
        <span><strong>Patients served:</strong> {$served}</span>
        <span><strong>Average wait time:</strong> {$avgWait} min</span>
      </div>
      <div class='summary'>
        <strong>Busiest departments:</strong> ";
    if ($byDept) {
        $parts = array_map(function($r){ return $r['name']." (".$r['code'].") : ".$r['total']; }, $byDept);
        echo implode(', ', $parts);
    } else { echo '-'; }
    echo "</div>
      <table>
        <thead>
          <tr>
            <th>Token</th><th>Patient</th><th>Phone</th><th>Department</th><th>Priority</th><th>Status</th><th>Created</th><th>Called</th><th>Completed</th>
          </tr>
        </thead>
        <tbody>";
    foreach ($rows as $r) {
        echo '<tr>'
          .'<td>'.htmlspecialchars($r['token_number'] ?? '').'</td>'
          .'<td>'.htmlspecialchars($r['patient_name'] ?? '').'</td>'
          .'<td>'.htmlspecialchars($r['patient_phone'] ?? '').'</td>'
          .'<td>'.htmlspecialchars($r['department'] ?? '').'</td>'
          .'<td>'.htmlspecialchars($r['priority_type'] ?? '').'</td>'
          .'<td>'.htmlspecialchars($r['status'] ?? '').'</td>'
          .'<td>'.htmlspecialchars($r['created_at'] ?? '').'</td>'
          .'<td>'.htmlspecialchars($r['called_at'] ?? '').'</td>'
          .'<td>'.htmlspecialchars($r['completed_at'] ?? '').'</td>'
          .'</tr>';
    }
    echo "</tbody></table>
      <div class='footer'>Generated on ".date('Y-m-d H:i')."</div>
    </body></html>";
    $conn->close();
    exit;
}

function safeQuery($conn, $sql) {
    $res = $conn->query($sql);
    if ($res === false) {
        jq_error('DB query failed', ['sql' => $sql, 'db_error' => $conn->error]);
    }
    return $res;
}

function get_department_map($conn) {
    $map = [];
    $res = $conn->query("SELECT id, code, name FROM departments");
    while ($row = $res->fetch_assoc()) { $map[$row['id']] = $row; }
    return $map;
}

function table_exists($conn, $table) {
    $db = $conn->real_escape_string(DB_NAME);
    $tbl = $conn->real_escape_string($table);
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='{$tbl}'";
    $res = $conn->query($sql);
    return $res && $res->num_rows > 0;
}

function stream_csv_to_zip($conn, $tables, $zipName = 'qech_backup.zip') {
    // Override JSON header for a binary download
    header_remove('Content-Type');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=' . $zipName);
    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'qech_zip_');
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        jq_error('Failed to create backup archive');
    }
    foreach ($tables as $t) {
        if (!table_exists($conn, $t)) continue;
        $csv = fopen('php://temp', 'r+');
        $rs = safeQuery($conn, "SELECT * FROM `{$t}`");
        // header
        $firstRow = $rs->fetch_assoc();
        if ($firstRow) {
            fputcsv($csv, array_keys($firstRow));
            fputcsv($csv, array_values($firstRow));
            while ($row = $rs->fetch_assoc()) { fputcsv($csv, $row); }
        } else {
            // no rows, still add header
            $colsRes = safeQuery($conn, "SHOW COLUMNS FROM `{$t}`");
            $cols = [];
            while ($c = $colsRes->fetch_assoc()) { $cols[] = $c['Field']; }
            if ($cols) fputcsv($csv, $cols);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        $zip->addFromString($t . '.csv', $content);
    }
    $zip->close();
    readfile($tmp);
    @unlink($tmp);
    exit;
}

// Lightweight health check to quickly verify routing and DB connectivity
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'health') {
    $ok = true; $db_ok = true; $db_err = null;
    try {
        $c = getDBConnection();
        $r = $c->query('SELECT 1');
        if ($r === false) { $db_ok = false; $db_err = $c->error; }
        $c->close();
    } catch (Throwable $e) {
        $db_ok = false; $db_err = $e->getMessage();
    }
    echo json_encode(['success' => true, 'ok' => $ok, 'db_ok' => $db_ok, 'db_error' => $db_err]);
    exit;
}

if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'stats') {
    $conn = getDBConnection();

    $totalToday = 0; $avgWait = 0; $waitingNow = 0; $busiest = [];

    $res = safeQuery($conn, "SELECT COUNT(*) AS c FROM queue_tokens WHERE DATE(created_at)=CURDATE()");
    $totalToday = (int)$res->fetch_assoc()['c'];

    $res = safeQuery($conn, "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, called_at)) AS avgm FROM queue_tokens WHERE called_at IS NOT NULL AND DATE(created_at)=CURDATE()");
    $avg = $res->fetch_assoc()['avgm']; $avgWait = $avg !== null ? round((float)$avg, 1) : 0;

    $res = safeQuery($conn, "SELECT COUNT(*) AS c FROM queue_tokens WHERE status='waiting'");
    $waitingNow = (int)$res->fetch_assoc()['c'];

    $busiest = [];
    $res = safeQuery($conn, "SELECT d.code, d.name, COUNT(*) AS served FROM queue_tokens qt JOIN departments d ON qt.department_id=d.id WHERE DATE(qt.created_at)=CURDATE() GROUP BY d.id ORDER BY served DESC LIMIT 5");
    while ($row = $res->fetch_assoc()) { $busiest[] = $row; }

    echo json_encode([
        'success' => true,
        'total_patients_today' => $totalToday,
        'avg_wait_time_minutes' => $avgWait,
        'currently_waiting' => $waitingNow,
        'busiest_departments_today' => $busiest
    ]);
    $conn->close();
    exit;
}

if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'report') {
    $period = $_GET['period'] ?? 'daily';
    $start = $_GET['start_date'] ?? null;
    $end = $_GET['end_date'] ?? null;
    $dept = $_GET['department'] ?? null; // code
    $conn = getDBConnection();

    // Build date filter
    if ($start && $end) {
        $dateFilter = sprintf("DATE(qt.created_at) BETWEEN '%s' AND '%s'", $conn->real_escape_string($start), $conn->real_escape_string($end));
    } else if ($start) {
        $dateFilter = sprintf("DATE(qt.created_at) >= '%s'", $conn->real_escape_string($start));
    } else if ($period === 'weekly') {
        $dateFilter = "YEARWEEK(qt.created_at,1)=YEARWEEK(CURDATE(),1)";
    } else {
        $dateFilter = "DATE(qt.created_at)=CURDATE()";
    }

    $deptJoin = '';
    $deptWhere = '';
    if ($dept) {
        $safe = $conn->real_escape_string($dept);
        $deptJoin = " JOIN departments dd ON qt.department_id = dd.id ";
        $deptWhere = " AND dd.code = '".$safe."' ";
    }

    $served = 0; $avgWait = 0; $byDept = []; $peakHours = []; $rows = [];

    $sqlServed = "SELECT COUNT(*) AS c FROM queue_tokens qt $deptJoin WHERE (qt.status IN ('serving','completed')) AND ($dateFilter) $deptWhere";
    $res = safeQuery($conn, $sqlServed); $served = (int)$res->fetch_assoc()['c'];

    $sqlAvg = "SELECT AVG(TIMESTAMPDIFF(MINUTE, qt.created_at, qt.called_at)) AS avgm FROM queue_tokens qt $deptJoin WHERE qt.called_at IS NOT NULL AND ($dateFilter) $deptWhere";
    $res = safeQuery($conn, $sqlAvg); $avg = $res->fetch_assoc()['avgm']; $avgWait = $avg !== null ? round((float)$avg, 1) : 0;

    $sqlByDept = "SELECT d.code, d.name, COUNT(*) AS total FROM queue_tokens qt JOIN departments d ON qt.department_id=d.id WHERE ($dateFilter)" . ($dept?" AND d.code='".$conn->real_escape_string($dept)."'":"") . " GROUP BY d.id ORDER BY total DESC";
    $res = safeQuery($conn, $sqlByDept); while ($row = $res->fetch_assoc()) { $byDept[] = $row; }

    $sqlPeak = "SELECT DATE_FORMAT(qt.created_at,'%Y-%m-%d %H:00') AS hour, COUNT(*) AS c FROM queue_tokens qt $deptJoin WHERE ($dateFilter) $deptWhere GROUP BY DATE_FORMAT(qt.created_at,'%Y-%m-%d %H') ORDER BY c DESC LIMIT 12";
    $res = safeQuery($conn, $sqlPeak); while ($row = $res->fetch_assoc()) { $peakHours[] = $row; }

    // Detailed rows (for table)
    $sqlRows = "SELECT qt.token_number, qt.patient_name, qt.patient_phone, d.name AS department, qt.priority_type, qt.status, qt.created_at, qt.called_at, qt.completed_at
                FROM queue_tokens qt JOIN departments d ON qt.department_id=d.id
                WHERE ($dateFilter)" . ($dept?" AND d.code='".$conn->real_escape_string($dept)."'":"") . "
                ORDER BY qt.created_at DESC LIMIT 1000";
    $res = safeQuery($conn, $sqlRows); while ($r = $res->fetch_assoc()) { $rows[] = $r; }

    echo json_encode([
        'success' => true,
        'period' => $period,
        'start_date' => $start,
        'end_date' => $end,
        'department' => $dept,
        'patients_served' => $served,
        'avg_wait_time_minutes' => $avgWait,
        'by_department' => $byDept,
        'peak_hours' => $peakHours,
        'rows' => $rows
    ]);
    $conn->close();
    exit;
}

if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'report_csv') {
    $start = $_GET['start_date'] ?? null;
    $end = $_GET['end_date'] ?? null;
    $dept = $_GET['department'] ?? null;
    $conn = getDBConnection();

    if ($start && $end) {
        $dateFilter = sprintf("DATE(qt.created_at) BETWEEN '%s' AND '%s'", $conn->real_escape_string($start), $conn->real_escape_string($end));
    } else if ($start) {
        $dateFilter = sprintf("DATE(qt.created_at) >= '%s'", $conn->real_escape_string($start));
    } else {
        $dateFilter = "DATE(qt.created_at)=CURDATE()";
    }
    $deptClause = $dept ? " AND d.code='".$conn->real_escape_string($dept)."'" : '';

    $sql = "SELECT qt.token_number, qt.patient_name, qt.patient_phone, d.code AS department_code, d.name AS department_name, qt.priority_type, qt.status, qt.created_at, qt.called_at, qt.completed_at
            FROM queue_tokens qt JOIN departments d ON qt.department_id=d.id
            WHERE ($dateFilter) $deptClause ORDER BY qt.created_at DESC";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=queue_report.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['token_number','patient_name','patient_phone','department_code','department_name','priority_type','status','created_at','called_at','completed_at']);
    $res = safeQuery($conn, $sql);
    while ($row = $res->fetch_assoc()) { fputcsv($out, $row); }
    fclose($out);
    $conn->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
