<?php

namespace Flamix\Sync\Operations;

class Init
{
    public function clearDir(string $type)
    {
        $dir = ($type === 'get_catalog') ? 'import' : 'sync';
        Files::exchange($dir)->clearDirectory();
        Helpers::sendResponseByType('success', 'Removed directory: '. $dir);
    }
}