<?php
/**
 * ReadyLaunch - Group's membership requests template.
 *
 * This template displays pending membership requests for a group
 * with AJAX loading support and request management interface.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();
?>

<div class="requests" data-bp-list="group_requests" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
	<?php
	if ( $is_send_ajax_request ) {
		?>
		<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-requests-loading' ); ?></div>
		<?php
	} else {
		bp_get_template_part( 'groups/single/requests-loop' );
	}
	?>
</div><!-- .requests -->
