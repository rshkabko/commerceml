<?php

namespace Flamix\CommerceML;

use Flamix\CommerceML\Operations\CheckAuth;
use Flamix\CommerceML\Operations\Import;
use Flamix\CommerceML\Operations\Init as OperationInit;
use Flamix\CommerceML\Operations\GetCatalog;
use Flamix\CommerceML\Operations\Files;
use Flamix\CommerceML\Contracts\HasAllImportHandlers;

class Init implements HasAllImportHandlers
{
    protected static string $product_callback;
    protected static string $category_callback;
    protected static string $attribute_callback;

    /**
     * Starting exchange
     *
     * We use separate method because in this case we can extend Init and change start (ex, add some CMS staff)
     *
     * @return void
     */
    public static function start()
    {
        static::exchange();
    }

    public static function init(string $path): Init
    {
        define('FLAMIX_EXCHANGE_DIR_PATH', $path);
        include_once 'helpers.php';
        return new Init();
    }

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
     * Checking login and password and return PHP_SESSIONID
     *
     * @return mixed
     * @throws \Exception
     */
    public static function actionCheck()
    {
        throw new \Exception('Please, make your own password checking by extends actionCheck() method!');

        // Example
        if ('my_login_from_CMS' !== ($_SERVER['PHP_AUTH_USER'] ?? ''))
            throw new \Exception('Bad login!');

        if ('my_password_from_CMS' !== ($_SERVER['PHP_AUTH_PW'] ?? ''))
            throw new \Exception('Bad password!');

        // If OK - Print our session_id
        return CheckAuth::printPhpSession();
    }

    /**
     * Delete old export files
     *
     * @param string $type
     * @return void
     */
    public static function actionInit(string $type)
    {
        OperationInit::clearDir($type);
    }

    /**
     * Export catalog
     *
     * @param string $type
     * @return void
     */
    public static function actionQuery(string $type)
    {
        tap(new GetCatalog(), function ($instance) {
            $instance->query(static::$product_callback, static::$category_callback, static::$attribute_callback);
        });
    }

    /**
     * Upload file
     *
     * @return void
     * @throws \Exception
     */
    public static function actionFile()
    {
        Files::exchange('upload')->create($_REQUEST['filename'] ?? '')->uploadBinary($_REQUEST['filename'] ?? '');
    }

    /**
     * Import rests, prices and products with standards attributes
     *
     * @return void
     */
    public static function actionImport()
    {
        tap(new Import, function ($importController) {
            $importController->unzipAndDeleteAllFilesInFolderBySteps();
            $importController->importAllFilesInFolderBySteps([static::class, 'restsHandler'], [static::class, 'pricesHandler'], [static::class, 'productsHandler']);
        });
    }

    /****************** | HANDLERS - CHANGE! | *******************/

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