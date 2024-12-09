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
<div class="bb-subnav-filters-container bb-subnav-filters-search">
	<div class="subnav-filters-opener">
		<i class="bb-icon-f bb-icon-search"></i>	
	</div>
	<div class="subnav-filters filters no-ajax subnav-filters-modal" id="subnav-filters">
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
</div>

<span class="bb-subnav-filters-label">Show</span>
<div class="bb-subnav-filters-container bb-subnav-filters-filtering">

	<div class="subnav-filters-opener">
		<span class="selected">all posts</span>
		<i class="bb-icon-l bb-icon-angle-down"></i>
	</div>

	<div class="subnav-filters-modal">
		<ul>
			<li><a href="#" data-value="all_posts">All posts</a></li>
			<li><a href="#" data-value="created_by_me">Created by me</a></li>
			<li><a href="#" data-value="from_my_groups">From my groups</a></li>
			<li><a href="#" data-value="from_my_connections">From my connections</a></li>
			<li><a href="#" data-value="im_mentioned_in">I'm mentioned in</a></li>
			<li><a href="#" data-value="im_following">I'm following</a></li>
			<li><a href="#" data-value="ihv_replied_to">I've replied to</a></li>
		</ul>
	</div>
	<input type="hidden" name="bb_activity_filter_show" value="all_posts" />
</div>

<span class="bb-subnav-filters-label">by</span>
<div class="bb-subnav-filters-container bb-subnav-filters-filtering">

	<div class="subnav-filters-opener">
		<span class="selected">most recent</span>
		<i class="bb-icon-l bb-icon-angle-down"></i>
	</div>

	<div class="subnav-filters-modal">
		<ul>
			<li><a href="#" data-value="most_recent">Most recent</a></li>
			<li><a href="#" data-value="recent_activity">Recent activity</a></li>
		</ul>
	</div>
	<input type="hidden" name="bb_activity_filter_by" value="most_recent" />
</div>