<?php

$folder_link      = bp_get_folder_folder_link();
?>

<li data-bp-item-id="<?php bp_get_folder_folder_id(); ?>" data-bp-item-component="document" class="search-document-list">
	<div class="list-wrap">
		<div class="item">

			<div class="media-folder_items ac-folder-list">
				<div class="media-folder_icon">
					<a href="<?php echo esc_url( $folder_link ); ?>">
						<i class="bb-icon-folder-stacked"></i>
					</a>
				</div>

				<div class="media-folder_details">
					<a class="media-folder_name " href="<?php echo esc_url( $folder_link ); ?>">
						<span><?php echo bp_get_folder_folder_title(); ?></span>
					</a>
				</div>

				<div class="media-folder_modified">
					<div class="media-folder_details__bottom">
						<span class="media-folder_date"><?php bp_document_folder_date(); ?></span>
						<span class="media-folder_author"><?php esc_html_e( 'by ', 'buddyboss' ); ?><a href="<?php echo trailingslashit(bp_core_get_user_domain( bp_get_document_folder_user_id() ) . bp_get_document_slug() ) ; ?>"><?php bp_folder_author(); ?></a></span>
					</div>
				</div>

				<?php
				if ( bp_is_active( 'groups' ) ) {
					?>
					<div class="media-folder_group">
						<div class="media-folder_details__bottom">
							<?php
							$group_id = bp_get_document_group_id();
							if ( $group_id > 0 ) {
								// Get the group from the database.
								$group = groups_get_group( $group_id );

								$group_name = isset( $group->name ) ? bp_get_group_name( $group ) : '';
								$group_link = sprintf(
										'<a href="%s" class="bp-group-home-link %s-home-link">%s</a>',
										esc_url( trailingslashit( bp_get_group_permalink( $group ) . bp_get_document_slug() ) ),
										esc_attr( bp_get_group_slug( $group ) ),
										esc_html( bp_get_group_name( $group ) )
								);
								$group_status     = bp_get_group_status( $group );
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
							$group_id = bp_get_folder_group_id();
							if ( $group_id > 0 ) {
								?>
								<span class="bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php bp_document_folder_privacy(); ?>
								</span>
								<?php
							} else {
								?>
								<span id="privacy-<?php echo esc_attr( bp_get_folder_folder_id() ); ?>">
									<?php bp_document_folder_privacy(); ?>
								</span>
								<?php
							}
						} else {
							?>
							<span>
								<?php bp_document_folder_privacy(); ?>
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
