<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Manager;

/**
 * WordPress class extended for on-the-fly add-on installations.
 */
class AddonInstallSkin extends \WP_Upgrader_Skin
{
    /**
     * Set the upgrader object and store it as a property in the parent class.
     *
     * @param object $upgrader The upgrader object (passed by reference).
     */
    public function set_upgrader(&$upgrader)
    {
        if (\is_object($upgrader)) {
            $this->upgrader =& $upgrader;
        }
    }
    /**
     * Empty out the header of its HTML content and only check to see if it has
     * been performed or not.
     */
    public function header()
    {
    }
    /**
     * Empty out the footer of its HTML contents.
     */
    public function footer()
    {
    }
    /**
     * Instead of outputting HTML for errors, json_encode the errors and send them
     * back to the Ajax script for processing.
     *
     * @param array $errors Array of errors with the install process.
     */
    public function error($errors)
    {
        if (!empty($errors)) {
            wp_send_json_error($errors);
        }
    }
    /**
     * Empty out the feedback method to prevent outputting HTML strings as the install
     * is progressing.
     *
     * @param string $string  The feedback string.
     * @param mixed  ...$args Optional text replacements.
     */
    public function feedback($string, ...$args)
    {
    }
    /**
     * Empty out JavaScript output that calls function to decrement the update counts.
     *
     * @param mixed $type Type of update count to decrement.
     */
    public function decrement_update_count($type)
    {
    }
}
