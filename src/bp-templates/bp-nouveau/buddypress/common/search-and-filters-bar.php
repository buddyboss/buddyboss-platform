<?php
/**
 * The template for BP Nouveau Search & filters bar
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/search-and-filters-bar.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

?>
<div class="subnav-filters filters no-ajax" id="subnav-filters">
	<?php
	$bp_current_component = bp_current_component();
	if (
		'friends' !== $bp_current_component &&
		(
			'members' !== $bp_current_component ||
			bp_disable_advanced_profile_search()
		)
	) {
		?>
		<div class="subnav-search clearfix">
			<?php bp_nouveau_search_form(); ?>
		</div>
		<?php
	}

	if (
		(
			'members' === $bp_current_component ||
			'groups' === $bp_current_component ||
			'friends' === $bp_current_component
		) &&
		! bp_is_current_action( 'requests' )
	) {
		bp_get_template_part( 'common/filters/grid-filters' );
	}

	if (
		(
			'members' === $bp_current_component ||
			'groups' === $bp_current_component ) ||
			(
				bp_is_user() &&
				(
					! bp_is_current_action( 'requests' ) &&
					! bp_is_current_action( 'mutual' )
				)
			)
	) {
		bp_get_template_part( 'common/filters/directory-filters' );
	}

	if (
		'members' === $bp_current_component ||
		(
			'friends' === $bp_current_component &&
			'my-friends' === bp_current_action()
		)
	) {
		bp_get_template_part( 'common/filters/member-filters' );
	}

	if ( 'groups' === $bp_current_component ) {
		bp_get_template_part( 'common/filters/group-filters' );
	}
	?>
</div><!-- search & filters -->
