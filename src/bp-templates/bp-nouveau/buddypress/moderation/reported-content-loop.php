<?php
/**
 * BuddyBoss - Moderation Reported Content loop
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
		esc_html_e( 'Content Type', 'buddyboss' );
		?>
	</th>
	<th>
		<?php
		esc_html_e( 'Content ID', 'buddyboss' );
		?>
	</th>
	<th>
		<?php
		esc_html_e( 'Content Owner', 'buddyboss' );
		?>
	</th>
	<th>
		<?php
		esc_html_e( 'Content Excerpt', 'buddyboss' );
		?>
	</th>
	<th>
		<?php
		esc_html_e( 'Category', 'buddyboss' );
		?>
	</th>
	<th>
		<?php
		esc_html_e( 'Reported', 'buddyboss' );
		?>
	</th>
	</thead>
	<tbody>
<?php
endif;
while ( bp_moderation() ) :
	bp_the_moderation();
	bp_get_template_part( 'moderation/moderation-reported-content-entry' );
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
