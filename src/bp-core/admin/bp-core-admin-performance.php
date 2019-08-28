<?php
/**
 * BuddyBoss Performance panel.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.1.8
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Render the BuddyBoss Performance page.
 *
 * @since BuddyBoss 1.1.8
 */
function bp_core_admin_performance() {
	?>
    <div class="wrap">
        <h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Performance', 'buddyboss' ) ); ?></h2>
    </div>
    <div class="wrap">

        <div class="bp-admin-card section-default_data">

            <h2><?php esc_html_e( 'Object Cache', 'buddyboss' ); ?></h2>

            <form action="" method="post" id="bp-admin-form" class="bp-admin-form">

                <fieldset>

                    <?php if ( function_exists( 'opcache_reset' ) && ini_get( 'opcache.enable' ) ) : ?>
                    OPCache Enabled
                    <?php endif; ?>

                    <legend><?php esc_html_e( 'Cache to use:', 'buddyboss' ); ?></legend>

                    <div class="select">
                        <label for="bp-object-cache">
                            <select name="bp-object-cache">
                                <option <?php echo function_exists( 'opcache_reset' ) && ini_get( 'opcache.enable' ) ? '' : 'disabled'; ?> value="opcache"><?php esc_html_e( 'OPCache', 'buddyboss' ) ?></option>
                                <option <?php echo function_exists( 'apc_store' ) || function_exists( 'apcu_store' ) ? '' : 'disabled'; ?> value="apc"><?php esc_html_e( 'APC', 'buddyboss' ) ?></option>
                                <option <?php echo class_exists( 'Redis' ) ? '' : 'disabled'; ?> value="redis"><?php esc_html_e( 'Redis', 'buddyboss' ) ?></option>
                                <option <?php echo class_exists( 'Memcache' ) || class_exists( 'Memcached' ) ? '' : 'disabled'; ?> value="memcache"><?php esc_html_e( 'Memcache', 'buddyboss' ) ?></option>
                            </select>
                        </label>
                    </div>

                    <p class="submit">
                        <input class="button-primary" type="submit" name="bp-performance-submit" value="<?php esc_attr_e( 'Save', 'buddyboss' ); ?>" />
	                    <?php wp_nonce_field( 'bp-admin-performance-settings' ); ?>
                    </p>

                </fieldset>
            </form>

        </div>
    </div>
	<?php
}