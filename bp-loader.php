<?php
/**
 * Plugin Name: BuddyBoss Platform
 * Plugin URI:  https://buddyboss.com/
 * Description: The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     1.5.3
 * Text Domain: buddyboss
 * Domain Path: /bp-languages/
 * License:     GPLv2 or later (license.txt)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assume you want to load from build
$bp_loader = dirname( __FILE__ ) . '/src/bp-loader.php';
$subdir    = 'src';

if ( ! defined( 'BP_SOURCE_SUBDIRECTORY' ) ) {
	// Set source subdirectory
	define( 'BP_SOURCE_SUBDIRECTORY', $subdir );
}

// Define overrides - only applicable to those running trunk
if ( ! defined( 'BP_PLUGIN_DIR' ) ) {
	define( 'BP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'BP_PLUGIN_URL' ) ) {
	// Be nice to symlinked directories
	define( 'BP_PLUGIN_URL', plugins_url( trailingslashit( basename( constant( 'BP_PLUGIN_DIR' ) ) ) ) );
}

// Include BuddyBoss Platform
include( $bp_loader );

// Unset the loader, since it's loaded in global scope
unset( $bp_loader );

add_action( 'wp_footer', function () {
	?>
    <div id="jk-modal" class="registration-popup bb-modal mfp-hide">
        <h2>
			<?php
			esc_html_e( 'Report Content', 'buddyboss' );
			?>
        </h2>
		<?php
		$reports_terms = get_terms( 'bpm_category', array(
			'hide_empty' => false,
			'fields'     => 'id=>name',
		) );
		?>
        <div class="bb-report-type-wrp">
            <form id="bb-report-content" action="javascript:void(0);">
				<?php
				if ( ! empty( $reports_terms ) ) {
					foreach ( $reports_terms as $key => $reports_term ) {
						?>
                        <input type="radio" id="report-type-<?php echo esc_attr( $key ); ?>" name="report_type"
                               value="<?php echo esc_attr( $key ); ?>">
                        <label for="report-type-<?php echo esc_attr( $key ) ?>">
							<?php
							echo esc_html( $reports_term );
							?>
                        </label>
						<?php
					}
					?>
					<?php
				}
				?>
                <input type="radio" id="report-type-other" name="report_type"
                       value="other">
                <label for="report-type-other">
					<?php
					esc_html_e( 'Other', 'buddyboss' );
					?>
                </label>
                <input type="button" class="bb-cancel-report-content"
                       value="<?php esc_attr_e( 'Cancel', 'buddyboss' ); ?>"/>
                <input type="submit" value="<?php esc_attr_e( 'Send Report', 'buddyboss' ); ?>" class="report-submit"/>
                <input type="hidden" name="content_id" class="bp-content-id"/>
                <input type="hidden" name="content_type" class="bp-content-type"/>
                <input type="hidden" name="_wpnonce" class="bp-nonce"/>
            </form>
        </div>
    </div>
	<?php
} );