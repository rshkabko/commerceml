<?php

namespace Flamix\Sync\Operations;

class CheckAuth
{
    private object $receiver;
    private string $login;
    private string $pwd;

    public function __construct($receiver)
    {
        $this->receiver = $receiver;
//        var_dump($_SERVER);
    }

    public function check()
    {
        if ($this->login !== $this->receiver->core()->getOptions('portal_url', ''))
            throw new \Exception('Portal domain not same');

        if ($this->pwd !== $this->receiver->core()->getOptions('secret_token', ''))
            throw new \Exception('Portal secret token not same');

        return $this;
    }

    public function setCredentials(string $login, string $pwd)
    {
        $this->login = $login;
        $this->pwd = $pwd;
        return $this;
    }

    public function printPhpSession()
    {
        $session_id = $this->getSessionId(true);

        Helpers::response([
            'success',
            'PHPSESSID',
            md5($session_id),
            'sessid=' . $session_id,
            'timestamp=' . time(),
        ]);
    }

    private function getSessionId($force = false)
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
            Helpers::sendResponseByType('failure', 'Empty PHPSESSID in header!');

        if ($request_session_id !== md5($this->getSessionId()))
            Helpers::sendResponseByType('failure', 'Wrong PHPSESSID in header!');

        return true;
    }
}