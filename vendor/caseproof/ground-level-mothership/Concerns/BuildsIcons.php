<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Concerns;

trait BuildsIcons
{
    /**
     * Builds the WordPress-shaped icons array for a plugin.
     *
     * @param  object|null $product The product object.
     * @return array
     */
    private function buildPluginIcons(?object $product) : array
    {
        $image = (string) ($product->image ?? '');
        if ('' === $image) {
            return [];
        }
        return ['2x' => $image, '1x' => $image];
    }
}
