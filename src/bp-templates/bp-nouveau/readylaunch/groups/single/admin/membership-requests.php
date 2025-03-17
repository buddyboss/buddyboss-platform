<?php
/**
 * BP Nouveau Group's membership requests template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/membership-requests.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

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
