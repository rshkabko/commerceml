<?php

namespace Flamix\CommerceML\Operations;

/** mode=init, deactivate and complete: wipes the exchange folder of the type. */
class Init
{
    // Hardcoded export/import: dir_export and dir_import from config.php are not read here.
    public static function clearDir(string $type)
    {
        $dir = ($type === 'get_catalog') ? 'export' : 'import';
        Files::exchange($dir)->clearDirectory();
        (new GetCatalog)->setPage(0);
        commerceml_response_by_type('success', 'Removed directory: '. $dir);
    }
}