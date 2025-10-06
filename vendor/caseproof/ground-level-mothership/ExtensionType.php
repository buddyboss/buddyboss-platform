<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership;

use BuddyBossPlatform\GroundLevel\Support\Enum;
/**
 * Extension types.
 *
 * @method static ExtensionType PLUGIN() Returns the {@see ExtensionType::PLUGIN} enum case.
 * @method static ExtensionType THEME() Returns the {@see ExtensionType::THEME} enum case.
 */
class ExtensionType extends Enum
{
    /**
     * The identifier for the plugin extension type.
     */
    public const PLUGIN = 'plugin';
    /**
     * The identifier for the theme extension type.
     */
    public const THEME = 'theme';
}
