<?php

namespace Flamix\CommerceML\Contracts;

/** An export callback: get() returns one page of entities, set() writes imported data to one entity. */
interface ExportImport
{
    public static function get(int $page, array $params = []): array;
    public static function set(int $entity_id, array $data = []): bool;
}