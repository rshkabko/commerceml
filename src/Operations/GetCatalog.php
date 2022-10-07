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
        $this->setElementsCount($product_callback::productCount());
        $description = 'Uploaded ' . $this->currentElement() . ' of ' . $this->getElementsCount() . ' products from the site';

        if ($this->isStart()) {
            // Step 0: Property && Categories
            $categories = Converter::replaceToCyrillic($category_callback::get(), Converter::getTranslate('category'), 'categories');
            $attributes = Converter::replaceToCyrillic($attribute_callback::get(), Converter::getTranslate('property'));

            $CommerML = tap(new CommerceML, function ($instance) use ($attributes, $categories) {
                $instance->setArray([
                    Converter::getTranslate('classifier') => [
                        Converter::getTranslate('id') => commerceml_config('exchange.iblock_id', 'IBLOCK_ID'),
                        Converter::getTranslate('name') => commerceml_config('exchange.iblock_name', 'IBLOCK_NAME'),
                        Converter::getTranslate('properties') => $attributes,
                        Converter::getTranslate('categories') => $categories,
                    ]
                ]);
            });

            $CommerML->saveToFile(Files::exchange('import')->getPath('import_' . $this->currentPage() . '.xml'));

            $this->nextPageProgress($description . PHP_EOL . $CommerML->getXML());
        } else if (!$this->isFinish()) {
            // Step 1-10000:  Products
            $products = Converter::replaceToCyrillic($product_callback::get($this->currentPage()), Converter::getTranslate('product'));
            $CommerML = tap(new CommerceML, function ($instance) use ($products) {
                $instance->setArray([
                    Converter::getTranslate('catalog') => [
                        Converter::getTranslate('id') => commerceml_config('exchange.iblock_id', 'IBLOCK_ID'),
                        Converter::getTranslate('classifier_id') => commerceml_config('exchange.iblock_id', 'IBLOCK_ID'),
                        Converter::getTranslate('name') => commerceml_config('exchange.iblock_name', 'IBLOCK_NAME'),
                        Converter::getTranslate('products') => $products
                    ]
                ]);
            });

            $CommerML->saveToFile(Files::exchange('import')->getPath('import_' . $this->currentPage() . '.xml'));

            $this->nextPageProgress($description . PHP_EOL . $CommerML->getXML());
        }

        $this->finish();
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
}