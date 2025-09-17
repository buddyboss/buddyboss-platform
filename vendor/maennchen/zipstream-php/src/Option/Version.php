<?php

declare (strict_types=1);
namespace BuddyBossPlatform\ZipStream\Option;

use MyCLabs\Enum\Enum;
/**
 * Class Version
 * @package ZipStream\Option
 *
 * @method static STORE(): Version
 * @method static DEFLATE(): Version
 * @method static ZIP64(): Version
 * @psalm-immutable
 */
class Version extends Enum
{
    public const STORE = 0xa;
    // 1.00
    public const DEFLATE = 0x14;
    // 2.00
    public const ZIP64 = 0x2d;
    // 4.50
}
