<?php

namespace Flamix\CommerceML\Contracts;

interface ExportImport
{
    public static function get(int $page, array $params = []): array;
    public static function set(array $data = []): array;
}