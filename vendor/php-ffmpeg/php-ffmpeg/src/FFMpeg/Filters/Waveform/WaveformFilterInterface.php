<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Strime <contact@strime.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BuddyBossPlatform\FFMpeg\Filters\Waveform;

use BuddyBossPlatform\FFMpeg\Filters\FilterInterface;
use BuddyBossPlatform\FFMpeg\Media\Waveform;
interface WaveformFilterInterface extends FilterInterface
{
    public function apply(Waveform $waveform);
}
