<?php

namespace Flamix\CommerceML\OneC;

use SimpleXMLElement;

class CommerceML
{
    private string $version = 'FlamixSimplyCommerceML';
    private SimpleXMLElement $data;

    /**
     * Simply init like static.
     *
     * @return CommerceML
     */
    public static function init(): CommerceML
    {
        return new CommerceML;
    }

    /**
     * Get XML Data like a object or array.
     *
     * @param bool $as_array
     * @return mixed|SimpleXMLElement
     */
    public function getData(bool $as_array = false)
    {
        if ($as_array)
            return json_decode(json_encode($this->data), true);

        return $this->data;
    }

    /**
     * Get XML Data like a XML
     *
     * @return string
     */
    public function getXML(): string
    {
        return $this->data->asXML();
    }

    /**
     * Save XML data to file like a XML
     *
     * @param string $file
     * @return $this
     */
    public function saveToFile(string $file): CommerceML
    {
        $this->data->asXML($file);
        return $this;
    }

    /**
     * Init SimpleXMLElement object from STRING.
     *
     * Ex, parse file content to SimpleXMLElement object
     *
     * @param string $file_content
     * @return $this
     * @throws \Exception
     */
    public function setFromString(string $file_content): CommerceML
    {
        $this->data = new SimpleXMLElement($file_content);
        return $this;
    }

    /**
     * Init SimpleXMLElement object from ARRAY.
     *
     * @param array $data
     * @param string|null $starting_data Must be valid XML
     * @return $this
     * @throws \Exception
     */
    public function setArray(array $data, ?string $starting_data = null): CommerceML
    {
        $this->data = new SimpleXMLElement($starting_data ?: $this->exportPrepareData());
        $this->array_to_xml($data, $this->data);
        return $this;
    }

    /**
     * Default starting SimpleXMLElement Data.
     *
     * @return string
     */
    private function exportPrepareData(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
                <CommercialInformation
                    xmlns="urn:1C.ru:commerceml_2" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    SchemeVersion="' . $this->version . '"
                    CreatedDate="' . date('Y-m-dTh:i:s') . '"
                ></CommercialInformation>';
    }

    public static function array_to_xml(array $data, SimpleXMLElement &$xml_data): SimpleXMLElement
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

        return $xml_data;
    }

    /**
     * Translate content.
     *
     * Native CommerceML use Russian language>
     * In our plugin we try to don't use this lang, but in some case we need for compatibility
     *
     * @param string $content Content to translate
     * @param string $lang_to In which lang we will translate
     * @return string Translated content
     */
    public function translate(string $content, string $lang_to = 'en'): string
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

        return str_replace($translate_words, array_keys($translate_words), $content);
    }

    /**
     * Translate file content.
     *
     * @param string $filepath Full file path
     * @param string $lang_to In which lang we will translate
     * @return string Translated content
     */
    public function translateFile(string $filepath, string $lang_to = 'en'): string
    {
        $content = @file_get_contents($filepath);
        $content = $this->translate($content, $lang_to);
        @file_put_contents($filepath, $content);
        return $content;
    }
}
