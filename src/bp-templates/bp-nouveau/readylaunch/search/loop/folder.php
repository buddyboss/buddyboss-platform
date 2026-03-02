<?php
/**
 * ReadyLaunch - Search Loop Folder template.
 *
 * The template for search results for folders.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$folder_link             = bp_get_folder_folder_link();
$folder_id               = bp_get_folder_folder_id();
$document_folder_privacy = bp_get_document_folder_privacy();
?>
<li data-bp-item-id="<?php echo esc_attr( $folder_id ); ?>" data-bp-item-component="document" class="bp-search-item search-document-list bb-rl-search-post-item">
	<div class="list-wrap">
		<a href="<?php echo esc_url( $folder_link ); ?>">
			<div class="item-avatar">
				<i class="bb-icons-rl-folder"></i>
			</div>
		</a>
		<div class="item">

			<div class="media-folder_items ac-folder-list">
				<div class="media-folder_details item-title">
					<a class="media-folder_name " href="<?php echo esc_url( $folder_link ); ?>">
						<span><?php echo bp_get_folder_folder_title(); ?></span>
					</a>
				</div>

				<div class="entry-meta">
					<div class="media-folder_modified">
						<div class="media-folder_details__bottom">
							<span class="media-folder_author"><?php esc_html_e( 'By ', 'buddyboss' ); ?><a href="<?php echo trailingslashit( bp_core_get_user_domain( bp_get_document_folder_user_id() ) . bp_get_document_slug() ); ?>" data-bb-hp-profile="<?php echo esc_attr( bp_get_document_folder_user_id() ); ?>"><?php bp_folder_author(); ?></a></span>
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
				</div>

			</div><!--.media-folder_items-->

		</div><!--.item-->
	</div><!--.list-wrap-->
</li>
