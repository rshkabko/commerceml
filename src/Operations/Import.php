<?php

namespace Flamix\CommerceML\Operations;

use Flamix\CommerceML\OneC\CommerceML;
use Flamix\CommerceML\OneC\Converter;

class Import
{
    private string $dir = 'import';

    public function __construct()
    {
        commerceml_log('Import init with dir: ' . $this->dir);
    }

    /**
     * Unzip our exchange file and delete
     *
     * @param string $filename
     * @return bool
     * @throws \Exception
     */
    public function unzipAndDelete(string $filename): bool
    {
        commerceml_log('Start extracting file ' . $filename . ' and delete it!');
        return Files::exchange($this->dir)->extract($filename)->deleteFile($filename)->exist($filename);
    }

    /**
     * Scan dir and try to find .zip files
     * If found - extract and delete
     *
     * @return void
     * @throws \Exception
     */
    public function unzipAndDeleteAllFilesInFolderBySteps(): void
    {
        $zip_files = Files::exchange($this->dir)->find('.zip');
        $is_unzipped = count($zip_files) === 0;

        if (!$is_unzipped) {
            foreach ($zip_files as $zip_file) {
                $this->unzipAndDelete($zip_file);
                commerceml_response_by_type('progress', 'Extracting zip file ' . $zip_file);
            }
        }
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

    /**
     * Scan dir, finding .xml files and run import() method to them
     *
     * @param $rests_fn
     * @param $prices_fn
     * @param $products_fn
     * @return void
     * @throws \Exception
     */
    public function importAllFilesInFolderBySteps($rests_fn, $prices_fn, $products_fn): void
    {
        $folder = Files::exchange($this->dir);
        $xml_files = $folder->find('.xml');
        if (!count($xml_files))
            commerceml_response_by_type('success', 'All files was imported!');

        foreach ($xml_files as $xml_file) {
            // Calling our imports handlers...
            $this->import($xml_file, $rests_fn, $prices_fn, $products_fn);
            $folder->deleteFile($xml_file);
            commerceml_response_by_type('progress', 'Import and delete file ' . $xml_file);
        }
    }
}