<?php

if (!function_exists('commerceml_log')) {
    function commerceml_log(string $message, array $context = []): \Monolog\Logger
    {
        return flamix_log($message, $context, 'commerceml');
    }
}

if (!function_exists('commerceml_config')) {
    function commerceml_config(string $key, $default = null)
    {
        $config = include FLAMIX_EXCHANGE_DIR_PATH . '/config.php';
        return $config[$key] ?? (($default === null) ? $config : $default);
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
        commerceml_log('In 1C was send a response of the type: ' . $type);

        foreach (commerceml_config('response_header', ['Content-Type' => 'Content-Type: text/plain; charset=utf-8']) as $header)
            header($header);

        if (in_array($type, ['success', 'progress']))
            commerceml_response([$type, $description]);

        commerceml_response(['failure', $description]);
    }
}
