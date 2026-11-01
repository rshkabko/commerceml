<?php

namespace Flamix\CommerceML\Operations;

use Flamix\CommerceML\OneC\Converter;
use Flamix\CommerceML\OneC\CommerceML;
use Flamix\CommerceML\Operations\Traits\SessionPaginator;

/** The get_catalog export: page 0 is the classifier, pages 1..N are products, the session holds the page. */
class GetCatalog
{
    use SessionPaginator;

    /**
     * Prints the current step as progress, or success and a reset to page 0 after the last one.
     *
     * @throws \Exception
     */
    public function query(string $product_callback, string $category_callback, string $attribute_callback)
    {
        $this->setElementsCount(call_user_func([$product_callback, 'productCount']));
        $description = 'Uploaded ' . $this->currentElement() . ' of ' . $this->getElementsCount() . ' products from the site';
        commerceml_log($description, [
            'current_page' => $this->currentPage(),
            'max_page' => $this->calculatePage(),
            'next_page' => $this->getNextPage()
        ]);

        if ($this->isStart()) {
            $CommerceML = $this->step_zero($category_callback, $attribute_callback);
            $this->savePreparedAndSwitchPage($CommerceML, $description);
        } else if (!$this->isFinish()) {
            $CommerceML = $this->step_products($product_callback);
            $this->savePreparedAndSwitchPage($CommerceML, $description);
        }

        $this->finish();
    }

    /**
     * The classifier: categories and properties.
     *
     * @throws \Exception
     */
    protected function step_zero(string $category_callback, string $attribute_callback): CommerceML
    {
        $categories = Converter::prepareToCommerceMLStructure(call_user_func([$category_callback, 'get']), 'category', 'categories');
        $attributes = Converter::prepareToCommerceMLStructure(call_user_func([$attribute_callback, 'get']), 'property');

        return CommerceML::init()->setArray([
            'classifier' => [
                'id' => commerceml_config('iblock_id', 'IBLOCK_ID'),
                'name' => commerceml_config('iblock_name', 'IBLOCK_NAME'),
                'categories' => $categories,
                'properties' => $attributes,
            ]
        ]);
    }

    /**
     * One page of products, items_per_page long.
     *
     * @throws \Exception
     */
    protected function step_products(string $product_callback): CommerceML
    {
        $products = Converter::prepareToCommerceMLStructure(call_user_func([$product_callback, 'get'], $this->currentPage()), 'product');
        return CommerceML::init()->setArray([
            'catalog' => [
                'id' => commerceml_config('iblock_id', 'IBLOCK_ID'),
                'classifier_id' => commerceml_config('iblock_id', 'IBLOCK_ID'),
                'name' => commerceml_config('iblock_name', 'IBLOCK_NAME'),
                'products' => $products
            ]
        ]);
    }

    protected function nextPageProgress(string $description)
    {
        $this->setNextPage();
        commerceml_response_by_type('progress', $description);
    }

    protected function finish()
    {
        $this->setPage(0);
        commerceml_response_by_type('success', 'Uploaded ' . $this->getElementsCount() . ' of ' . $this->getElementsCount() . ' products from the site');
    }

    /**
     * Drops empty tags, translates to Russian, saves export/import_<page>.xml and prints it as progress.
     *
     * @throws \Exception
     */
    protected function savePreparedAndSwitchPage(CommerceML $CommerceML, $description)
    {
        $exportFile = Files::exchange(commerceml_config('dir_export', 'export'))->getPath('import_' . $this->currentPage() . '.xml');
        $xml_content = $CommerceML->translate($CommerceML->clearEmptyTags($CommerceML->getXML()), 'ru');
        // A copy for debugging only: Bitrix24 reads the XML from the response body, not this file.
        file_put_contents($exportFile, $xml_content);
        $this->nextPageProgress($description . PHP_EOL . $xml_content);
    }
}