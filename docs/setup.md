# Setup

A CMS plugin exposes one URL that Bitrix24 calls with `?type=…&mode=…`.

## PHP

Boot order matters: `FLAMIX_LOGS_PATH` comes from flamix/plugin-helpers, the `commerceml_*` helpers from `Init::init()`.

```php
// Defines FLAMIX_LOGS_PATH: logs and exchanged files live under it, so keep it outside the web root.
\Flamix\Plugin\Init::init(__DIR__, 'FLAMIX_EXCHANGE_PLUGIN')->setLogsPath(dirname(ABSPATH) . '/flamix-data');

// Defines FLAMIX_EXCHANGE_DIR_PATH = __DIR__ (where config.php is) and loads src/helpers.php.
\Flamix\CommerceML\Init::init(__DIR__);

if (($_GET['flamix_product'] ?? false) === 'Y')
    \My\CMS\Exchange::start(); // never returns: every response ends in die()
```

The exchange class.

```php
namespace My\CMS;

use Flamix\CommerceML\Init;
use Flamix\CommerceML\Operations\CheckAuth;

class Exchange extends Init
{
    protected static string $product_callback = Products::class;   // get(int $page) + productCount()
    protected static string $category_callback = Categories::class; // get()
    protected static string $attribute_callback = Attributes::class; // get()

    // Required: the parent throws "Please, make your own password checking…".
    public static function actionCheck()
    {
        if (Setting::get('login') !== ($_SERVER['PHP_AUTH_USER'] ?? ''))
            throw new \Exception('Bad login!'); // any exception becomes "failure\nError: Bad login!"

        if (Setting::get('password') !== ($_SERVER['PHP_AUTH_PW'] ?? ''))
            throw new \Exception('Bad password!');

        return CheckAuth::printPhpSession();
    }
}
```

Callbacks and handlers: [export.md](export.md), [import.md](import.md).

## config.php

Read from `FLAMIX_EXCHANGE_DIR_PATH/config.php` on every `commerceml_config()` call. Every key is optional.

```php
return [
    'response_header' => ['Content-Type: text/plain; charset=utf-8'], // values are sent as raw header lines
    'items_per_page' => 30,             // products per export step
    'iblock_id' => 'WooCommerce',       // Ид of the classifier and the catalog; default 'IBLOCK_ID'
    'iblock_name' => 'WooCommerce Products', // default 'IBLOCK_NAME'
    'dir_export' => 'export',           // FLAMIX_LOGS_PATH/commerceml/<dir>/
    'dir_import' => 'import',           // mode=init still clears the hardcoded export/ or import/
];
```

## Protocol

Every response is plain text: the status on the first line, then the description.

| Request | Response |
|---|---|
| `type=catalog&mode=checkauth` + Basic auth | `success`, `PHPSESSID`, `<id>`, `sessid=<md5>`, `timestamp=<time>` |
| any other mode | needs the cookie `PHPSESSID=<id>` from checkauth, else `failure`, `Empty PHPSESSID in Cookie!` or `Session did not pass checkauth!` |
| `type=get_catalog&mode=query` | `progress` + description + XML per step, then `success` (see [export.md](export.md)) |
| `type=catalog&mode=file&filename=offers.xml`, body = file | `success`, `File uploaded!`; a name other than `*.xml`/`*.zip` gets `failure`, `Only .xml and .zip files are accepted!` |
| `type=catalog&mode=import` | `progress` per zip or XML file, then `success`, `All files was imported!` |
| `mode=init` / `deactivate` / `complete` | deletes `export/` (get_catalog) or `import/` (catalog), `success`, `Removed directory: …` |
| `type` other than `catalog`, `get_catalog` | `failure`, `Type must be catalog or get_catalog, … given!` |
