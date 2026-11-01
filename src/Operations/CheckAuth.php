<?php
namespace Flamix\CommerceML\Operations;

/** The checkauth handshake: issues the PHP session id and checks it on every later mode. */
class CheckAuth
{
    /** Regenerates the session and prints success, PHPSESSID, the id, sessid and timestamp. */
    public static function printPhpSession()
    {
        $session_id = self::getSessionId(true);
        $_SESSION['commerceml_auth'] = true;

        commerceml_response([
            'success',
            'PHPSESSID',
            $session_id,
            'sessid=' . md5($session_id),
            'timestamp=' . time(),
        ]);
    }

    /** @return false|string */
    private static function getSessionId($force = false)
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        if ($force)
            session_regenerate_id(true);

        return session_id();
    }

    /** Ends the request with failure unless the PHPSESSID cookie is a session that passed checkauth. */
    public function checkByPhpSessionId(): bool
    {
        $cookie_session_id = $_COOKIE['PHPSESSID'] ?? '';
        if (empty($cookie_session_id))
            commerceml_response_by_type('failure', 'Empty PHPSESSID in Cookie!');

        if ($cookie_session_id !== $this->getSessionId())
            commerceml_response_by_type('failure', 'Wrong PHPSESSID in Cookie!');

        // session_start() adopts any cookie as the id, so the cookie match alone proves nothing.
        if (empty($_SESSION['commerceml_auth']))
            commerceml_response_by_type('failure', 'Session did not pass checkauth!');

        return true;
    }
}