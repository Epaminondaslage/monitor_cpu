<?php
header('Content-Type: application/json');

// ======================
// LOAD
// ======================
$load = sys_getloadavg();

// ======================
// CPU TOTAL + POR CORE (via /proc/stat)
// ======================
$stat1 = file('/proc/stat');
usleep(200000);
$stat2 = file('/proc/stat');

$cores = [];
$cpuUsage = 0;

foreach ($stat1 as $i => $line) {

    if (preg_match('/^cpu[0-9]*/', $line)) {

        $a = preg_split('/\s+/', trim($stat1[$i]));
        $b = preg_split('/\s+/', trim($stat2[$i]));

        $idle1 = $a[4];
        $idle2 = $b[4];

        $total1 = array_sum(array_slice($a,1));
        $total2 = array_sum(array_slice($b,1));

        $totalDiff = $total2 - $total1;
        $idleDiff  = $idle2 - $idle1;

        if ($totalDiff > 0) {
            $usage = 100 * ($totalDiff - $idleDiff) / $totalDiff;
            $usage = round($usage,2);

            if ($a[0] === 'cpu') {
                $cpuUsage = $usage; // total
            } else {
                $core = substr($a[0],3);
                $cores[$core] = $usage;
            }
        }
    }
}

// ======================
// RAM
// ======================
$mem = shell_exec("free -m");
preg_match('/Mem:\s+(\d+)\s+(\d+)/',$mem,$m);
$ramTotal = $m[1] ?? 0;
$ramUsed  = $m[2] ?? 0;

// ======================
// Temperatura
// ======================
$tempFile="/sys/class/thermal/thermal_zone0/temp";
$temp = file_exists($tempFile)
    ? intval(file_get_contents($tempFile))/1000
    : 0;

// ======================
// Docker Frigate
// ======================
$docker = shell_exec("docker stats frigate --no-stream --format '{{.CPUPerc}}'");
$frigateCpu = floatval(str_replace('%','',$docker));

// ======================
// IO
// ======================
$io = shell_exec("iostat -dx 1 1 | grep -E 'sd|nvme' | head -n 1");
$ioParts = preg_split('/\s+/',trim($io));
$read  = floatval($ioParts[5] ?? 0);
$write = floatval($ioParts[6] ?? 0);

// ======================
// OUTPUT
// ======================
echo json_encode([
    "load"=>$load[0],
    "cpu"=>$cpuUsage,
    "cores"=>$cores,
    "ram_used"=>$ramUsed,
    "ram_total"=>$ramTotal,
    "temp"=>$temp,
    "frigate_cpu"=>$frigateCpu,
    "disk_read"=>$read,
    "disk_write"=>$write
]);