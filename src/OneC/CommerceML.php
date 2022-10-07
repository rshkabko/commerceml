<?php

namespace Flamix\CommerceML\OneC;

use SimpleXMLElement;

class CommerceML
{
    private string $version = '2.09';
    private SimpleXMLElement $data;

    public function init(): CommerceML
    {
        return $this;
    }

    public function getData(): SimpleXMLElement
    {
        return $this->data;
    }

    public function getXML(): string
    {
        return $this->data->asXML();
    }

    public function saveToFile(string $file): CommerceML
    {
        $this->data->asXML($file);
        return $this;
    }

    public function setArray(array $data): CommerceML
    {
        $this->data = new SimpleXMLElement($this->exportPrepareData());
        $this->array_to_xml($data, $this->data);
        return $this;
    }

    private function exportPrepareData(): string
    {
        return str_replace([':version', ':date'], [$this->version, date('Y-m-dTh:i:s')], commerceml_config('translate.starter', ''));
    }

    public static function array_to_xml($data, SimpleXMLElement &$xml_data): void
    {
        foreach ($data as $key => $value) {
            //Потому что есть одинаковые ключи "Склад" и тд
            if (isset($value['code'])) {
                $key = $value['code'];
                unset($value['code']);
            }

            if (is_array($value) && !isset($value['value'])) {
                if (is_numeric($key))
                    $key = 'item_' . $key;

                if (!empty($value['value'])) {
                    $value[$key] = $value['value'];
                    unset($value['value']);
                }

                $subnode = $xml_data->addChild($key);
                self::array_to_xml($value, $subnode);

                // А еще можно передать массив (если нужно code), но value в значение подставить
            } else if (isset($value['value'])) {
                $xml_data->addChild("$key", mb_convert_encoding(htmlspecialchars($value['value']), 'utf-8', mb_detect_encoding($value['value'])));
            } else {
                $xml_data->addChild("$key", mb_convert_encoding(htmlspecialchars($value), 'utf-8', mb_detect_encoding($value)));
            }
        }
    }
}
