<?php

namespace Flamix\CommerceML\Contracts;

interface CanWorkWithExternalID
{
    public static function editExternalID(): bool;
    public static function saveExternalID(int $id, string $value);
    public static function getExternalID(int $id);
    public static function getOrGenerateIfNotExist(int $id): string;
}