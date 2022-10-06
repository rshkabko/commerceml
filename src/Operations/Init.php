<?php

namespace Flamix\CommerceML\Operations;

class Init
{
    public static function clearDir(string $type)
    {
        $dir = ($type === 'get_catalog') ? 'import' : 'sync';
        Files::exchange($dir)->clearDirectory();
        commerceml_response_by_type('success', 'Removed directory: '. $dir);
    }
}