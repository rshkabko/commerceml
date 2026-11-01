<?php

namespace Flamix\CommerceML\Contracts;

/** The handlers Import calls per offer with its EXTERNAL_ID. */
interface HasAllImportHandlers
{
    public static function restsHandler($product_id, array $rests): bool;
    public static function pricesHandler($product_id, array $prices): bool;
    public static function productsHandler($product_id, array $products): bool;
}