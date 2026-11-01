# CommerceML XML and helpers

`Flamix\CommerceML\OneC\CommerceML` builds and translates the XML; usable on its own, without the exchange.

## Build

```php
use Flamix\CommerceML\OneC\CommerceML;

// Russian root + Russian keys: the array keys are written as given.
$xml = CommerceML::init('ru')->setArray([
    'ПакетПредложений' => [
        'Ид' => 'FLAMIX',
        'Предложения' => [
            ['code' => 'Предложение', 'Ид' => 'b5c3…', 'Количество' => 7], // 'code' = tag name for a list item
        ],
    ],
])->getXML();

// English keys, then translate the whole document.
$cml = CommerceML::init()->setArray(['catalog' => ['id' => 'FLAMIX', 'name' => 'Shop']]);
$ru = $cml->translate($cml->clearEmptyTags($cml->getXML()), 'ru'); // clearEmptyTags() matches English names only

CommerceML::init()->setArray($data)->saveToFile('/tmp/offers.xml');
```

A list item without `code` becomes `<item_0>`. A value `['code' => 'id', 'value' => 'x']` is written as `<id>x</id>`.

## Parse

```php
$en = CommerceML::init()->translate(file_get_contents($file), 'en'); // Russian tags → English
$xml = simplexml_load_string($en);
$xml->offersPackage->offers->offer;

$array = (new CommerceML)->setFromString($en)->getData(true); // a single child is an object, not a list of one
$array['offersPackage']['offers']['offer'];
```

`translate()` renames whole tag names only, by `src/translate.php`; text and unknown tags stay.

## Helpers

Defined by `\Flamix\CommerceML\Init::init()`, not by the Composer autoloader.

```php
commerceml_log('Import started', ['file' => 'offers.xml']); // FLAMIX_LOGS_PATH/logs/commerceml/<date>-info-….log

commerceml_config('items_per_page', 30); // key from FLAMIX_EXCHANGE_DIR_PATH/config.php, or the default
commerceml_config('missing');            // null default + missing key = the whole config array

commerceml_response(['success', 'PHPSESSID', $id]); // echoes the lines joined by PHP_EOL, then die()

commerceml_response_by_type('progress', 'Step 2'); // sends response_header, prints "progress\nStep 2", die()
commerceml_response_by_type('oops', 'x');          // anything but success/progress prints "failure"
```
