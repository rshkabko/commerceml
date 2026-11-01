<?php

namespace Flamix\CommerceML\Tests\Fixtures;

use Flamix\CommerceML\Init;
use Flamix\CommerceML\Operations\CheckAuth;

/** The site side of the exchange: an Init subclass with in-memory products and handlers that record their calls. */
class Shop extends Init
{
    protected static string $product_callback = Products::class;
    protected static string $category_callback = Categories::class;
    protected static string $attribute_callback = Attributes::class;

    public static function actionCheck()
    {
        if (($_SERVER['PHP_AUTH_USER'] ?? '') !== 'login' || ($_SERVER['PHP_AUTH_PW'] ?? '') !== 'secret')
            throw new \Exception('Bad password!');

        return CheckAuth::printPhpSession();
    }

    public static function restsHandler($product_id, array $rests): bool
    {
        return self::record('rests', $product_id, $rests);
    }

    public static function pricesHandler($product_id, array $prices): bool
    {
        return self::record('prices', $product_id, $prices);
    }

    public static function productsHandler($product_id, array $products): bool
    {
        return self::record('products', $product_id, $products);
    }

    private static function record(string $handler, $product_id, array $data): bool
    {
        $file = FLAMIX_LOGS_PATH . '/calls.json';
        $calls = is_file($file) ? json_decode(file_get_contents($file), true) : [];
        $calls[] = compact('handler', 'product_id', 'data');
        file_put_contents($file, json_encode($calls));
        return true;
    }
}

/** Product callback: productCount() plus one product per page, pages start at 1. */
class Products
{
    public static function productCount(): int
    {
        return 2;
    }

    public static function get(int $page, array $params = []): array
    {
        return [[
            'id' => 'product-' . $page,
            'name' => 'Product ' . $page,
            'sku' => 'SKU-' . $page,
            'description' => '',
            'categories' => [['code' => 'id', 'value' => 'cat-hoodies']],
            'prices' => ['BASE' => ['code' => 'price', 'priceId' => 'BASE', 'currency' => 'UAH', 'priceItem' => 1000 + $page]],
            'property_value' => ['pa_color' => 'Red'],
        ]];
    }
}

/** Category callback: a tree through the nested 'categories' key. */
class Categories
{
    public static function get(int $page = 0, array $params = []): array
    {
        return [[
            'id' => 'cat-clothing',
            'name' => 'Clothing',
            'categories' => [['id' => 'cat-hoodies', 'name' => 'Hoodies']],
        ]];
    }
}

/** Attribute callback: the classifier properties. */
class Attributes
{
    public static function get(int $page = 0, array $params = []): array
    {
        return [['id' => 'pa_color', 'name' => 'Color', 'multi' => 'true']];
    }
}
