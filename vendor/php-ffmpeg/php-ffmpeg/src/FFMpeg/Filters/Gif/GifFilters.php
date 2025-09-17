<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Strime <contact@strime.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BuddyBossPlatform\FFMpeg\Filters\Gif;

use BuddyBossPlatform\FFMpeg\Media\Gif;
class GifFilters
{
    private $gif;
    public function __construct(Gif $gif)
    {
        $this->gif = $gif;
    }
}
