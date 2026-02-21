<?php
header('Content-Type: application/json');

// Load
$load = sys_getloadavg();

// CPU atual
$cpuLine = shell_exec("sar -u 1 1 | tail -n 1");
preg_match('/\s+([0-9.,]+)\s+([0-9.,]+)\s+([0-9.,]+)\s+([0-9.,]+)\s+([0-9.,]+)\s+([0-9.,]+)/', $cpuLine, $cpu);
$cpuUsage = 100 - floatval(str_replace(',', '.', $cpu[6] ?? 0));

// RAM
$mem = shell_exec("free -m");
preg_match('/Mem:\s+(\d+)\s+(\d+)/', $mem, $m);
$ramTotal = $m[1] ?? 0;
$ramUsed = $m[2] ?? 0;

// Temperatura
$tempFile = "/sys/class/thermal/thermal_zone0/temp";
$temp = file_exists($tempFile) ? intval(file_get_contents($tempFile))/1000 : 0;

// Frigate
$docker = shell_exec("docker stats frigate --no-stream --format '{{.CPUPerc}},{{.MemUsage}}'");
$dockerParts = explode(",", $docker);
$frigateCpu = floatval(str_replace('%','',$dockerParts[0] ?? 0));

// IO
$ioRaw = shell_exec("iostat -dx 1 1 | grep -E 'sd|nvme' | head -n 1");
$ioParts = preg_split('/\s+/', trim($ioRaw));
$read = floatval($ioParts[5] ?? 0);
$write = floatval($ioParts[6] ?? 0);

// CPU por core
$coreRaw = shell_exec("sar -P ALL 1 1 | grep -E '^[0-9]' | grep -v all");
$cores = [];

foreach (explode("\n", trim($coreRaw)) as $line) {
    if ($line) {
        $parts = preg_split('/\s+/', $line);
        $core = $parts[1] ?? null;
        $idle = floatval(str_replace(',', '.', $parts[7] ?? 0));
        if (is_numeric($core)) {
            $cores[$core] = 100 - $idle;
        }
    }
}

echo json_encode([
    "load"=>$load[0],
    "cpu"=>$cpuUsage,
    "ram_used"=>$ramUsed,
    "ram_total"=>$ramTotal,
    "temp"=>$temp,
    "frigate_cpu"=>$frigateCpu,
    "disk_read"=>$read,
    "disk_write"=>$write
]);
