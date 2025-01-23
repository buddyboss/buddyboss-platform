<?php
/**
 * The ReadyLaunch template for members directory.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

/**
 * Fires at the begining of the templates BP injected content.
 *
 * @since BuddyBoss [BBVERSION]
 */
do_action( 'bp_before_directory_members_page' );
?>

<div class="bb-rl-members-directory-wrapper">
	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<h2><?php esc_html_e( 'Members', 'buddyboss' ); ?> <span class="bb-rl-heading-count"><?php echo ! $is_send_ajax_request ? bp_core_get_all_member_count() : ''; ?></span></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>
			<div class="bb-rl-action-button">
				<a href="" id="bb-rl-invite-button" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small flex items-center"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Invite', 'buddyboss' ); ?></a>
			</div>
		</div>
	</div>

	<div class="bb-rl-container-inner">

		<?php
		/**
		 * Fires before the display of the members.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bp_before_directory_members' );
		?>

		<div class="bb-rl-members-directory-container">

			<?php
			/**
			 * Fires before the display of the members list tabs.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_before_directory_members_tabs' );

			/**
			 * Fires before the display of the members content.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_before_directory_members_content' );
			?>

			<div class="screen-content bb-rl-members-directory-content">

				<div id="bb-rl-members-dir-list" class="members dir-list bb-rl-members" data-bp-list="members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bp-ajax-loader">';
						bp_nouveau_user_feedback( 'directory-members-loading' );
						echo '</div>';
					} else {
						bp_get_template_part( 'members/members-loop' );
					}
					?>
				</div><!-- #members-dir-list -->

				<?php
				/**
				 * Fires and displays the members content.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_directory_members_content' );
				?>
			</div><!-- // .screen-content -->

			<?php

			bp_get_template_part( 'sidebar/right-sidebar' );

			/**
			 * Fires after the display of the members content.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_after_directory_members_content' );
			?>

		</div>

		<?php
		/**
		 * Fires after the display of the members.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bp_after_directory_members' );
		?>
	</div>

</div>

<?php
/**
 * Fires at the bottom of the member directory template file.
 *
 * @since BuddyBoss [BBVERSION]
 */
do_action( 'bp_after_directory_members_page' );
?>
