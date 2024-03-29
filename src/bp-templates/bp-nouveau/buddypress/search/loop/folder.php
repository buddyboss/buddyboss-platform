<?php
/**
 * Template for displaying the search results of the folder
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/folder.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$folder_link             = bp_get_folder_folder_link();
$folder_id               = bp_get_folder_folder_id();
$document_folder_privacy = bp_get_document_folder_privacy();
?>
<li data-bp-item-id="<?php echo esc_attr( $folder_id ); ?>" data-bp-item-component="document" class="search-document-list">
	<div class="list-wrap">
		<div class="item">

			<div class="media-folder_items ac-folder-list">
				<div class="media-folder_icon">
					<a href="<?php echo esc_url( $folder_link ); ?>">
						<i class="bb-icon-f bb-icon-folder-alt"></i>
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
						<span class="middot">·</span>
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
								<span class="middot">·</span>
								<span>
									<?php echo $document_folder_privacy; ?>
								</span>
								<?php
							} else {
								?>
								<span class="middot">·</span>
								<span id="privacy-<?php echo esc_attr( $folder_id ); ?>">
									<?php echo $document_folder_privacy; ?>
								</span>
								<?php
							}
						} else {
							?>
							<span class="middot">·</span>
							<span>
								<?php echo $document_folder_privacy; ?>
							</span>
							<?php
						}
						?>
					</div>
				</div>

			</div><!--.media-folder_items-->

		</div><!--.item-->
	</div><!--.list-wrap-->
</li>
