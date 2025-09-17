<?php

namespace BuddyBoss\Library\Composer;

/**
 * FFMpeg custom class.
 *
 * @since BuddyBoss 2.6.30
 */
class FFMpeg
{
    private static $instance;
    /**
     * Get the instance of the class.
     *
     * @since BuddyBoss 2.6.30
     *
     * @return FFMpeg
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            $class = __CLASS__;
            self::$instance = new $class();
        }
        return self::$instance;
    }
    /**
     * This Function Is Used To Get Instance From Scoped Vendor.
     *
     * @since BuddyBoss 2.6.30
     *
     * @param \FFMpeg\Driver\FFMpegDriver $ffmpeg
     * @param \FFMpeg\FFProbe             $ffprobe
     *
     * @return \FFMpeg\FFMpeg
     */
    function ffmpeg($ffmpeg, $ffprobe)
    {
        return new \BuddyBossPlatform\FFMpeg\FFMpeg($ffmpeg, $ffprobe);
    }
    /**
     * This Function Is Used To Get Instance From Scoped Vendor.
     *
     * @since BuddyBoss 2.6.30
     *
     * @param \FFMpeg\Driver\FFProbeDriver $ffprobe
     * @param \Doctrine\Common\Cache\Cache $cache
     *
     * @return \FFMpeg\FFProbe
     */
    function ffprobe($ffprobe, $cache)
    {
        return new \BuddyBossPlatform\FFMpeg\FFProbe($ffprobe, $cache);
    }
    /**
     * Creates an FFMpeg.
     *
     * @since BuddyBoss 2.6.30
     *
     * @param array|\Alchemy\BinaryDriver\ConfigurationInterface $configuration
     * @param \Psr\Log\LoggerInterface                           $logger
     * @param \FFMpeg\FFProbe                                    $probe
     *
     * @return \FFMpeg\FFMpeg
     */
    function ffmpeg_create($configuration = array(), $logger = null, $probe = null)
    {
        return \BuddyBossPlatform\FFMpeg\FFMpeg::create($configuration, $logger, $probe);
    }
    /**
     * Creates an FFProbe.
     *
     * @since BuddyBoss 2.6.30
     *
     * @param array|\Alchemy\BinaryDriver\ConfigurationInterface $configuration
     * @param \Psr\Log\LoggerInterface                           $logger
     * @param \Doctrine\Common\Cache\Cache                       $cache
     *
     * @return \FFMpeg\FFProbe
     */
    function ffprobe_create($configuration = array(), $logger = null, $cache = null)
    {
        return \BuddyBossPlatform\FFMpeg\FFProbe::create($configuration, $logger, $cache = null);
    }
    /**
     * Create timecode from number of seconds From Scoped Vendor
     *
     * @since BuddyBoss 2.6.30
     *
     * @param float $seconds Seconds value.
     *
     * @return \FFMpeg\Coordinate\TimeCode
     */
    function timecode_from_seconds($seconds)
    {
        return \BuddyBossPlatform\FFMpeg\Coordinate\TimeCode::fromSeconds($seconds);
    }
}
