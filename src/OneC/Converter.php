<?php

namespace Flamix\CommerceML\OneC;

use Flamix\CommerceML\OneC\Traits\Replacer;

class Converter
{
    use Replacer;

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
                        'code' => Converter::getTranslate('xml_value'),
                        'value' => $option,
                    ];
                }
                // Single
            } else {
                $option = $value;
                $value = [
                    'code' => Converter::getTranslate('xml_value'),
                    'value' => $option,
                ];
            }

            $value['code'] = Converter::getTranslate('property_value');
            $value[Converter::getTranslate('id')] = $id;
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
                'code' => Converter::getTranslate('id'),
                'value' => $option,
            ];
        }

        return $values;
    }
}