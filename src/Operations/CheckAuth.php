<?php
namespace Flamix\CommerceML\Operations;

class CheckAuth
{
    public static function printPhpSession()
    {
        $session_id = self::getSessionId(true);

        commerceml_response([
            'success',
            'PHPSESSID',
            md5($session_id),
            'sessid=' . $session_id,
            'timestamp=' . time(),
        ]);
    }

    private static function getSessionId($force = false)
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        if ($force)
            session_regenerate_id();

        return session_id();
    }

    public function checkByPhpSessionId(): bool
    {
        $request_session_id = $_SERVER['HTTP_PHPSESSID'] ?? '';
        if (empty($request_session_id))
            commerceml_response_by_type('failure', 'Empty PHPSESSID in header!');

        if ($request_session_id !== md5($this->getSessionId()))
            commerceml_response_by_type('failure', 'Wrong PHPSESSID in header!');

        return true;
    }
}