<?php
/**
 * Template for displaying the search results of the folder ajax
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/folder-ajax.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$folder_link = bp_get_folder_folder_link();
?>

<div class="bp-search-ajax-item bboss_ajax_search_document search-document-list">
	<div class="item">

		<div class="media-folder_items ac-folder-list">
			<div class="media-folder_icon">
				<a href="<?php echo esc_url( $folder_link ); ?>">
					<i class="bb-icon-l bb-icon-folder-alt"></i>
				</a>
			</div>

			<div class="media-folder_details">
				<a class="media-folder_name " href="<?php echo esc_url( $folder_link ); ?>">
					<span><?php echo bp_get_folder_folder_title(); ?></span>
				</a>
			</div>

			<div class="media-folder_modified">
				<div class="media-folder_details__bottom">
					<span class="media-folder_author"><?php esc_html_e( 'By ', 'buddyboss' ); ?><a href="<?php echo trailingslashit( bp_core_get_user_domain( bp_get_document_folder_user_id() ) . bp_get_document_slug() ); ?>"><?php bp_folder_author(); ?></a></span>
					<span class="middot">&middot;</span>
					<span class="media-folder_date"><?php bp_document_folder_date(); ?></span>
				</div>
			</div>

			<div class="media-folder_visibility">
				<div class="media-folder_details__bottom">
					<?php
					if ( bp_is_active( 'groups' ) ) {
						$group_id = bp_get_folder_group_id();
						if ( $group_id > 0 ) {
							?>
							<span class="middot">&middot;</span>
							<span>
								<?php bp_document_folder_privacy(); ?>
							</span>
							<?php
						} else {
							?>
							<span class="middot">&middot;</span>
							<span id="privacy-<?php echo esc_attr( bp_get_folder_folder_id() ); ?>">
								<?php bp_document_folder_privacy(); ?>
							</span>
							<?php
						}
					} else {
						?>
						<span class="middot">&middot;</span>
						<span>
							<?php bp_document_folder_privacy(); ?>
						</span>
						<?php
					}
					?>
				</div>
			</div>

		</div><!--.media-folder_items-->

	</div>
</div>
