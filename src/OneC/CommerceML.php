<?php

namespace Flamix\CommerceML\OneC;

use SimpleXMLElement;

/** Builds CommerceML XML from arrays and translates tag names between English and the Russian of 1C. */
class CommerceML
{
    private string $version = 'FlamixSimplyCommerceML';
    private SimpleXMLElement $data;
    private string $lang;

    public function __construct(string $lang = 'en')
    {
        $this->lang = $lang;
    }

    public static function init(string $lang = 'en'): CommerceML
    {
        return new CommerceML($lang);
    }

    public function setLang(string $lang): CommerceML
    {
        $this->lang = $lang;
        return $this;
    }

    /** @return array|SimpleXMLElement The array goes through json_encode(): a single child is an object, not a list of one. */
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

    /** @throws \Exception */
    public function setFromString(string $file_content): CommerceML
    {
        $this->data = new SimpleXMLElement($file_content);
        return $this;
    }

    /**
     * Appends the array to the root translated to $lang; the array keys are written as given, not translated.
     *
     * @param string|null $starting_data Root XML to append to instead of the default CommercialInformation.
     * @throws \Exception
     */
    public function setArray(array $data, ?string $starting_data = null): CommerceML
    {
        $this->data = new SimpleXMLElement($this->translate($starting_data ?: $this->exportPrepareData(), $this->lang));
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
                ></CommercialInformation>';
    }

    /** A 'code' key names the tag, a 'value' key is its text, a numeric key without 'code' becomes item_<n>. */
    public static function array_to_xml(array $data, SimpleXMLElement &$xml_data): SimpleXMLElement
    {
        foreach ($data as $key => $value) {
            // 'code' because PHP keys are unique and XML repeats tags: several <price> in one <prices>.
            if (isset($value['code'])) {
                $key = $value['code'];
                unset($value['code']);
            }

            if (is_array($value) && !isset($value['value'])) {
                if (is_numeric($key))
                    $key = 'item_' . $key;

                $subNode = $xml_data->addChild($key);
                self::array_to_xml($value, $subNode);
            } else if (isset($value['value'])) {
                $xml_data->addChild("$key", mb_convert_encoding(htmlspecialchars($value['value']), 'utf-8', mb_detect_encoding($value['value'])));
            } else {
                $xml_data->addChild("$key", mb_convert_encoding(htmlspecialchars($value), 'utf-8', mb_detect_encoding($value)));
            }
        }

        return $xml_data;
    }

    /** Renames the tags of translate.php: 'en' turns Russian into English, any other value English into Russian. */
    public function translate(string $content, string $lang_to = 'en'): string
    {
        $translate_words = include(__DIR__ . '/../translate.php');
        if ($lang_to !== 'en')
            $translate_words = array_flip($translate_words);

        $plain = $tags = [];
        foreach ($translate_words as $translate_word_to => $translate_word_from) {
            // If we use @ - replace anywhere: root tag and its attributes
            if (str_contains($translate_word_from, '@') || str_contains($translate_word_to, '@')) {
                $plain[str_replace('@', '', $translate_word_from)] = str_replace('@', '', $translate_word_to);
                continue;
            }

            $tags[$translate_word_from] = $translate_word_to;
        }

        $content = str_replace(array_keys($plain), $plain, $content);

        // Whole tag names only: "Свойства>" must not rewrite the end of "ЗначенияСвойства>", tags with attributes count too.
        $pattern = '/<(\/?)(' . implode('|', array_map(fn($tag) => preg_quote($tag, '/'), array_keys($tags))) . ')(?=[\s\/>])/u';
        return preg_replace_callback($pattern, fn($match) => '<' . $match[1] . $tags[$match[2]], $content);
    }

    /** Translates the file and writes it back in place. */
    public function translateFile(string $filepath, string $lang_to = 'en'): string
    {
        $content = @file_get_contents($filepath);
        $content = $this->translate($content, $lang_to);
        @file_put_contents($filepath, $content);
        return $content;
    }

    /** Drops <tag/>; only [a-zA-Z] names match, so run it before translating to Russian. */
    public function clearEmptyTags(string $content): string
    {
        return preg_replace("/<[a-zA-Z]+\/>/", "", $content);
    }
}
