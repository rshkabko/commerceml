<?php

namespace Flamix\CommerceML\OneC;

use SimpleXMLElement;

class CommerceML
{
    private string $version = 'FlamixSimplyCommerceML';
    private SimpleXMLElement $data;

    public function init(): CommerceML
    {
        return new CommerceML;
    }

    public function getData(bool $as_array = false)
    {
        if ($as_array)
            return json_decode(json_encode($this->data), true);

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

    public function setFromString(string $file_content): CommerceML
    {
        $this->data = new SimpleXMLElement($file_content);
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
        return '<?xml version="1.0" encoding="UTF-8"?>
                <CommercialInformation
                    xmlns="urn:1C.ru:commerceml_2" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    SchemeVersion="' . $this->version . '"
                    CreatedDate="' . date('Y-m-dTh:i:s') . '"
                >
                </CommercialInformation>';
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

                $value[$key] = $value['value'];
                unset($value['value']);

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

    /**
     * Native CommerceML use Russian language
     *
     * In our plugin we try to don't use this lang, but in some case we need for compatibility
     *
     * @param string $filepath Full file path
     * @param string $lang_to In which lang we will translate
     * @return string Translated content
     */
    public function translate(string $filepath, string $lang_to = 'en'): string
    {
        $translate_words = include(__DIR__ . '/../translate.php');
        if ($lang_to !== 'en')
            $translate_words = array_flip($translate_words);

        foreach ($translate_words as $translate_word_from => $translate_word_to) {
            unset($translate_words[$translate_word_from]);

            // If we use @ - Just ignore adding ">" tags
            if (str_contains($translate_word_from, '@') || str_contains($translate_word_to, '@')) {
                $translate_words[str_replace('@', '', $translate_word_from)] = str_replace('@', '', $translate_word_to);
                continue;
            }

            $translate_words[$translate_word_from . '>'] = $translate_word_to . '>';
            $translate_words[$translate_word_from . '/>'] = $translate_word_to . '/>'; // For empty tags, ex <id/>
        }

        // Replace in file
        $content = @file_get_contents($filepath);
        $content = str_replace($translate_words, array_keys($translate_words), $content);
        @file_put_contents($filepath, $content);
        return $content;
    }
}
