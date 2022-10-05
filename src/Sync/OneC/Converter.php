<?php

namespace Flamix\Sync\OneC;

use Flamix\Sync\OneC\Traits\Replacer;

class Converter
{
    use Replacer;

    /**
     * Обработка поля "ЗначенияСвойства"
     * @param array $values
     * @return array
     */
    private static function attrProperty(array $values): array
    {
        foreach ($values as $id => &$value) {
            // Multi
            if (is_array($value)) {
                foreach ($value as $key => $option) {
                    $value[$key] = [
                        'code' => 'Значение',
                        'value' => $option,
                    ];
                }
                // Single
            } else {
                $option = $value;
                $value = [
                    'code' => 'Значение',
                    'value' => $option,
                ];
            }

            $value['code'] = 'ЗначенияСвойства';
            $value['Ид'] = $id;
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
                'code' => 'Ид',
                'value' => $option,
            ];
        }

        return $values;
    }
}