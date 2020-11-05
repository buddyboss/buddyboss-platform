<?php
/**
 * BuddyBoss - Moderation Blocked Member loop
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.5.4
 */

if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) :
	?>
	<table id="moderation-list">
	<thead>
	<th>
		<?php
		esc_html_e( 'Blocked Member', 'buddyboss' );
		?>
	</th>
	<th>
		<?php
		esc_html_e( 'Blocked', 'buddyboss' );
		?>
	</th>
	<th>
		<?php
		esc_html_e( 'Actions', 'buddyboss' );
		?>
	</th>
	</thead>
	<tbody>
<?php
endif;
while ( bp_moderation() ) :
	bp_the_moderation();
	bp_get_template_part( 'moderation/moderation-blocked-members-entry' );
endwhile;
if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) :
	?>
	</tbody>
	</table>
<?php
endif;
if ( bp_moderation_has_more_items() ) :
	?>
	<div class="pager">
		<div class="md-more-container load-more">
			<a class="button outline full" href="<?php bp_moderation_load_more_link(); ?>">
				<?php
				esc_html_e( 'Load More', 'buddyboss' );
				?>
			</a>
		</div>
	</div>
<?php
endif;
