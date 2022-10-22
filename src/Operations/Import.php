<?php

namespace Flamix\CommerceML\Operations;

use Flamix\CommerceML\OneC\CommerceML;
use Flamix\CommerceML\OneC\Converter;

class Import
{
    private string $dir = 'upload';

    public function __construct()
    {
        commerceml_log('Import init with dir: ' . $this->dir);
    }

    public function checkIsUnzipped(string $filename_request): bool
    {
        $is_zip = str_contains($filename_request, '.zip');
        return !$is_zip || !Files::exchange('upload')->exist($filename_request);
    }

    /**
     * Unzip our exchange file and delete
     *
     * @param string $filename_request
     * @return bool
     * @throws \Exception
     */
    public function unzipAndDelete(string $filename_request): bool
    {
        commerceml_log('Start extracting file ' . $filename_request . ' and delete it!');
        return Files::exchange($this->dir)->extract($filename_request)->deleteFile($filename_request)->exist($filename_request);
    }

    /**
     * Get products from file if exist
     *
     * @param string $filename
     * @return array
     * @throws \Exception
     */
    public function parseEntities(string $filename): array
    {
        $content = Files::exchange($this->dir)->content($filename);
        $products = (new CommerceML)->setFromString($content)->getData(true)[Converter::getTranslate('offersPackage')][Converter::getTranslate('offers')][Converter::getTranslate('offer')] ?? [];
        if (count($products) === 1 || isset($products['Ид'])) // TODO: translate
            $products = [$products];

        commerceml_log('Get products from file ' . $filename, $products);
        return $products;
    }


    /**
     * Parse file and run callback with our products entities (rests, prices or general products attribute)
     *
     * @param string $filename
     * @param $rests_fn
     * @param $prices_fn
     * @param $products_fn
     * @return bool
     * @throws \Exception
     */
    public function import(string $filename, $rests_fn, $prices_fn, $products_fn): bool
    {
        $entities = $this->parseEntities($filename);
        if (empty($entities))
            throw new \Exception('Empty offers in file: ' . $filename);

        foreach ($entities as $entity) {
            $product_id = $entity['Ид'];

            /***** | Run handles | ****/
            // Rests
            $rests = $entity['Остатки']['Остаток']['Склад'] ?? [];
            if ($product_id && !empty($rests))
                call_user_func($rests_fn, $product_id, $rests);

            // Prices
            $prices = $entity['Цены']['Цена'] ?? [];
            if ($product_id && !empty($prices))
                call_user_func($prices_fn, $product_id, $prices);

            // Products
            $products = $entity['Товары']['Товар'] ?? [];
            if ($product_id && !empty($products))
                call_user_func($products_fn, $product_id, $products);
        }

        return true;
    }
}