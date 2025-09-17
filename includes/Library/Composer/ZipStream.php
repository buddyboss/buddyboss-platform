<?php

namespace BuddyBoss\Library\Composer;

/**
 * ZipStream custom class.
 *
 * @since BuddyBoss 2.6.30
 */
class ZipStream
{
    private static $instance;
    /**
     * Get the instance of the class.
     *
     * @since BuddyBoss 2.6.30
     *
     * @return ZipStream
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
     * @return \ZipStream\ZipStream
     */
    function zipstream($file_name, $options)
    {
        return new \BuddyBossPlatform\ZipStream\ZipStream($file_name, $options);
    }
    /**
     * This Function Is Used To Get Instance From Scoped Vendor.
     *
     * @since BuddyBoss 2.6.30
     *
     * @return \ZipStream\Option\Archive
     */
    function archive()
    {
        return new \BuddyBossPlatform\ZipStream\Option\Archive();
    }
}
