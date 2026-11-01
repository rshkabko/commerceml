<?php

namespace Flamix\CommerceML\Tests\Feature;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;

/** The exchange as Bitrix24 drives it: HTTP requests against a real `php -S` running Shop::start(). */
class ExchangeTest extends TestCase
{
    private static $server;
    private static string $url;
    private static string $workdir;
    private ?string $cookie = null;

    public static function setUpBeforeClass(): void
    {
        self::$workdir = sys_get_temp_dir() . '/commerceml-test-' . getmypid();
        @mkdir(self::$workdir, 0755, true);

        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        self::$url = 'http://127.0.0.1:' . $port . '/';

        // Not ReflectionClass(ClassLoader): Herd prepends a phar with its own copy of that class.
        $vendor = key(array_filter(ClassLoader::getRegisteredLoaders(), fn($loader) => $loader->findFile(\Flamix\CommerceML\Init::class)));
        self::$server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, dirname(__DIR__) . '/Fixtures/server.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            self::$workdir,
            ['COMMERCEML_AUTOLOAD' => $vendor . '/autoload.php', 'COMMERCEML_WORKDIR' => self::$workdir]
        );

        for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++)
            usleep(100000);
    }

    public static function tearDownAfterClass(): void
    {
        proc_terminate(self::$server);
        exec('rm -rf ' . escapeshellarg(self::$workdir));
    }

    protected function setUp(): void
    {
        exec('rm -rf ' . escapeshellarg(self::$workdir . '/commerceml') . ' ' . escapeshellarg(self::$workdir . '/calls.json'));
    }

    public function test_checkauth_returns_a_session_for_valid_credentials()
    {
        $lines = $this->login();

        $this->assertSame('success', $lines[0]);
        $this->assertSame('PHPSESSID', $lines[1]);
        $this->assertSame($lines[2], $this->cookie);
    }

    public function test_checkauth_fails_on_a_wrong_password()
    {
        $this->assertSame(['failure', 'Error: Bad password!'], $this->call(['type' => 'catalog', 'mode' => 'checkauth'], null, 'login:wrong'));
    }

    public function test_an_unknown_type_is_rejected_before_auth()
    {
        $this->assertSame(['failure', 'Type must be catalog or get_catalog, sale given!'], $this->call(['type' => 'sale', 'mode' => 'checkauth']));
    }

    public function test_query_without_a_session_cookie_fails()
    {
        $this->assertSame(['failure', 'Empty PHPSESSID in Cookie!'], $this->call(['type' => 'get_catalog', 'mode' => 'query']));
    }

    public function test_mode_requires_a_session_from_checkauth()
    {
        $this->cookie = 'session0without0checkauth';

        $this->assertSame(['failure', 'Session did not pass checkauth!'], $this->call(['type' => 'get_catalog', 'mode' => 'query']));
    }

    public function test_query_exports_the_classifier_then_one_products_page_per_request()
    {
        $this->login();

        $step0 = $this->call(['type' => 'get_catalog', 'mode' => 'query']);
        $this->assertSame('progress', $step0[0]);
        $this->assertSame('Uploaded 0 of 2 products from the site', $step0[1]);
        $classifier = $this->xml($step0);
        $this->assertSame('TestShop', (string)$classifier->Классификатор->Ид);
        $this->assertSame('Hoodies', (string)$classifier->Классификатор->Группы->Группа->Группы->Группа->Наименование);
        $this->assertSame('pa_color', (string)$classifier->Классификатор->Свойства->Свойство->Ид);

        $step1 = $this->call(['type' => 'get_catalog', 'mode' => 'query']);
        $this->assertSame('Uploaded 1 of 2 products from the site', $step1[1]);
        $product = $this->xml($step1)->Каталог->Товары->Товар;
        $this->assertSame('product-1', (string)$product->Ид);
        $this->assertSame('SKU-1', (string)$product->Артикул);
        $this->assertSame('cat-hoodies', (string)$product->Группы->Ид);
        $this->assertSame('1001', (string)$product->Цены->Цена->ЦенаЗаЕдиницу);
        $this->assertCount(0, $product->Описание, 'empty fields are dropped');

        $this->assertSame('product-2', (string)$this->xml($this->call(['type' => 'get_catalog', 'mode' => 'query']))->Каталог->Товары->Товар->Ид);
        $this->assertSame(['success', 'Uploaded 2 of 2 products from the site'], $this->call(['type' => 'get_catalog', 'mode' => 'query']));
        $this->assertSame('progress', $this->call(['type' => 'get_catalog', 'mode' => 'query'])[0], 'the next query starts over from the classifier');
    }

    public function test_product_property_values_are_exported()
    {
        $this->markTestIncomplete('Converter::prepareToCommerceMLStructure() stores the attrProperty_value()/attrCategory() result and unsets the same key on the next line: property_value and category never reach the XML.');

        $this->login();
        $this->call(['type' => 'get_catalog', 'mode' => 'query']);

        $this->assertSame('Red', (string)$this->xml($this->call(['type' => 'get_catalog', 'mode' => 'query']))->Каталог->Товары->Товар->ЗначенияСвойств->ЗначенияСвойства->Значение);
    }

    public function test_uploaded_offers_reach_the_rests_handler_and_the_file_is_deleted()
    {
        $this->login();

        $this->assertSame(['success', 'File uploaded!'], $this->upload('offers.xml', file_get_contents(dirname(__DIR__) . '/Fixtures/offers.xml')));
        $this->assertSame(['progress', 'Import and delete file offers.xml'], $this->call(['type' => 'catalog', 'mode' => 'import']));
        $this->assertSame(['success', 'All files was imported!'], $this->call(['type' => 'catalog', 'mode' => 'import']));

        $this->assertSame(['handler' => 'rests', 'product_id' => 'product-1', 'data' => ['id' => 'main', 'quantity' => '7']], $this->calls()[0]);
    }

    public function test_an_uploaded_zip_is_extracted_on_the_first_import_request()
    {
        $zipPath = self::$workdir . '/offers.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile(dirname(__DIR__) . '/Fixtures/offers.xml', 'offers.xml');
        $zip->addFromString('readme.txt', 'not an exchange file');
        $zip->close();

        $this->login();
        $this->upload('offers.zip', file_get_contents($zipPath));

        $this->assertSame(['progress', 'Extracting zip file offers.zip'], $this->call(['type' => 'catalog', 'mode' => 'import']));
        $this->assertSame(['offers.xml'], array_values(array_diff(scandir(self::$workdir . '/commerceml/import'), ['.', '..'])));
        $this->assertSame(['progress', 'Import and delete file offers.xml'], $this->call(['type' => 'catalog', 'mode' => 'import']));
        $this->assertSame('product-1', $this->calls()[0]['product_id']);
    }

    public function test_every_warehouse_of_an_offer_reaches_the_rests_handler()
    {
        $this->markTestIncomplete('Import::import() reads Остатки.Остаток.Склад, which is absent when an offer has several <Остаток>: multi-warehouse rests never reach restsHandler.');

        $this->login();
        $this->upload('offers.xml', file_get_contents(dirname(__DIR__) . '/Fixtures/offers.xml'));
        $this->call(['type' => 'catalog', 'mode' => 'import']);

        $this->assertSame('product-2', $this->calls()[1]['product_id']);
    }

    public function test_offer_prices_reach_the_prices_handler()
    {
        $this->markTestIncomplete('Import::import() reads $entity[\'Цены\'][\'Цена\'] after translateFile() renamed them to prices/price: pricesHandler and productsHandler are never called.');

        $this->login();
        $this->upload('offers.xml', file_get_contents(dirname(__DIR__) . '/Fixtures/offers.xml'));
        $this->call(['type' => 'catalog', 'mode' => 'import']);

        $this->assertContains('prices', array_column($this->calls(), 'handler'));
    }

    public function test_upload_accepts_only_a_bare_xml_or_zip_name()
    {
        $this->login();

        $this->assertSame(['failure', 'Only .xml and .zip files are accepted!'], $this->upload('offers.txt', 'x'));
        $this->assertSame(['success', 'File uploaded!'], $this->upload('nested/offers.xml', '<xml/>'));
        $this->assertSame(['offers.xml'], array_values(array_diff(scandir(self::$workdir . '/commerceml/import'), ['.', '..'])));
    }

    public function test_init_clears_the_import_directory()
    {
        $this->login();
        $this->upload('offers.xml', '<xml/>');

        $this->assertSame(['success', 'Removed directory: import'], $this->call(['type' => 'catalog', 'mode' => 'init']));
        $this->assertSame(['success', 'All files was imported!'], $this->call(['type' => 'catalog', 'mode' => 'import']));
    }

    private function login(): array
    {
        $lines = $this->call(['type' => 'catalog', 'mode' => 'checkauth'], null, 'login:secret');
        $this->cookie = $lines[2] ?? null;
        return $lines;
    }

    private function upload(string $filename, string $content): array
    {
        return $this->call(['type' => 'catalog', 'mode' => 'file', 'filename' => $filename], $content);
    }

    private function call(array $query, ?string $body = null, ?string $basicAuth = null): array
    {
        $headers = [];
        if ($this->cookie)
            $headers[] = 'Cookie: PHPSESSID=' . $this->cookie;
        if ($basicAuth)
            $headers[] = 'Authorization: Basic ' . base64_encode($basicAuth);

        $response = file_get_contents(self::$url . '?' . http_build_query($query), false, stream_context_create(['http' => [
            'method' => $body === null ? 'GET' : 'POST',
            'header' => array_merge($headers, ['Content-Type: application/octet-stream']),
            'content' => (string)$body,
            'ignore_errors' => true,
        ]]));

        return explode(PHP_EOL, $response);
    }

    private function xml(array $lines): \SimpleXMLElement
    {
        return new \SimpleXMLElement(implode(PHP_EOL, array_slice($lines, 2)));
    }

    private function calls(): array
    {
        return json_decode(@file_get_contents(self::$workdir . '/calls.json') ?: '[]', true);
    }
}
