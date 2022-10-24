<?php
namespace Flamix\CommerceML\Operations;

class CheckAuth
{
    /**
     * Generate new session_id and print result
     * New session_id will be automatically located in Cookies
     *
     * @return void
     */
    public static function printPhpSession()
    {
        $session_id = self::getSessionId(true);

        commerceml_response([
            'success',
            'PHPSESSID',
            $session_id,
            'sessid=' . md5($session_id),
            'timestamp=' . time(),
        ]);
    }

    /**
     * Return session_id
     *
     * @param $force Generate new session_id before returning
     * @return false|string
     */
    private static function getSessionId($force = false)
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        if ($force)
            session_regenerate_id();

        return session_id();
    }

    /**
     * PHPSESSID located in Cookies file
     *
     * @return bool
     */
    public function checkByPhpSessionId(): bool
    {
        $cookie_session_id = $_COOKIE['PHPSESSID'] ?? '';
        if (empty($cookie_session_id))
            commerceml_response_by_type('failure', 'Empty PHPSESSID in Cookie!');

        if ($cookie_session_id !== $this->getSessionId())
            commerceml_response_by_type('failure', 'Wrong PHPSESSID in Cookie!');

        return true;
    }
}