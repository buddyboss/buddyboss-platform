<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

class StyleUtil
{
    /**
     * Implodes an associative array of CSS rules into a style attribute string.
     *
     * @param  array $styles The styles array.
     * @return string
     */
    public static function arrToAttr(array $styles) : string
    {
        $attr = '';
        foreach ($styles as $prop => $val) {
            $prop = esc_attr($prop);
            $val = esc_attr($val);
            $attr .= "{$prop}:{$val};";
        }
        return $attr ? "style=\"{$attr}\"" : '';
    }
}
