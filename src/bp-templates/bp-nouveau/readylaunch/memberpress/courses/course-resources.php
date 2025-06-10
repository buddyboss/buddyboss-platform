<?php
/**
 * Template for displaying course resources in MemberPress Courses integration.
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 *
 * @since 2.7.20
 */

defined( 'ABSPATH' ) || exit;
?>
<h2><?php esc_html_e( 'Resources', 'buddyboss-pro' ); ?></h2>

<?php if ( ! empty( $resources->downloads ) ) : ?>
	<div id="downloads" class="mpcs-section mpcs-resource-section">
		<div class="mpcs-section-header active">
			<div class="mpcs-section-title">
				<span class="mpcs-section-title-text">
					<?php echo esc_html( ! empty( $resources->labels['downloads'] ) ? $resources->labels['downloads'] : __( 'Downloads', 'buddyboss-pro' ) ); ?>
				</span>
			</div>
		</div><!-- mpcs-section-header -->
		<div class="mpcs-lessons" style="display: block;">
			<?php foreach ( $resources->downloads as $key => $download ) : ?>
				<?php if ( isset( $download->id ) ) : ?>
					<?php
					$metadata  = array();
					$file_type = '';
					$file_size = '';
					// Check if it's an attachment.
					if ( isset( $download->type ) && 'attachment' === $download->type ) {
						$metadata  = wp_get_attachment_metadata( $download->id );
						$file_type = get_post_mime_type( $download->id );
						$file_size = isset( $metadata['filesize'] ) ? $metadata['filesize'] : '';
					} elseif ( isset( $download->type ) && 'download' === $download->type && class_exists( '\memberpress\downloads\models\File' ) ) {
						// Check if it's a download and the Downloads add-on is active.
						$file      = new \memberpress\downloads\models\File( $download->id );
						$file_size = $file->filesize;
						$file_type = $file->filetype;
					}
					?>
					<div id="mpcs-lesson-<?php echo esc_attr( $download->id ); ?>" class="mpcs-lesson">
						<a href="<?php echo esc_url( $download->url ); ?>" class="mpcs-lesson-row-link" target="_blank">
							<div class="mpcs-lesson-link flex">
								<i class="mpcs-download" style="align-self: start;"></i>
								<div class="mpcs-file-info">
									<span class="mpcs-file-title">
										<?php echo esc_html( $download->title ); ?>
									</span>
									<div class="bb-mpcs-file-meta" style="padding-top: 2px; font-size: 11px; color: grey;">
										<?php if ( ! empty( $file_size ) ) : ?>
											<span class="bb-mpcs-filesize">
												<?php echo esc_html( bp_core_format_size_units( $file_size, true ) ); ?>
											</span>
										<?php endif; ?>

										<?php if ( ! empty( $file_type ) ) : ?>
											<span class="bb-mpcs-file-type" style="text-transform: uppercase;">
												<?php echo esc_html( bb_mpcs_get_normalized_file_type( $file_type ) ); ?>
											</span>
										<?php endif; ?>
									</div>
								</div>
							</div>
							<div class="mpcs-lesson-button">
								<span class="mpcs-button">
									<span class="btn"><?php esc_html_e( 'View', 'buddyboss-pro' ); ?></span>
								</span>
							</div>
						</a>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div><!-- mpcs-lessons -->
	</div>
<?php endif; ?>

<?php if ( ! empty( $resources->links ) ) : ?>
	<div id="links" class="mpcs-section mpcs-resource-section">
		<div class="mpcs-section-header active">
			<div class="mpcs-section-title">
				<span class="mpcs-section-title-text">
					<?php echo esc_html( ! empty( $resources->labels['links'] ) ? $resources->labels['links'] : __( 'Links', 'buddyboss-pro' ) ); ?>
				</span>
			</div>
		</div><!-- mpcs-section-header -->
		<div class="mpcs-lessons" style="display: block;">
			<?php foreach ( $resources->links as $key => $link ) : ?>
				<div id="mpcs-lesson-<?php echo esc_attr( $link->id ); ?>" class="mpcs-lesson">
					<a href="<?php echo esc_url( $link->url ); ?>" class="mpcs-lesson-row-link" target="_blank">
						<div class="mpcs-lesson-link">
							<i class="mpcs-link"></i>
							<?php echo esc_html( $link->label ? $link->label : $link->url ); ?>
						</div>
						<div class="mpcs-lesson-button">
							<span class="mpcs-button">
								<span class="btn"><?php esc_html_e( 'Visit', 'buddyboss-pro' ); ?></span>
							</span>
						</div>
					</a>
				</div>
			<?php endforeach; ?>
		</div><!-- mpcs-lessons -->
	</div>
<?php endif; ?>

<?php if ( ! empty( $resources->custom ) && ! empty( $resources->custom[0]->content ) ) : ?>
	<div id="custom" class="mpcs-resource-section">
		<?php if ( ! empty( $resources->labels['custom'] ) ) : ?>
			<h3><?php echo esc_html( $resources->labels['custom'] ); ?></h3>
		<?php endif; ?>
		<?php echo wpautop( wp_kses_post( $resources->custom[0]->content ) ); ?>
	</div>
<?php endif; ?>
