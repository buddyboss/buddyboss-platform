<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Strime <contact@strime.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BuddyBossPlatform\FFMpeg\Filters\Concat;

use BuddyBossPlatform\FFMpeg\Media\Concat;
class ConcatFilters
{
    private $concat;
    public function __construct(Concat $concat)
    {
        $this->concat = $concat;
    }
}
