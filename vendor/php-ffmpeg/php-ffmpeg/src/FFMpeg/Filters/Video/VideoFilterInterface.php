<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BuddyBossPlatform\FFMpeg\Filters\Video;

use BuddyBossPlatform\FFMpeg\Filters\FilterInterface;
use BuddyBossPlatform\FFMpeg\Format\VideoInterface;
use BuddyBossPlatform\FFMpeg\Media\Video;
interface VideoFilterInterface extends FilterInterface
{
    /**
     * Applies the filter on the the Video media given an format.
     *
     * @param Video          $video
     * @param VideoInterface $format
     *
     * @return array An array of arguments
     */
    public function apply(Video $video, VideoInterface $format);
}
