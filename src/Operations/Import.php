<?php

namespace Flamix\CommerceML\Operations;

use Flamix\CommerceML\OneC\CommerceML;
use Flamix\CommerceML\OneC\Converter;

/** The catalog import: unzips uploads, translates each XML to English and calls the handlers per offer. */
class Import
{
    public function __construct()
    {
        commerceml_log('Import init with dir: ' . $this->dir());
    }

    public function dir(): string
    {
        return commerceml_config('dir_import', 'import');
    }

    /** @throws \Exception */
    public function unzipAndDelete(string $filename): bool
    {
        commerceml_log('Start extracting file ' . $filename . ' and delete it!');
        return Files::exchange($this->dir())->extract($filename)->deleteFile($filename)->exist($filename);
    }

    /**
     * Extracts the first .zip and ends the request with progress; returns only when there is none.
     *
     * @throws \Exception
     */
    public function unzipAndDeleteAllFilesInFolderBySteps(): void
    {
        $zip_files = Files::exchange($this->dir())->find('.zip');
        $is_unzipped = count($zip_files) === 0;

        if (!$is_unzipped) {
            foreach ($zip_files as $zip_file) {
                $this->unzipAndDelete($zip_file);
                commerceml_response_by_type('progress', 'Extracting zip file ' . $zip_file);
            }
        }
    }

    /**
     * The offers of an English-translated file, always as a list.
     *
     * @throws \Exception
     */
    public function parseEntities(string $filename): array
    {
        $content = Files::exchange($this->dir())->content($filename);
        $products = (new CommerceML)->setFromString($content)->getData(true)['offersPackage']['offers']['offer'] ?? [];

        // json_encode() turns a single <offer> into an object, not a list of one.
        if (count($products) === 1 || isset($products['id']))
            $products = [$products];

        commerceml_log('Get products from file ' . $filename, $products);
        return $products;
    }

    /**
     * Calls each handler with the offer id and its part of the offer, when that part is not empty.
     *
     * @throws \Exception
     */
    public function import(string $filename, $rests_fn, $prices_fn, $products_fn): bool
    {
        $entities = $this->parseEntities($filename);
        if (empty($entities))
            throw new \Exception('Empty offers in file: ' . $filename);

        foreach ($entities as $entity) {
            $product_id = $entity['id'];

            $rests = $entity['Остатки']['Остаток']['Склад'] ?? [];
            if ($product_id && !empty($rests))
                call_user_func($rests_fn, $product_id, $rests);

            $prices = $entity['Цены']['Цена'] ?? [];
            if ($product_id && !empty($prices))
                call_user_func($prices_fn, $product_id, $prices);

            $products = $entity['Товары']['Товар'] ?? [];
            if ($product_id && !empty($products))
                call_user_func($products_fn, $product_id, $products);
        }

        return true;
    }

    /**
     * Imports and deletes the first .xml and ends the request with progress, or with success when none is left.
     *
     * @throws \Exception
     */
    public function importAllFilesInFolderBySteps($rests_fn, $prices_fn, $products_fn): void
    {
        $folder = Files::exchange($this->dir());
        $xml_files = $folder->find('.xml');

        if (!count($xml_files))
            commerceml_response_by_type('success', 'All files was imported!');

        foreach ($xml_files as $xml_file) {
            // Rewrites the file in place: Bitrix24 sends Russian tags, parseEntities() reads English ones.
            (new CommerceML)->translateFile($folder->getPath($xml_file), 'en');
            $this->import($xml_file, $rests_fn, $prices_fn, $products_fn);
            $folder->deleteFile($xml_file);
            commerceml_response_by_type('progress', 'Import and delete file ' . $xml_file);
        }
    }
}