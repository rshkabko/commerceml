# Export: site → Bitrix24

`type=get_catalog&mode=query`, one step per request. The page lives in `$_SESSION['flamix_page']`.

| Page | Response |
|---|---|
| 0 | `progress`, `Uploaded 0 of N products from the site`, classifier XML (categories + properties) |
| 1…ceil(N / items_per_page) | `progress`, `Uploaded X of N …`, catalog XML with one page of products |
| after the last | `success`, `Uploaded N of N …`; the page resets to 0 |

Each step is also saved to `FLAMIX_LOGS_PATH/commerceml/export/import_<page>.xml` for debugging. `productCount()` returning `0` fails the step with `failure`, `Error: Products not found!`.

## Products callback

```php
class Products
{
    public static function productCount(): int
    {
        return 2;
    }

    // $page starts at 1.
    public static function get(int $page, array $params = []): array
    {
        return [[
            'id' => 'b5c3…',            // EXTERNAL_ID: Bitrix24 matches products by it
            'name' => 'Hoodie',
            'description' => '',        // null and '' fields are dropped
            'sku' => 'H-1',
            'image' => 'https://…/hoodie.jpg',
            'weight' => 100, 'length' => 12, 'width' => 34, 'height' => 56,
            'categories' => [['code' => 'id', 'value' => 'cat-hoodies']], // 'code' renames the tag, 'value' is its text
            'prices' => [
                'BASE' => ['code' => 'price', 'priceId' => 'BASE', 'currency' => 'UAH', 'priceItem' => 1002],
                'SALE' => ['code' => 'price', 'priceId' => 'SALE', 'currency' => 'UAH', 'priceItem' => 501],
            ],
        ]];
    }
}
```

The English keys are translated to CommerceML tags by `src/translate.php`: `sku` → `Артикул`, `priceItem` → `ЦенаЗаЕдиницу`. A key missing there is sent as is.

## Categories and attributes callbacks

```php
class Categories
{
    public static function get(int $page = 0, array $params = []): array
    {
        return [[
            'id' => 'cat-clothing',
            'name' => 'Clothing',
            'categories' => [['id' => 'cat-hoodies', 'name' => 'Hoodies']], // nested tree, any depth
        ]];
    }
}

class Attributes
{
    public static function get(int $page = 0, array $params = []): array
    {
        return [[
            'id' => 'pa_color',
            'name' => 'Color',
            'multi' => 'true',
            'variant_values' => [['code' => 'xml_value', 'value' => 'Red']],
        ]];
    }
}
```

## Result

```xml
<КоммерческаяИнформация xmlns="urn:1C.ru:commerceml_2" ВерсияСхемы="FlamixSimplyCommerceML" ДатаФормирования="…">
  <Каталог>
    <Ид>WooCommerce</Ид><ИдКлассификатора>WooCommerce</ИдКлассификатора><Наименование>WooCommerce Products</Наименование>
    <Товары><Товар><Ид>b5c3…</Ид><Наименование>Hoodie</Наименование><Артикул>H-1</Артикул>
      <Группы><Ид>cat-hoodies</Ид></Группы>
      <Цены><Цена><ИдТипаЦены>BASE</ИдТипаЦены><Валюта>UAH</Валюта><ЦенаЗаЕдиницу>1002</ЦенаЗаЕдиницу></Цена></Цены>
    </Товар></Товары>
  </Каталог>
</КоммерческаяИнформация>
```
