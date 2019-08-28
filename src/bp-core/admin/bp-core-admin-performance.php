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
        <div class="nav-settings-subsubsub">
            <ul class="subsubsub">
				<?php bp_core_performance_settings_admin_tabs(); ?>
            </ul>
        </div>
    </div>
    <div class="wrap">
        <div class="bp-admin-card section-default_data">

            <h2><?php esc_html_e( 'OpCache', 'buddyboss' ) ?></h2>

            <form action="" method="post" id="bp-admin-form" class="bp-admin-form">
				<?php wp_nonce_field( 'bp-admin-performance-settings' ); ?>
            </form>

        </div>
    </div>
	<?php
}