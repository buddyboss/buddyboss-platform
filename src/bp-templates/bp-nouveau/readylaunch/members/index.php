<?php
/**
 * The ReadyLaunch template for members directory.
 *
 * @since   BuddyBoss [BBVERSION]
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

<div class="members-directory-wrapper">

	<?php
	/**
	 * Fires before the display of the members.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bp_before_directory_members' );
	?>

	<div class="members-directory-container">

		<?php
		/**
		 * Fires before the display of the members list tabs.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bp_before_directory_members_tabs' );

		if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
			bp_get_template_part( 'common/nav/directory-nav' );
		}

		bp_get_template_part( 'common/search-and-filters-bar' );

		/**
		 * Fires before the display of the members content.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bp_before_directory_members_content' );
		?>

		<div class="screen-content members-directory-content">

			<div id="members-dir-list" class="members dir-list" data-bp-list="members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
				<?php
				if ( $is_send_ajax_request ) {
					echo '<div id="bb-rl-ajax-loader">';
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

<?php
/**
 * Fires at the bottom of the member directory template file.
 *
 * @since BuddyBoss [BBVERSION]
 */
do_action( 'bp_after_directory_members_page' );
?>
