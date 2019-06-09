<?php


namespace sockstack;


class Log
{
    private static $log_path = "./logs/";

    private static $support_type = [
        'log' => "\033[36m",
        'debug' => "\033[33m",
        'error' => "\033[31m"
    ];

    private static $str = "[~#T#~] LOG.~#TYPE#~ ~#P#~ - ~#N#~ - ~#M#~";

    private static function rep($color, $time, $pid, $message, $type)
    {
        $str = str_replace(
            ["~#T#~", "~#TYPE#~", "~#P#~", "~#N#~", "~#M#~"],
            [$time, $type, $pid, cli_get_process_title(), $message],
            static::$str
        );

        $log_file = trim(static::$log_path, "/") . "/" . date("Y-m-d") . ".log";
        if (!is_dir(dirname($log_file))) {
            $fd = fopen("/tmp/lock", "w");
            if (!is_dir(dirname($log_file)) && flock($fd, LOCK_EX | LOCK_NB)) {
                mkdir(dirname($log_file), 0777, true);
                flock($fd, LOCK_UN);
                fclose($fd);
            }
        }
        error_log($str . PHP_EOL, 3, $log_file);

        return $color . $str . "\033[0m" . PHP_EOL;
    }

    public static function __callStatic($name, $arguments)
    {
        if (!is_string($arguments[0])) {
            return;
        }
        if (!isset(static::$support_type[$name])) {
            static::rep(static::$support_type['error'], date("Y-m-d H:i:s"),
                posix_getpid(), "the method is not exist", $name);
        }

        $str = static::rep(static::$support_type[$name], date("Y-m-d H:i:s"),
            posix_getpid(), $arguments[0], $name);
        if (!isset($arguments[1]) || $arguments[1] != false) {
            echo $str;
        }
    }
}