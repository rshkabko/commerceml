<?php namespace Flamix\Sync;

class Init
{
    public static function init(string $path): Init
    {
        define('FLAMIX_EXCHANGE_DIR_PATH', $path);
        include_once 'helpers.php';

        return $this;
    }
}