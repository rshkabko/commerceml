<?php

namespace Flamix\CommerceML\OneC;

/** Prepares callback arrays for CommerceML::setArray(): drops empty fields, sets 'code', reshapes special fields. */
class Converter
{
    /** @param string|null $closure_key A nested key prepared the same way, recursively: 'categories' for the category tree. */
    public static function prepareToCommerceMLStructure(array $entities, string $code = '', ?string $closure_key = null): array
    {
        foreach ($entities as &$entity) {
            $entity = array_filter($entity, fn($value) => !is_null($value) && $value !== '');

            if ($closure_key && !empty($entity[$closure_key]))
                $entity[$closure_key] = self::prepareToCommerceMLStructure($entity[$closure_key], $code, $closure_key);

            if (!empty($code))
                $entity['code'] = $code;

            if (!is_array($entity))
                continue;

            // Working with all keys
            foreach ($entity as $field_key => $field_value) {
                if (self::isHaveSpecialMethod($field_key) && !empty($field_value)) {
                    $name = self::getSpecialMethodName($field_key);
                    $entity[$field_key] = call_user_func([self::class, $name], $field_value);
                    unset($entity[$field_key]);
                }
            }
        }

        return $entities;
    }

    private static function getSpecialMethodName(string $method): string
    {
        return 'attr' . ucfirst($method);
    }

    /** A field with an attr<Field>() method here is reshaped by it: property_value, category. */
    private static function isHaveSpecialMethod(string $method): bool
    {
        return method_exists(static::class, self::getSpecialMethodName($method));
    }

    /** ['pa_color' => 'Red' or ['Red', 'Blue']] into ЗначенияСвойства entries. */
    private static function attrProperty_value(array $values): array
    {
        foreach ($values as $id => &$value) {
            if (is_array($value)) {
                foreach ($value as $key => $option) {
                    $value[$key] = [
                        'code' => 'xml_value',
                        'value' => $option,
                    ];
                }
            } else {
                $option = $value;
                $value = [
                    'code' => 'xml_value',
                    'value' => $option,
                ];
            }

            $value['code'] = 'property_value';
            $value['id'] = $id;
        }

        return $values;
    }

    /** A list of category ids into <id> entries. */
    private static function attrCategory(array $values): array
    {
        foreach ($values as &$value) {
            $option = $value;
            $value = [
                'code' => 'id',
                'value' => $option,
            ];
        }

        return $values;
    }
}