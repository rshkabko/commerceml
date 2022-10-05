<?php

if (!function_exists('tap')) {
    function tap($value, $callback)
    {
        $callback($value);
        return $value;
    }
}

if (!function_exists('commerceml_log')) {
    function commerceml_log(string $message, array $context = []): \Monolog\Logger
    {
        $date = date('Y-m-d');
        $log = new \Monolog\Logger('commerceml');
        $log->pushHandler(new StreamHandler(FLAMIX_EXCHANGE_DIR_PATH . '/logs/' . $date . '-info-' . md5($date) . '.log', \Monolog\Logger::DEBUG));
        $log->info($message, $context);
        return $log;
    }
}

if (!function_exists('commerceml_response')) {
    function commerceml_response(array|string $result)
    {
        if(is_array($result))
            echo implode(PHP_EOL, $result);
        else
            echo $result;

        die();
    }
}

if (!function_exists('commerceml_response_by_type')) {
    function commerceml_response_by_type(string $type = 'failure', string $description = '')
    {
        commerceml_log()->info('In 1C was send a response of the type:' . $type);
        $headers = [
            'Content-Type' => 'Content-Type: text/plain; charset=utf-8',
        ];

        foreach($headers as $header)
            header($header);

        if(in_array($type, ['success', 'progress']))
            commerceml_response([$type, $description]);

        commerceml_response(['failure', $description]);
    }
}

if (!function_exists('commerceml_config')) {
    function exchange_config(string $value, $default = null)
    {
        $value = explode('.', $value, 2);
        $file = $value['0'] ?? '';
        $key = $value['1'] ?? '';

        $config = @include FLAMIX_EXCHANGE_DIR_PATH . '/config/' . $file . '.php';
        return $config[$key] ?? (($default === null) ? $config : $default);
    }
}