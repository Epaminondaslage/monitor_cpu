
<?php

header('Content-Type: application/json');

error_reporting(0);


/* =========================
   LOAD
========================= */

$load = sys_getloadavg();
$loadValue = $load[0] ?? 0;


/* =========================
   CPU TOTAL + CORES
========================= */

$stat1 = @file('/proc/stat');
usleep(200000);
$stat2 = @file('/proc/stat');

$cpuUsage = 0;
$cores = [];

if($stat1 && $stat2){

foreach($stat1 as $i => $line){

if(preg_match('/^cpu[0-9]*/',$line)){

$a = preg_split('/\s+/',trim($stat1[$i]));
$b = preg_split('/\s+/',trim($stat2[$i]));

$idle1 = $a[4] ?? 0;
$idle2 = $b[4] ?? 0;

$total1 = array_sum(array_slice($a,1));
$total2 = array_sum(array_slice($b,1));

$totalDiff = $total2 - $total1;
$idleDiff = $idle2 - $idle1;

if($totalDiff > 0){

$usage = 100 * ($totalDiff - $idleDiff) / $totalDiff;
$usage = round($usage,2);

if($a[0] === 'cpu')
$cpuUsage = $usage;
else
$cores[substr($a[0],3)] = $usage;

}

}

}

}


/* =========================
   RAM
========================= */

$mem = @shell_exec("free -m");

preg_match('/Mem:\s+(\d+)\s+(\d+)/',$mem,$m);

$ramTotal = $m[1] ?? 0;
$ramUsed  = $m[2] ?? 0;


/* =========================
   TEMPERATURA SOC
========================= */

$tempFile="/sys/class/thermal/thermal_zone0/temp";

$temp = file_exists($tempFile)
? intval(file_get_contents($tempFile))/1000
: 0;


/* =========================
   DISK SPACE
========================= */

$totalDisk = disk_total_space("/");
$freeDisk  = disk_free_space("/");

$diskTotal = round($totalDisk / (1024*1024*1024));
$diskUsed  = round(($totalDisk - $freeDisk) / (1024*1024*1024));


/* =========================
   DISK IO
========================= */

$io = @shell_exec(
"iostat -dx 1 1 2>/dev/null | grep -E 'sd|nvme' | head -n 1"
);

$ioParts = preg_split('/\s+/',trim($io));

$read  = floatval($ioParts[5] ?? 0);
$write = floatval($ioParts[6] ?? 0);


/* =========================
   FRIGATE CPU
========================= */

$docker = @shell_exec(
"docker stats frigate --no-stream --format '{{.CPUPerc}}' 2>/dev/null"
);

$frigateCpu = floatval(
str_replace('%','',$docker)
);


/* =========================
   DOCKER CONTAINERS
========================= */

$containers = [];

$dockerList = @shell_exec(
"docker ps -a --format '{{.Names}}|{{.State}}' 2>/dev/null"
);

if($dockerList){

foreach(explode("\n",trim($dockerList)) as $line){

if(!$line) continue;

$parts = explode("|",$line);

$name = $parts[0] ?? "";
$state = $parts[1] ?? "";

$containers[] = [
"name"=>$name,
"state"=>$state
];

}

}


/* =========================
   OUTPUT JSON
========================= */

echo json_encode([

"load" => $loadValue,

"cpu" => $cpuUsage,

"cores" => $cores,

"ram_used" => $ramUsed,
"ram_total" => $ramTotal,

"temp" => $temp,

"disk_used" => $diskUsed,
"disk_total" => $diskTotal,

"disk_read" => $read,
"disk_write" => $write,

"frigate_cpu" => $frigateCpu,

"containers" => $containers

]);