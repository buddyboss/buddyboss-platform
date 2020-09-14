<?php
$listing_class  = '';
$attachment_id  = bp_get_media_attachment_id();
$attachment_url = '';
$media_id       = bp_get_media_id();
$filename       = basename( get_attached_file( $attachment_id ) );
$photo_title    = '';
$media_type     = '';
if ( $attachment_id ) {
	$attachment_url = wp_get_attachment_url( $attachment_id );
	$listing_class  = 'ac-media-list';
	$media_type     = 'photos';
	$photo_title    = bp_get_media_title();
}

$class = ''; // used.
if ( $attachment_id && bp_get_media_activity_id() ) {
	$class = ''; // used.
}
$link = trailingslashit( bp_core_get_user_domain( bp_get_media_user_id() ) . bp_get_media_slug() );
?>

<div class="bp-search-ajax-item bboss_ajax_search_media search-media-list">
	<a href="">
		<div class="item">
			<div class="media-folder_items <?php echo esc_attr( $listing_class ); ?>" data-activity-id="<?php bp_media_activity_id(); ?>" data-id="<?php bp_media_id(); ?>" data-parent-id="<?php bp_media_album_id(); ?>" id="div-listing-<?php bp_media_id(); ?>">
				<div class="media-folder_icon">
					<a href="<?php echo esc_url( $link ); ?>">
						<i>
							<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32"><title>file-jpg</title><path d="M13.728 0c1.088 0 2.112 0.448 2.88 1.216v0l6.272 6.496c0.736 0.736 1.12 1.728 1.12 2.784v0 17.504c0 2.208-1.792 4-4 4v0h-16c-2.208 0-4-1.792-4-4v0-24c0-2.208 1.792-4 4-4v0h9.728zM13.728 1.984h-9.728c-1.088 0-1.984 0.896-1.984 2.016v0 24c0 1.12 0.896 2.016 1.984 2.016v0h16c1.12 0 2.016-0.896 2.016-2.016v0-17.504c0-0.512-0.224-1.024-0.576-1.408v0l-6.272-6.464c-0.384-0.416-0.896-0.64-1.44-0.64v0zM16 13.504c1.6 0 2.912 1.248 3.008 2.816v8.192c0 1.6-1.248 2.88-2.816 2.976h-8.192c-1.6 0-2.912-1.248-2.976-2.816l-0.032-0.16v-8c0-1.6 1.248-2.912 2.848-3.008h8.16zM16 14.496h-8c-1.056 0-1.92 0.832-1.984 1.856v8.16c0 0.064 0 0.096 0 0.16l2.624-2.432c0.384-0.384 1.024-0.352 1.408 0.032v0l1.376 1.504 3.328-3.84c0.352-0.416 0.992-0.448 1.408-0.096 0.032 0.032 0.064 0.064 0.096 0.096l1.76 1.92v-5.344c0-1.056-0.832-1.92-1.856-2.016h-0.16zM10.752 18.112c0.704 0 1.248 0.544 1.248 1.248 0 0.672-0.544 1.248-1.248 1.248s-1.248-0.576-1.248-1.248c0-0.704 0.544-1.248 1.248-1.248z"></path></svg>
						</i>
					</a>
				</div>
				<div class="media-folder_details">
					<a class="media-folder_name <?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $link ); ?>" data-id="<?php bp_media_id(); ?>" data-attachment-full="" data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>" data-activity-id="<?php bp_media_activity_id(); ?>" data-preview="<?php echo $attachment_url ? esc_url( $attachment_url ) : ''; ?>" data-album-id="<?php bp_media_album_id(); ?>" data-group-id="<?php bp_media_group_id(); ?>" data-document-title="<?php echo esc_html( $filename ); ?>">
						<span><?php echo esc_html( $photo_title ); ?></span>
						<i class="media-document-id" data-item-id="<?php echo esc_attr( bp_media_id() ); ?>" style="display: none;"></i>
						<i class="media-document-attachment-id" data-item-id="<?php echo esc_attr( bp_get_media_attachment_id() ); ?>" style="display: none;"></i>
						<i class="media-document-type" data-item-id="<?php echo esc_attr( $media_type ); ?>" style="display: none;"></i>
					</a>
				</div>
				<div class="media-folder_modified">
					<div class="media-folder_details__bottom">
						<span class="media-folder_date"><?php bp_media_date_created(); ?></span>
						<?php
						if ( ! bp_is_user() ) {
							?>
							<span class="media-folder_author"><?php esc_html_e( 'by ', 'buddyboss' ); ?><a href="<?php echo trailingslashit( bp_core_get_user_domain( bp_get_media_user_id() ) . bp_get_media_slug() ); ?>"><?php bp_media_user_id(); ?></a></span>
							<?php
						}
						?>
					</div>
				</div>
				<?php
				if ( bp_is_active( 'groups' ) ) {
					?>
					<div class="media-folder_group">
						<div class="media-folder_details__bottom">
							<?php
							$group_id = bp_get_media_group_id();
							if ( $group_id > 0 ) {
								// Get the group from the database.
								$group 		  = groups_get_group( $group_id );
								$group_name   = isset( $group->name ) ? bp_get_group_name( $group ) : '';
								$group_link   = sprintf( '<a href="%s" class="bp-group-home-link %s-home-link">%s</a>', esc_url( $link ), esc_attr( bp_get_group_slug( $group ) ), esc_html( bp_get_group_name( $group ) ) );
								$group_status = bp_get_group_status( $group );
								?>
								<span class="media-folder_group"><?php echo wp_kses_post( $group_link ); ?></span>
								<span class="media-folder_status"><?php echo ucfirst( $group_status ); ?></span>
								<?php
							} else {
								?>
								<span class="media-folder_group"> </span>
								<?php
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
				<div class="media-folder_visibility">
					<div class="media-folder_details__bottom">
						<?php
						if ( bp_is_active( 'groups' ) ) {
							$group_id = bp_get_media_group_id();
							if ( $group_id > 0 ) {
								?>
								<span class="bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php bp_media_privacy(); ?>
								</span>
								<?php
							} else {
								?>
								<span id="privacy-<?php echo esc_attr( bp_get_media_id() ); ?>">
									<?php bp_media_privacy(); ?>
								</span>
								<?php
							}
						} else {
							?>
							<span>
								<?php bp_media_privacy(); ?>
							</span>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</a>
</div>
