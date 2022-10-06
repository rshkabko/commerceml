<?php

namespace Flamix\CommerceML\Contracts;

interface ICanWorkWithExternalID
{
    public static function editExternalID(): bool;
    public static function saveExternalID(int $term_id, string $value);
    public static function getExternalID(int $term_id);
    public static function getOrGenerateIfNotExist(int $term_id): string;
}