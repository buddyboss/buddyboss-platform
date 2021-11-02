<?php
/**
 * BuddyBoss Admin Screen.
 *
 * This file contains information about BuddyBoss.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="bp-hello-backdrop" style="display: none"></div>
<div id="bp-hello-container" class="bp-hello-buddyboss" role="dialog" aria-labelledby="bp-hello-title" style="display: none">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e('Close pop-up', 'buddyboss' ); ?>">
				<?php
				esc_html_e( 'Close', 'buddyboss' );
				?>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1">Notice</h1>
		</div>
	</div>

    <div class="bp-hello-content">
		<?php
		$component_link = bp_get_admin_url(
			add_query_arg(
				array(
					'page' => 'bp-components'
				),
				'admin.php'
			)
		);
		?>
        <div class="bp-spam-action-msg" style="display: none">
			<?php
			printf( __( 'To suspend members who are creating spam in your network, activate the <a href="%s" >Moderation</a> component.', 'buddyboss' ), $component_link );
			?>
        </div>
        <div class="bp-not-spam-action-msg" style="display: none;">
	        <?php
	        printf( __( 'To unsuspend members who are not creating spam anymore in your network, activate the <a href="%s" >Moderation</a> component.', 'buddyboss' ), $component_link );
	        ?>
        </div>
    </div>
</div>
