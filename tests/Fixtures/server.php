<?php

// Front controller for `php -S`: every request is one exchange call, and every response ends in die().
require getenv('COMMERCEML_AUTOLOAD');
require __DIR__ . '/Shop.php';

ini_set('session.save_path', getenv('COMMERCEML_WORKDIR'));

\Flamix\Plugin\Init::init(__DIR__, 'FLAMIX_EXCHANGE_PLUGIN')->setLogsPath(getenv('COMMERCEML_WORKDIR'));
\Flamix\CommerceML\Init::init(__DIR__);

\Flamix\CommerceML\Tests\Fixtures\Shop::start();
