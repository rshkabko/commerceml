<?php

namespace Flamix\CommerceML;

use Flamix\CommerceML\Operations\CheckAuth;
use Flamix\CommerceML\Operations\Import;
use Flamix\CommerceML\Operations\Init as OperationInit;
use Flamix\CommerceML\Operations\GetCatalog;
use Flamix\CommerceML\Operations\Files;
use Flamix\CommerceML\Contracts\HasAllImportHandlers;

/** The exchange endpoint: extend it, set the three callbacks, override actionCheck() and the import handlers. */
class Init implements HasAllImportHandlers
{
    protected static string $product_callback;
    protected static string $category_callback;
    protected static string $attribute_callback;

    /** Entry point for the exchange URL; a subclass overrides it to boot CMS stuff before exchange(). */
    public static function start()
    {
        static::exchange();
    }

    /** Defines FLAMIX_EXCHANGE_DIR_PATH (the folder with config.php) and loads the commerceml_* helpers. */
    public static function init(string $path): Init
    {
        define('FLAMIX_EXCHANGE_DIR_PATH', $path);
        include_once 'helpers.php';
        return new Init();
    }

    /** Routes ?type=&mode= to an action; every branch ends the request through commerceml_response(). */
    public static function exchange()
    {
        $type = ($_GET['type'] ?? false) ?: '';
        $mode = ($_GET['mode'] ?? false) ?: '';

        commerceml_log('New request with params', ['type' => $type, 'mode' => $mode]);

        if (!in_array($type, ['catalog', 'get_catalog']))
            commerceml_response_by_type('failure', 'Type must be catalog or get_catalog, ' . $type . ' given!');

        try {
            $auth = new CheckAuth();
            switch ($mode) {
                case 'checkauth':
                    static::actionCheck();
                    break;

                case 'query':
                    $auth->checkByPhpSessionId();
                    static::actionQuery($type);
                    break;

                case 'file':
                    $auth->checkByPhpSessionId();
                    static::actionFile();
                    break;

                case 'import':
                    $auth->checkByPhpSessionId();
                    static::actionImport();
                    break;

                case 'init':
                case 'deactivate':
                case 'complete':
                    $auth->checkByPhpSessionId();
                    static::actionInit($type);
                    break;

                default:
                    commerceml_response_by_type('failure', 'Mode not found!');
                    break;
            }
        } catch (\Exception $exception) {
            commerceml_response_by_type('failure', 'Error: ' . $exception->getMessage());
        }

        commerceml_response_by_type('failure', 'The end!');
    }

    /**
     * Checks the Basic auth login and password and prints the session id; must be overridden.
     *
     * @throws \Exception
     */
    public static function actionCheck()
    {
        throw new \Exception('Please, make your own password checking by extends actionCheck() method!');

        // Unreachable on purpose: the shape a subclass's override takes.
        if ('my_login_from_CMS' !== ($_SERVER['PHP_AUTH_USER'] ?? ''))
            throw new \Exception('Bad login!');

        if ('my_password_from_CMS' !== ($_SERVER['PHP_AUTH_PW'] ?? ''))
            throw new \Exception('Bad password!');

        return CheckAuth::printPhpSession();
    }

    /** Deletes the export (get_catalog) or import (catalog) folder and resets the export page. */
    public static function actionInit(string $type)
    {
        OperationInit::clearDir($type);
    }

    /** Prints the next export step: the classifier first, then one products page per request. */
    public static function actionQuery(string $type)
    {
        tap(new GetCatalog(), function ($instance) {
            $instance->query(static::$product_callback, static::$category_callback, static::$attribute_callback);
        });
    }

    /**
     * Writes the request body to ?filename= (an .xml or .zip name) in the import folder.
     *
     * @throws \Exception
     */
    public static function actionFile()
    {
        // basename + extension check: the name comes from the request and lands in a folder under the web root.
        $name = basename((string) ($_REQUEST['filename'] ?? ''));
        if (!preg_match('/^[\w.-]+\.(xml|zip)$/i', $name))
            commerceml_response_by_type('failure', 'Only .xml and .zip files are accepted!');

        Files::exchange(commerceml_config('dir_import', 'import'))->create($name)->uploadBinary($name);
    }

    /** Unzips one archive or imports one XML file per request, calling the handlers per offer. */
    public static function actionImport()
    {
        tap(new Import, function ($importController) {
            $importController->unzipAndDeleteAllFilesInFolderBySteps();
            $importController->importAllFilesInFolderBySteps([static::class, 'restsHandler'], [static::class, 'pricesHandler'], [static::class, 'productsHandler']);
        });
    }

    public static function restsHandler($product_id, array $rests): bool
    {
        return false;
    }

    public static function pricesHandler($product_id, array $prices): bool
    {
        return false;
    }

    public static function productsHandler($product_id, array $products): bool
    {
        return false;
    }
}