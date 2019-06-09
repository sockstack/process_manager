<?php

require "vendor/autoload.php";

(new sockstack\Master())->setCallable(function () {
    $data = md5(microtime(true));
    \sockstack\Log::debug($data);
})->start();
