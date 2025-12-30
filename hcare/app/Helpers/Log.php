<?php
class Log {
    public static function info($msg, $data = []) {
        $logFile = __DIR__ . '/../../logs/debug_auth.log';
        $timestamp = date('Y-m-d H:i:s');
        $content = "[$timestamp] INFO: $msg " . json_encode($data) . "\n";
        file_put_contents($logFile, $content, FILE_APPEND);
    }
    
    public static function error($msg, $data = []) {
        $logFile = __DIR__ . '/../../logs/debug_auth.log';
        $timestamp = date('Y-m-d H:i:s');
        $content = "[$timestamp] ERROR: $msg " . json_encode($data) . "\n";
        file_put_contents($logFile, $content, FILE_APPEND);
    }
}
