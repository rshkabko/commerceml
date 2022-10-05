<?php

namespace Flamix\Sync\Operations;

use Flamix\Sync\Woo\Categories;
use Flamix\Sync\Woo\Attributes;
use Flamix\Sync\Woo\Products;
use Flamix\Sync\OneC\Converter;
use Flamix\Sync\OneC\CommerceML;
use Flamix\Sync\Operations\Traits\SessionPaginator;

class GetCatalog
{
    use SessionPaginator;

    private object $receiver;

    public function __construct($receiver)
    {
        $this->receiver = $receiver;
    }

    public function query()
    {
        $this->setElementsCount(Products::productCount());
        $description = 'Uploaded ' . $this->currentElement() . ' of ' . $this->getElementsCount() . ' products from the site';

        if ($this->isStart()) {
            // Step 0: Property && Categories
            $categories = Converter::replaceToCyrillic(Categories::get(), Converter::getTranslate('category'), 'categories');
            $attributes = Converter::replaceToCyrillic(Attributes::get(), Converter::getTranslate('properties'));

            $CommerML = tap(new CommerceML, function ($instance) use ($attributes, $categories) {
                $instance->setArray([
                    Converter::getTranslate('properties') => $attributes,
                    Converter::getTranslate('categories') => $categories,
                ]);
            });

            $CommerML->saveToFile(Files::exchange('import')->getPath('import_' . $this->currentPage() . '.xml'));

            $this->nextPageProgress($description . PHP_EOL . $CommerML->getXML());
        } else if (!$this->isFinish()) {
            // Step 1-10000:  Products
            $products = Products::getPerPage($this->currentPage());
            $products = Converter::replaceToCyrillic($products, Converter::getTranslate('product'));
            $CommerML = tap(new CommerceML, function ($instance) use ($products) {
                $instance->setArray([Converter::getTranslate('products') => $products]);
            });

            $CommerML->saveToFile(Files::exchange('import')->getPath('import_' . $this->currentPage() . '.xml'));

            $this->nextPageProgress($description . PHP_EOL . $CommerML->getXML());
        }

        // Finish
        $this->setPage(0);
        Helpers::sendResponseByType('success', 'Uploaded ' . $this->getElementsCount() . ' of ' . $this->getElementsCount() . ' products from the site');
    }

    private function nextPageProgress(string $description)
    {
        $this->setNextPage();
        Helpers::sendResponseByType('progress', $description);
    }
}