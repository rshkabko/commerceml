---
name: commerceml-exchange
description: "Flamix commerceml exchange protocol and XML. Use when writing or debugging a site exchange class extending Flamix\\CommerceML\\Init (actionCheck, restsHandler, pricesHandler, productsHandler, product/category/attribute callbacks, productCount), the ?type=catalog|get_catalog&mode=checkauth|query|file|import|init requests, config.php keys (items_per_page, iblock_id, dir_import), or building/parsing XML with OneC\\CommerceML (setArray, translate, getData, clearEmptyTags) and OneC\\Converter. Also use for the symptoms: prices or product fields never update on the site, only the first warehouse's stock arrives or none, product attributes or categories missing from the exported XML, mode=import answers the same failure forever, 'Session did not pass checkauth!' mid-exchange, an upload rejected with 'Only .xml and .zip files are accepted!', an export that fails with \"Products not found!\", Russian tags left in a translated file, code after a response never runs. Skip for the Inventory app's own 1C controllers and Bitrix24 REST calls."
---

# CommerceML exchange

One URL on the site answers Bitrix24 step by step: `checkauth` issues a PHP session, `query` exports the catalog one page per request, `file` + `import` bring stock back. `OneC\CommerceML` turns arrays into CommerceML XML and renames tags between English and 1C Russian.

Full docs: vendor/flamix/commerceml/docs/setup.md, export.md, import.md, xml.md. Below is what the docs get wrong or omit.

## Every response ends the process

`commerceml_response()` calls `die()`. `GetCatalog::query()`, `Files::uploadBinary()` and both `Import` loops print and die inside, so:

- one request = one step: one zip extracted, or one XML imported, or one export page;
- code after `static::exchange()` or after a handler loop never runs; do cleanup inside the handler;
- PHPUnit cannot call these in-process. The package tests run a real `php -S` against `tests/Fixtures/server.php`.

## Known bugs (tests marked incomplete)

| Symptom | Cause | Until fixed |
|---|---|---|
| `pricesHandler`, `productsHandler` never called | `Import::import()` reads `$entity['Цены']['Цена']` / `['Товары']['Товар']`, but `translateFile($file, 'en')` already renamed them to `prices`/`price`, `products`/`product` | Prices cannot be imported through the handlers |
| Stock arrives only for offers with one `<Остаток>` | With several, `Остатки.Остаток` is a list, and `['Остатки']['Остаток']['Склад']` is missing | Only one warehouse per offer reaches `restsHandler` |
| `property_value` and `category` missing from exported products | `Converter::prepareToCommerceMLStructure()` assigns the `attr*()` result and unsets the same key on the next line | Send categories as `'categories' => [['code' => 'id', 'value' => $id]]`; property values are not exported |

## Import traps

- `restsHandler` gets `['id' => 'main', 'quantity' => '7']` for one `<Склад>`, strings, not a list. Handle both: `$rests['quantity'] ?? array_sum(array_column($rests, 'quantity'))`.
- `Остатки`, `Остаток`, `Склад` are not in `src/translate.php`, so they stay Russian while `Ид`/`Количество` inside become `id`/`quantity`.
- A file whose offers are empty throws before `deleteFile()`: it stays in `import/`, and every next `mode=import` answers the same `failure`. Clear it with `mode=init`.
- `Files::create()` truncates before `uploadBinary()` appends: a file sent in several `mode=file` parts keeps only the last part.
- `?filename=` is reduced to its base name and must match `*.xml` or `*.zip`; a zip yields only its `.xml` entries, flattened.
- Every mode needs a session from checkauth: a pause over `session.gc_maxlifetime` between steps fails with `Session did not pass checkauth!`; call checkauth again.
- `mode=init` clears the hardcoded `export/` or `import/`, not `dir_export`/`dir_import` from config.php.

## Export traps

- `productCount()` is required on the product callback though `ExportImport` does not declare it. Returning `0` fails the step: `failure`, `Error: Products not found!`.
- Products `get($page)` is called with pages starting at 1; page 0 is the classifier.
- `null` and `''` fields are dropped; `0` and `false` are kept.
- The XML Bitrix24 reads is the response body. `export/import_<page>.xml` is only a debug copy.
- `clearEmptyTags()` matches `[a-zA-Z]` names only: run it before translating to Russian, as `GetCatalog` does.

## Boot traps

- `Init::init($dir)` calls `define('FLAMIX_EXCHANGE_DIR_PATH')`; a second call warns and keeps the first path.
- `Files::exchange()` throws without `FLAMIX_LOGS_PATH`: call `\Flamix\Plugin\Init::init(...)->setLogsPath(...)` first.
- `commerceml_config('key')` with no default returns the whole config array when the key is missing, not `null`.
- The parent `actionCheck()` always throws; checkauth fails until you override it.
- Old snippets use `\Flamix\Sync\Init::init()`; the class is `\Flamix\CommerceML\Init`.

## Building XML in an app

```php
use Flamix\CommerceML\OneC\CommerceML;

$offers = [];
foreach ($rows as $row)
    $offers[] = ['code' => 'Предложение', 'Ид' => $row['xml_id'], 'Количество' => $row['quantity']];

$xml = CommerceML::init('ru')->setArray(['ПакетПредложений' => ['Ид' => 'FLAMIX', 'Предложения' => $offers]])->getXML();
```

```php
// Parsing a 1C file: translate first, then normalise one-vs-many.
$xml = simplexml_load_string(CommerceML::init()->translate(file_get_contents($file), 'en'));
foreach ($xml->offersPackage->offers->offer ?? [] as $offer) { /* SimpleXML iterates one or many alike */ }
```
