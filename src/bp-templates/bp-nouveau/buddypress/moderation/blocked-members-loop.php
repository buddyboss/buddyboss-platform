<?php
/**
 * BuddyBoss - Moderation Blocked Member loop
 *
 * This template is used to loop through the blocked members.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/moderation/blocked-members-loop.php.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Core
 * @version 1.5.6
 */

if ( empty( $_POST['page'] ) || 1 === (int) bb_filter_input_string( INPUT_POST, 'page' ) ) :
    ?>
    <div class="bp-feedback bp-messages error moderation_notice is_hidden"><span class="bp-icon" aria-hidden="true"></span><p><?php _e( 'Sorry, you were not able to report this member.', 'buddyboss' ); ?></p></div>
    <table id="moderation-list" class="bp-tables-user">
    <thead>
    <th class="title">
		<?php
		esc_html_e( 'Blocked Members', 'buddyboss' );
		?>
    </th>
    <th class="title"></th>
    <th class="title"></th>
    </thead>
    <tbody>
<?php
endif;
while ( bp_moderation() ) :
	bp_the_moderation();
	bp_get_template_part( 'moderation/moderation-blocked-members-entry' );
endwhile;
if ( empty( $_POST['page'] ) || 1 === (int) bb_filter_input_string( INPUT_POST, 'page' ) ) :
	?>
    </tbody>
    </table>
<?php
endif;
if ( bp_moderation_has_more_items() ) :
	?>
    <div class="pager">
        <div class="md-more-container load-more text-center">
            <a class="button outline" href="<?php bp_moderation_load_more_link(); ?>">
				<?php
				esc_html_e( 'Load More', 'buddyboss' );
				?>
            </a>
        </div>
    </div>
<?php
endif;
