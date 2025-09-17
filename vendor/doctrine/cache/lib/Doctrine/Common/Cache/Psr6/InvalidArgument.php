<?php

namespace BuddyBossPlatform\Doctrine\Common\Cache\Psr6;

use InvalidArgumentException;
use BuddyBossPlatform\Psr\Cache\InvalidArgumentException as PsrInvalidArgumentException;
/**
 * @internal
 */
final class InvalidArgument extends InvalidArgumentException implements PsrInvalidArgumentException
{
}
