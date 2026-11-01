# Import: Bitrix24 → site

Bitrix24 uploads `offers.xml` (or a `.zip` of it) with `mode=file`, then calls `mode=import` until it gets `success`.

| `mode=import` call | Response |
|---|---|
| a `.zip` is in `import/` | extracts its `.xml` entries only and deletes it: `progress`, `Extracting zip file offers.zip` |
| an `.xml` is in `import/` | translates it to English in place, calls the handlers per offer, deletes it: `progress`, `Import and delete file offers.xml` |
| nothing left | `success`, `All files was imported!` |

An XML without offers fails with `failure`, `Error: Empty offers in file: …`.

## Handlers

Override them in the [exchange class](setup.md). Each gets the offer `Ид` (your EXTERNAL_ID) and its part of the offer. The default returns `false` and does nothing.

```php
// <Остатки><Остаток><Склад><Ид>main</Ид><Количество>7</Количество></Склад></Остаток></Остатки>
public static function restsHandler($product_id, array $rests): bool
{
    // $rests = ['id' => 'main', 'quantity' => '7']: one warehouse, values are strings
    $quantity = $rests['quantity'] ?? array_sum(array_column($rests, 'quantity'));
    return Products::set(Products::findByExternalId($product_id), ['quantity' => $quantity]);
}

public static function pricesHandler($product_id, array $prices): bool
{
    return false;
}

public static function productsHandler($product_id, array $products): bool
{
    return false;
}
```

## Offers file

```xml
<КоммерческаяИнформация xmlns="urn:1C.ru:commerceml_2" ВерсияСхемы="2.09">
  <ПакетПредложений>
    <Ид>WooCommerce</Ид>
    <Предложения>
      <Предложение>
        <Ид>b5c3…</Ид>
        <Остатки><Остаток><Склад><Ид>main</Ид><Количество>7</Количество></Склад></Остаток></Остатки>
      </Предложение>
    </Предложения>
  </ПакетПредложений>
</КоммерческаяИнформация>
```
