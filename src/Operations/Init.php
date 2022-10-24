<?php

namespace Flamix\CommerceML\Operations;

class Init
{
    public static function clearDir(string $type)
    {
        $dir = ($type === 'get_catalog') ? 'export' : 'import';
        Files::exchange($dir)->clearDirectory();
        (new GetCatalog)->setPage(0);
        commerceml_response_by_type('success', 'Removed directory: '. $dir);
    }
}