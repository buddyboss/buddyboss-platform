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
        <form action="" method="post">
			<?php
			settings_fields( 'bp-performance' );
			bp_custom_pages_do_settings_sections( 'bp-performance' );

			printf(
				'<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="%s" />
			</p>',
				esc_attr__( 'Save Settings', 'buddyboss' )
			);
			?>
        </form>
    </div>

	<?php
}

/**
 * Caching setting field.
 *
 * @since BuddyBoss 1.1.8
 *
 */
function bp_admin_setting_caching_callback() {
	?>

    <input id="bp-enable-caching" name="bp-enable-caching" type="checkbox" value="1" <?php checked( bp_is_caching_enabled() ); ?> />
    <label for="bp-enable-caching"><?php echo sprintf( __( 'Enable cache using %s caching method', 'buddyboss' ), bp_performance_caching_methods_dropdown() ); ?></label>
    <p class="description"><?php _e( 'You have <a href="#">APC</a> and <a href="#">Memcached</a> enabled on your server.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Flush Caching setting field.
 *
 * @since BuddyBoss 1.1.8
 *
 */
function bp_performance_flush_cache_callback() {
	?>

    <p>
        <a class="button" href="#"><?php _e( 'Flush Cache', 'buddyboss' ); ?></a>
    </p>

	<?php
}

function bp_performance_caching_methods_dropdown() {
    ob_start();
    ?>
    <select name="bp-caching-method">
        <option><?php _e( 'Select', 'buddyboss' ); ?></option>
        <option <?php echo function_exists( 'opcache_reset' ) && ini_get( 'opcache.enable' ) ? '' : 'disabled'; ?> value="opcache"><?php esc_html_e( 'OPCache', 'buddyboss' ) ?></option>
        <option <?php echo function_exists( 'apc_store' ) || function_exists( 'apcu_store' ) ? '' : 'disabled'; ?> value="apc"><?php esc_html_e( 'APC', 'buddyboss' ) ?></option>
        <option <?php echo class_exists( 'Redis' ) ? '' : 'disabled'; ?> value="redis"><?php esc_html_e( 'Redis', 'buddyboss' ) ?></option>
        <option <?php echo class_exists( 'Memcache' ) || class_exists( 'Memcached' ) ? '' : 'disabled'; ?> value="memcache"><?php esc_html_e( 'Memcache', 'buddyboss' ) ?></option>
    </select>
    <?php
    return ob_get_clean();
}