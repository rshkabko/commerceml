<?php

if (!function_exists('tap')) {
    function tap($value, callable $callback)
    {
        $callback($value);
        return $value;
    }
}

if (!function_exists('dd')) {
    function dd(...$var)
    {
        var_dump($var);
        exit();
    }
}

if (!function_exists('commerceml_log')) {
    function commerceml_log(string $message, array $context = []): \Monolog\Logger
    {
        $date = date('Y-m-d');
        $log = new \Monolog\Logger('commerceml');
        $log->pushHandler(new \Monolog\Handler\StreamHandler(FLAMIX_EXCHANGE_DIR_PATH . '/logs/' . $date . '-info-' . md5($date) . '.log', \Monolog\Logger::DEBUG));
        $log->info($message, $context);
        return $log;
    }
}

if (!function_exists('commerceml_response')) {
    function commerceml_response($result)
    {
        echo (is_array($result)) ? implode(PHP_EOL, $result) : $result;
        die();
    }
}

if (!function_exists('commerceml_response_by_type')) {
    function commerceml_response_by_type(string $type = 'failure', string $description = '')
    {
        commerceml_log('In 1C was send a response of the type:' . $type);

        foreach (commerceml_config('response_header', ['Content-Type' => 'Content-Type: text/plain; charset=utf-8']) as $header)
            header($header);

        if (in_array($type, ['success', 'progress']))
            commerceml_response([$type, $description]);

        commerceml_response(['failure', $description]);
    }
}

if (!function_exists('commerceml_config')) {
    function commerceml_config(string $key, $default = null)
    {
        $config = include FLAMIX_EXCHANGE_DIR_PATH . '/config.php';
        return $config[$key] ?? (($default === null) ? $config : $default);
    }
}
