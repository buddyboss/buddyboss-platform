<?php
/**
 * BP Nouveau Default user's front template.
 *
 * @since 3.0.0
 * @version 3.1.0
 */
?>

<div class="member-front-page">

	<?php if ( bp_nouveau_members_wp_bio_info() ) : ?>

		<div class="member-description">

			<?php if ( get_the_author_meta( 'description', bp_displayed_user_id() ) ) : ?>
				<blockquote class="member-bio">
					<?php bp_nouveau_member_description( bp_displayed_user_id() ); ?>
				</blockquote><!-- .member-bio -->
			<?php endif; ?>

		</div><!-- .member-description -->

	<?php endif; ?>

	<?php if ( is_active_sidebar( 'sidebar-buddypress-members' ) ) : ?>

		<div id="member-front-widgets" class="bp-sidebar bp-widget-area" role="complementary">
			<?php dynamic_sidebar( 'sidebar-buddypress-members' ); ?>
		</div><!-- .bp-sidebar.bp-widget-area -->

	<?php endif; ?>

</div>
