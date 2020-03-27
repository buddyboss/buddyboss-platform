<?php
/**
 * BuddyBoss - Activity Media
 *
 * @since BuddyBoss 1.0.0
 */

global $document_template;

$attachment_id     = bp_get_document_attachment_id();
$extension         = bp_document_extension( $attachment_id );
$svg_icon          = bp_document_svg_icon( $extension );
$svg_icon_download = bp_document_svg_icon( 'download' );
$url               = wp_get_attachment_url( $attachment_id );
$filename          = basename( get_attached_file( $attachment_id ) );
$size              = size_format(filesize( get_attached_file( $attachment_id ) ) );
$download_url      = bp_document_download_link( $attachment_id );
?>

	<div class="bb-activity-media-elem document-activity <?php echo wp_is_mobile() ? 'is-mobile' : ''; ?>" data-id="<?php bp_document_id(); ?>">
		<div class="document-description-wrap">
			<a href="<?php echo esc_url( $download_url ); ?>" target="_blank" class="entry-img" data-id="<?php bp_document_id(); ?>" data-activity-id="<?php bp_document_activity_id(); ?>">
				<i class="<?php echo $svg_icon; ?>" ></i>
			</a>
			<a href="<?php echo esc_url( $download_url ); ?>" target="_blank" class="document-detail-wrap">
				<span class="document-title"><?php echo $filename; ?></span>
				<span class="document-description"><?php echo $size; ?></span>
				<span class="document-helper-text"><?php esc_html_e( '- Click to Download', 'buddyboss' ); ?></span>
			</a>
		</div>

		<div class="document-action-wrap">
			<a href="<?php echo esc_url( $download_url ); ?>" target="_blank" class="document-action_download" data-id="<?php bp_document_id(); ?>" data-activity-id="<?php bp_document_activity_id(); ?>" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'Download', 'buddyboss' ); ?>">
				<i class="bb-icon-download"></i>
			</a>

			<?php if ( bp_loggedin_user_id() === bp_get_document_user_id() ) { ?>

			<a href="#" target="_blank" class="document-action_more" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
				<i class="bb-icon-menu-dots-v"></i>
			</a>
			<div class="document-action_list">
				<ul>
					<li class="move_file"><a href="#" class="ac-document-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a></li>
					<li class="delete_file"><a href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a></li>
				</ul>
			</div>

			<?php } ?>

		</div>

		<?php if ( 'mp3' === $extension || 'wav' === $extension || 'ogg' === $extension ) { ?>
			<div class="document-audio-wrap">
				<audio controls>
					<source src="<?php echo esc_url( $url ); ?>" type="audio/mpeg">
					<?php esc_html_e( 'Your browser does not support the audio element.', 'buddyboss' ); ?>
				</audio>
			</div>
		<?php }
		if('pdf' === $extension || 'pptx' === $extension || 'pps' === $extension || 'xls' === $extension || 'xlsx' === $extension || 'pps' === $extension || 'ppt' === $extension || 'pptx' === $extension || 'doc' === $extension || 'docx' === $extension || 'dot' === $extension || 'rtf' === $extension || 'wps' === $extension || 'wpt' === $extension || 'dotx' === $extension || 'potx' === $extension || 'xlsm' === $extension )  { 
			$attachment_url = wp_get_attachment_url( bp_get_document_preview_attachment_id()  );
		?>
			<div class="document-preview-wrap">
				<img src="<?php echo $attachment_url; ?>" alt="" />
			</div><!-- .document-preview-wrap -->

		<?php } 

		if( filesize( get_attached_file( $attachment_id ) ) / 1e+6 < 3 ) { ?>


			<?php if ( 'css' == $extension || 'txt' == $extension || 'html' == $extension || 'htm' == $extension || 'js' == $extension || 'csv' == $extension ) { ?>
				<?php
					$file_open = fopen($url, 'r');
					$file_data = fread($file_open, 10000);
					$more_text = false;
					if(strlen($file_data) >= 9999){
						$file_data.='...';
						$more_text = true;
					}
					fclose($file_open);
				?>
				<div class="document-text-wrap">

					<div class="document-text" data-extension="<?php echo $extension; ?>">
						<textarea class="document-text-file-data-hidden" style="display: none;"><?php
							echo $file_data;
							?>
						</textarea>
					</div>

					<div class="document-expand">
						<a href="#" class="document-expand-anchor"><i class="bb-icon-plus document-icon-plus"></i> <?php esc_html_e( 'Click to expand', 'buddyboss' ); ?></a>
					</div>

				</div> <!-- .document-text-wrap -->

				<div class="document-action-wrap">
					
					<a href="#" class="document-action_collapse" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Collapse', 'buddyboss' ); ?>"><i class="bb-icon-arrow-up document-icon-collapse"></i></a>
					
					<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="document-action_download" data-id="<?php bp_document_id(); ?>" data-activity-id="<?php bp_document_activity_id(); ?>" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'Download', 'buddyboss' ); ?>">
						<i class="bb-icon-download document-icon-download"></i>
					</a>

					<?php if ( bp_loggedin_user_id() === bp_get_document_user_id() ) { ?>

						<a href="#" target="_blank" class="document-action_more" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
							<i class="bb-icon-menu-dots-v document-icon-download-more"></i>
						</a>
						<div class="document-action_list">
							<ul>
								<li class="move_file"><a href="#" class="ac-document-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a></li>
								<li class="delete_file"><a href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a></li>
							</ul>
						</div>

					<?php } ?>
					
				</div> <!-- .document-action-wrap -->

				<?php
					if( $more_text == true ){
						echo esc_html_e( '<div class="more_text_view">This file was truncated for preview. Please <a href="'.$url.'">download</a> to view the full file. </div>', 'buddyboss' );
					}
				?>
			<?php } 
		} ?>
		
	</div> <!-- .bb-activity-media-elem -->