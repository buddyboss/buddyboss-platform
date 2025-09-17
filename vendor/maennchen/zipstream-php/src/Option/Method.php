<?php

declare (strict_types=1);
namespace BuddyBossPlatform\ZipStream\Option;

use MyCLabs\Enum\Enum;
/**
 * Methods enum
 *
 * @method static STORE(): Method
 * @method static DEFLATE(): Method
 * @psalm-immutable
 */
class Method extends Enum
{
    public const STORE = 0x0;
    public const DEFLATE = 0x8;
}
