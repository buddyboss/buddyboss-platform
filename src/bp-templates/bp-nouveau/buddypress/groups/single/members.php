<?php
/**
 * BuddyBoss - Groups Members
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/members.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
$is_send_ajax_request = bb_is_send_ajax_request();
bp_get_template_part( 'groups/single/parts/members-subnav' );
$enable_count = bb_enable_content_counts();
if ( $enable_count ) {
	?>
		<div class="bb-item-count bb-group-members-count">
			<?php
			if ( ! $is_send_ajax_request ) {
				$count = groups_get_total_member_count( bp_get_current_group_id() );

				printf(
					wp_kses(
						/* translators: %d is the members count */
						_n(
							'<span class="bb-count">%d</span> Member',
							'<span class="bb-count">%d</span> Members',
							$count,
							'buddyboss'
						),
						array( 'span' => array( 'class' => true ) )
					),
					(int) $count
				);

				unset( $count );
			}
			?>
		</div>
	<?php
	bp_nouveau_search_form();
}
?>
<div class="subnav-filters filters clearfix no-subnav">
	<?php
	if ( ! $enable_count ) {
		bp_nouveau_search_form();
	}
	unset( $enable_count );
	bp_get_template_part( 'common/filters/groups-screens-filters' );
	bp_get_template_part( 'common/filters/grid-filters' );
	?>
</div>

<?php
switch ( bp_action_variable( 0 ) ) :

	// Groups/All Members
	case 'all-members':
		?>
		<div id="members-group-list" class="group_members dir-list" data-bp-list="group_members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
			<?php
			if ( $is_send_ajax_request ) {
				?>
				<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-members-loading' ); ?></div>
				<?php
			} else {
				bp_get_template_part( 'groups/single/members-loop' );
			}
			?>
		</div>
		<?php
		break;

	case 'leaders':
		?>
		<div id="members-group-list" class="group_members dir-list" data-bp-list="group_members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
			<?php
			if ( $is_send_ajax_request ) {
				?>
				<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-leaders-loading' ); ?></div>
				?>
				<?php
			} else {
				bp_get_template_part( 'groups/single/members-loop' );
			}
			?>
		</div>
		<?php
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
