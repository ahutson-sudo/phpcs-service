<?php
declare(strict_types=1);
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Runner;
require __DIR__ . '/../vendor/autoload.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST only']); exit; }
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$code = $payload['code'] ?? '';
$filename = preg_replace('~[^\w.\-]~','_', $payload['filename'] ?? 'snippet.php');
if ($code === '') { http_response_code(400); echo json_encode(['error'=>'Missing "code"']); exit; }
$work = sys_get_temp_dir().'/scan-'.bin2hex(random_bytes(6)); @mkdir($work);
$path = $work.'/'.$filename; file_put_contents($path, $code);
$fixCfg = new Config(['--standard=WordPress','--extensions=php',$path]);
$fix = new Runner(); $fix->config=$fixCfg; $fix->init(); ob_start(); $fix->runPHPCBF(); ob_end_clean();
$chkCfg = new Config(['--standard=WordPress','--extensions=php','--report=json',$path]);
$chk = new Runner(); $chk->config=$chkCfg; $chk->init(); ob_start(); $chk->runPHPCS(); $report = ob_get_clean();
echo json_encode(['fixedCode'=>file_get_contents($path),'remaining'=>json_decode($report,true)]);
