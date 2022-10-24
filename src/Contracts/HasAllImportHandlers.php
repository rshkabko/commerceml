<?php

namespace Flamix\CommerceML\Contracts;

interface HasAllImportHandlers
{
    public static function restsHandler($product_id, array $rests): bool;
    public static function pricesHandler($product_id, array $prices): bool;
    public static function productsHandler($product_id, array $products): bool;
}