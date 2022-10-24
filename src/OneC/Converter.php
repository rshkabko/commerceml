<?php

namespace Flamix\CommerceML\OneC;

class Converter
{
    /**
     * Replace products keys if exist translate
     *
     * @param array $entities
     * @param string $code
     * @param string|null $closure_key Key to find Closure (Check Categories)
     * @return array
     */
    public static function prepareToCommerceMLStructure(array $entities, string $code = '', ?string $closure_key = null): array
    {
        foreach ($entities as &$entity) {
            // Run closure if key exist
            if ($closure_key && !empty($entity[$closure_key]))
                $entity[$closure_key] = self::prepareToCommerceMLStructure($entity[$closure_key], $code, $closure_key);

            // Set CommerceML special 'code' attribute
            if (!empty($code))
                $entity['code'] = $code;

            if(!is_array($entity))
                continue;

            // Working with all keys
            foreach ($entity as $field_key => $field_value) {
                if (self::isHaveSpecialMethod($field_key)) {
                    $name = self::getSpecialMethodName($field_key);
                    $entity[$field_key] = call_user_func([self::class, $name], $field_value);
                    unset($entity[$field_key]);
                }
            }
        }

        return $entities;
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

    /**
     * Обработка поля "ЗначенияСвойства"
     * @param array $values
     * @return array
     */
    private static function attrProperty_value(array $values): array
    {
        foreach ($values as $id => &$value) {
            // Multi
            if (is_array($value)) {
                foreach ($value as $key => $option) {
                    $value[$key] = [
                        'code' => 'xml_value',
                        'value' => $option,
                    ];
                }
                // Single
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

    /**
     * Обработка поля "Группа"
     * @param array $values
     * @return array
     */
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