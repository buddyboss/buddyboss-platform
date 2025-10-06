<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Container;

use BuddyBossPlatform\Psr\Container\ContainerExceptionInterface;
use Exception as BaseException;
class Exception extends BaseException implements ContainerExceptionInterface
{
}
