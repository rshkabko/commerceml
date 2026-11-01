<?php

namespace Flamix\CommerceML\Contracts;

/** An entity that keeps the EXTERNAL_ID Bitrix24 matches it by, generating one when it is missing. */
interface CanWorkWithExternalID
{
    public static function editExternalID(): bool;
    public static function saveExternalID(int $id, string $value);
    public static function getExternalID(int $id);
    public static function getOrGenerateIfNotExist(int $id): string;
}