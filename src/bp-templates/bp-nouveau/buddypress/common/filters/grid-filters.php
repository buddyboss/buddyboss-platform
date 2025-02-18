<?php
/**
 * The template for BP Nouveau Component's grid filters template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/filters/grid-filters.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

global $post;

$bb_is_member_dir       = bp_is_members_directory();
$bb_is_user             = bp_is_user();
$bb_is_group            = bp_is_group();
$bb_is_user_groups      = bp_is_user_groups();
$bb_is_groups_directory = bp_is_groups_directory();
$has_profile_shortcode  = false;
$has_group_shortcode    = false;
if ( ! empty( $args['shortcode_type'] ) ) {
	if ( 'members' === $args['shortcode_type'] ) {
		$has_profile_shortcode = true;
	} elseif ( 'groups' === $args['shortcode_type'] ) {
		$has_group_shortcode = true;
	}
} else {
	$has_profile_shortcode = has_shortcode( $post->post_content, 'profile' );
	$has_group_shortcode   = has_shortcode( $post->post_content, 'group' );
}

if ( $bb_is_member_dir || $bb_is_user || ( is_a( $post, 'WP_Post' ) && $has_profile_shortcode ) || bp_is_group_members() ) {
	if ( ! $bb_is_user_groups ) {
		$current_value = bp_get_option( 'bp-profile-layout-format', 'list_grid' );
	} else {
		$current_value = bp_get_option( 'bp-group-layout-format', 'list_grid' );
	}
} elseif ( $bb_is_groups_directory || $bb_is_group || ( is_a( $post, 'WP_Post' ) && $has_group_shortcode ) ) {
	$current_value = bp_get_option( 'bp-group-layout-format', 'list_grid' );
} else {
	$current_value = bp_get_option( 'bp-group-layout-format', 'list_grid' );
}
if ( 'list_grid' === $current_value ) {
	$default_current_value = '';
	if ( $bb_is_member_dir || $bb_is_user || ( is_a( $post, 'WP_Post' ) && $has_profile_shortcode ) ) {
		if ( ! $bb_is_user_groups ) {
			$default_current_value = bb_get_directory_layout_preference( 'members' );
		} else {
			$default_current_value = bb_get_directory_layout_preference( 'groups' );
		}
	} elseif ( $bb_is_groups_directory || $bb_is_group || ( is_a( $post, 'WP_Post' ) && $has_group_shortcode ) ) {
		if ( ! $bb_is_user_groups && ! $bb_is_groups_directory && ! $has_group_shortcode ) {
			$default_current_value = bb_get_directory_layout_preference( 'members' );
		} else {
			$default_current_value = bb_get_directory_layout_preference( 'groups' );
		}
	} else {
		$default_current_value = bb_get_directory_layout_preference( 'groups' );
	}

	$component = bp_current_component();
	if ( $bb_is_group && 'members' === bp_current_action() ) {
		$component = 'group_members';
	}

	if ( is_a( $post, 'WP_Post' ) ) {
		if ( $has_profile_shortcode ) {
			$component = 'members';
		} elseif ( $has_group_shortcode ) {
			$component = 'groups';
		}
	}

	?>
	<div class="grid-filters" data-object="<?php echo esc_attr( $component ); ?>">
		<a href="#" class="layout-view layout-grid-view bp-tooltip <?php echo ( 'grid' === $default_current_value ) ? 'active' : ''; ?>" data-view="grid" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Grid View', 'buddyboss' ); ?>"> <i class="bb-icon-l bb-icon-grid-large" aria-hidden="true"></i> </a>

		<a href="#" class="layout-view layout-list-view bp-tooltip <?php echo ( 'list' === $default_current_value ) ? 'active' : ''; ?>" data-view="list" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'List View', 'buddyboss' );?>"> <i class="bb-icon-l bb-icon-bars" aria-hidden="true"></i> </a>
	</div>
	<?php
}
