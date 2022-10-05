<?php namespace Flamix\Sync;

class Helpers
{
    public static function response(array|string $result)
    {
        if(is_array($result))
            echo implode(PHP_EOL, $result);
        else
            echo $result;

        die();
    }

    public static function sendResponseByType(string $type = 'failure', string $description = '')
    {
        // TODO: Change to logger
        log()->info('In 1C was send a response of the type:' . $type);
        $headers= [
            'Content-Type' => 'Content-Type: text/plain; charset=utf-8',
        ];

        foreach($headers as $header) {
            header($header);
        }

        if(in_array($type, ['success', 'progress']))
            self::response([$type, $description]);

        self::response(['failure', $description]);
    }
}