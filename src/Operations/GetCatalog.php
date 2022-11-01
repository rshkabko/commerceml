<?php

namespace Flamix\CommerceML\Operations;

use Flamix\CommerceML\OneC\Converter;
use Flamix\CommerceML\OneC\CommerceML;
use Flamix\CommerceML\Operations\Traits\SessionPaginator;

class GetCatalog
{
    use SessionPaginator;

    /**
     * Main query functional
     *
     * @param string $product_callback
     * @param string $category_callback
     * @param string $attribute_callback
     * @return void
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
            // Step 0: Property && Categories as array with special structure
            $CommerceML = $this->step_zero($category_callback, $attribute_callback);

            // dd($CommerceML->getData(true)); // Debug
            $this->savePreparedAndSwitchPage($CommerceML, $description);
        } else if (!$this->isFinish()) {
            // Step 1+: Products
            $CommerceML = $this->step_products($product_callback);

            // dd($CommerceML->getData(true)); // Debug
            $this->savePreparedAndSwitchPage($CommerceML, $description);
        }

        $this->finish();
    }

    /**
     * Generating all categories and property (attributes)
     *
     * @param string $category_callback
     * @param string $attribute_callback
     * @return CommerceML
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
     * Generate products with basic property
     *
     * Using steps 30
     *
     * @param string $product_callback
     * @return CommerceML
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

    /**
     * Set +1 to current page
     * Print PROGRESS status
     *
     * @param string $description
     * @return void
     */
    protected function nextPageProgress(string $description)
    {
        $this->setNextPage();
        commerceml_response_by_type('progress', $description);
    }

    /**
     * Set ZERO page to session
     * Print SUCCESS
     *
     * @return void
     */
    protected function finish()
    {
        $this->setPage(0);
        commerceml_response_by_type('success', 'Uploaded ' . $this->getElementsCount() . ' of ' . $this->getElementsCount() . ' products from the site');
    }

    /**
     * Make all needed with XML Data.
     *
     * 1. clearEmptyTags, ex remove empty <catalog/>, etc.
     * 2. Translate for compatibility.
     * 3. Save to file for debug.
     *
     * @param CommerceML $CommerceML
     * @param $description
     * @return void
     * @throws \Exception
     */
    protected function savePreparedAndSwitchPage(CommerceML $CommerceML, $description)
    {
        $exportFile = Files::exchange(commerceml_config('dir_export', 'export'))->getPath('import_' . $this->currentPage() . '.xml');
        $xml_content = $CommerceML->translate($CommerceML->clearEmptyTags($CommerceML->getXML()), 'ru');
        file_put_contents($exportFile, $xml_content); // Save to file for debug, because our plugin take info from output
        $this->nextPageProgress($description . PHP_EOL . $xml_content);
    }
}