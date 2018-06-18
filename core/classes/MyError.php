<?php
class MyError {
    static function errorCatcher($code, $msg, $file, $line) {
        if (error_reporting() & $code) {
            throw new PHP_Exception($msg, $code, $file, $line);
        }
    }
    
    static function exceptionCatcher($e, $is_exit = true) {
        if (!is_a($e, 'Base_Exception')) {
            $e = new Unknown_Exception($e);
        }
        
        $trace_arr = (cfg('is_var_dump_trace') ? varDump($e->getTrace()) : print_r($e->getTrace(), true));
        $trace_str = base64_encode(gzencode($trace_arr));
        
        $log_str = "[TS_START: " . TS_START . "]" . ($is_exit ? ' EXIT' : '') . "\n"
                . ">>> Class: " . $e->getClass() . "\n"
                . ">>> Message: " . $e->getMessage() . "\n"
                //. "File: " . $e->getFile() . " (" . $e->getCode() . ")\n"
                . ">>> Trace: $trace_str\n"
                . "_____________________________________________________________________________";
        
        error_log($log_str);
        
        if ($is_exit) {
            if (cfg('is_call_500error')) {
                header('HTTP/1.1 500 Internal Server Error');
            }
            
            exit();
        }
    }
    
    static function shutdown() {
        $error = error_get_last();
        
        if (isset($error)) {
            if ($error['type'] === E_ERROR
                || $error['type'] === E_PARSE
                || $error['type'] === E_COMPILE_ERROR
                || $error['type'] === E_CORE_ERROR
            ) {
                self::exceptionCatcher(new Fatal_Exception($error));
            }
        }
    }
}
