<?php
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $log = "[" . date("Y-m-d H:i:s") . "] Error: [$errno] $errstr - $errfile:$errline\n";
    error_log($log, 3, __DIR__ . "/logs/error.log");
}

// Register custom handler
set_error_handler("customErrorHandler");
?>