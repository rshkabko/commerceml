<?php

namespace Flamix\CommerceML\OneC\Traits;

trait Replacer
{
    /**
     * Replace products keys if exist translate
     *
     * @param array $products
     * @param string $code
     * @param string|null $closure_key Key to find Closure (Check Categories)
     * @return array
     */
    public static function replaceToCyrillic(array $products, string $code = '', ?string $closure_key = null): array
    {
        foreach ($products as &$product) {
            // Closure
            if ($closure_key && !empty($product[$closure_key]))
                $product[$closure_key] = self::replaceToCyrillic($product[$closure_key], $code, $closure_key);

            if (!empty($code))
                $product['code'] = $code;

            foreach ($product as $field_key => $field_value) {
                $need = self::needReplace($field_key);
                if ($need) {
                    if (!self::isHaveSpecialMethod($field_key))
                        $product[$need] = $field_value;
                    else {
                        $name = self::getSpecialMethodName($field_key);
                        $product[$need] = self::$name($field_value);
                    }

                    unset($product[$field_key]);
                }
            }
        }

        return $products;
    }

    /**
     * Return translated key or input key, if we didn't have translated : Converter::getTranslate('products');
     *
     * @param string $key
     * @return string
     */
    public static function getTranslate(string $key): string
    {
        $translate = self::needReplace($key);
        if (!$translate)
            return $key;

        return $translate;
    }

    /**
     * Checking if we have translated value
     *
     * @param string $key
     * @return string|bool
     */
    private static function needReplace(string $key): string|bool
    {
        return commerceml_config('translate.' . $key, false);
    }

    /**
     * Return special method to more modifications of variable, ex: attrPrice($value);
     *
     * @param string $method
     * @return string
     */
    private static function getSpecialMethodName(string $method): string
    {
        return 'attr' . ucfirst($method);
    }

    /**
     * Checking is we have special method to modify variable, ex: attrPrice($value);
     *
     * @param string $method
     * @return bool
     */
    private static function isHaveSpecialMethod(string $method): bool
    {
        return method_exists(static::class, self::getSpecialMethodName($method));
    }
}
