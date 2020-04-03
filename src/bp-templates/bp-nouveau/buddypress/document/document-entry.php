<?php
/**
 * BuddyBoss - Document Entry
 *
 * @since BuddyBoss 1.0.0
 */

$attachment_id    = bp_get_document_attachment_id();
$extension        = '';
$can_download_btn = false;
$can_manage_btn   = false;
$can_view         = false;
if ( $attachment_id ) {
	$extension        = bp_document_extension( $attachment_id );
	$svg_icon         = bp_document_svg_icon( $extension );
	$link             = bp_document_download_link( $attachment_id );
	$text_attachment_url = wp_get_attachment_url( $attachment_id );
	$move_class       = 'ac-document-move';
	$listing_class    = 'ac-document-list';
	$type             = 'document';
	$document_privacy = bp_document_user_can_manage_document( bp_get_document_id(), bp_loggedin_user_id() );
	$can_download_btn = ( true === (bool) $document_privacy['can_download'] ) ? true : false;
	$can_manage_btn   = ( true === (bool) $document_privacy['can_manage'] ) ? true : false;
	$can_view         = ( true === (bool) $document_privacy['can_view'] ) ? true : false;
	$group_id         = bp_get_document_group_id();
	$title            = bp_get_document_title();

	if('pdf' === $extension || 'pptx' === $extension || 'pps' === $extension || 'xls' === $extension || 'xlsx' === $extension || 'pps' === $extension || 'ppt' === $extension || 'pptx' === $extension || 'doc' === $extension || 'docx' === $extension || 'dot' === $extension || 'rtf' === $extension || 'wps' === $extension || 'wpt' === $extension || 'dotx' === $extension || 'potx' === $extension || 'xlsm' === $extension )  {
		$attachment_url = wp_get_attachment_url( bp_get_document_preview_attachment_id()  );
	}

} else {
	$svg_icon       = bp_document_svg_icon( 'folder' );
	$link           = bp_get_folder_link();
	$move_class     = 'ac-folder-move';
	$listing_class  = 'ac-folder-list';
	$type           = 'folder';
	$folder_privacy = bp_document_user_can_manage_folder( bp_get_document_folder_id(), bp_loggedin_user_id() );
	$can_manage_btn = ( true === (bool) $folder_privacy['can_manage'] ) ? true : false;
	$can_view       = ( true === (bool) $folder_privacy['can_view'] ) ? true : false;
	$group_id       = bp_get_document_folder_group_id();
	$title          = bp_get_folder_title();
}

?>
<div class="media-folder_items <?php echo $listing_class; ?>" data-activity-id="<?php bp_document_activity_id(); ?>" data-id="<?php bp_document_id(); ?>">
	<div class="media-folder_icon">
		<a href="<?php echo esc_url( $link ); ?>">
			<i class="<?php echo $svg_icon; ?>"></i>
		</a>
	</div>
	<div class="media-folder_details">
		<a class="media-folder_name
		<?php if( $attachment_id ) { echo 'bb-open-document-theatre'; }?>"
		href="<?php echo esc_url( $link ); ?>"
		data-extension="<?php echo $extension ? $extension : ''; ?>"
		data-preview="<?php echo $attachment_url ? $attachment_url : ''; ?>"
		data-text-preview="<?php echo $text_attachment_url ? $text_attachment_url : ''; ?>" >
			<span><?php echo $title; ?></span><?php echo $extension ? '.' . $extension : ''; ?>
			<i class="media-document-id" data-item-id="<?php echo base64_encode( bp_get_document_id() ); ?>" style="display: none;"></i>
			<i class="media-document-attachment-id" data-item-id="<?php echo base64_encode( bp_get_document_attachment_id() ); ?>" style="display: none;"></i>
			<i class="media-document-type" data-item-id="<?php echo esc_attr( $type ); ?>" style="display: none;"></i>
		</a>
		<div class="media-folder_name_edit_wrap">
			<input type="text" value="" class="media-folder_name_edit" />
			<?php
            if ( wp_is_mobile() ) { ?>
				<a href="#" class="name_edit_cancel button small"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
				<a href="#" class="name_edit_save button small pull-right"><?php esc_html_e( 'Save', 'buddyboss' ); ?></a>
			    <?php
            } ?>
		</div>
	</div>
	<div class="media-folder_modified">
		<div  class="media-folder_details__bottom">
			<span class="media-folder_date"><?php bp_document_date(); ?></span>
			<?php
            if ( ! bp_is_user() ) { ?>
				<span class="media-folder_author">by <?php bp_document_author(); ?></span>
			    <?php
            } ?>
		</div>
	</div>
	<div class="media-folder_visibility">
		<div  class="media-folder_details__bottom">
			<span><?php bp_document_privacy(); ?></span>
			<select id="bb-folder-privacy" class="hide">
				<?php foreach ( bp_document_get_visibility_levels() as $key => $privacy ) : ?>
					<option value="<?php echo $key; ?>"><?php echo $privacy; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
    <div class="media-folder_actions">
        <a href="#" class="media-folder_action__anchor">
            <i class="bb-icon-menu-dots-v"></i>
        </a>
        <div class="media-folder_action__list">
            <ul>
                <?php
                if ( $attachment_id && $can_download_btn ) { ?>
                    <li class="download_file"><a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Download', 'buddyboss' ); ?></a></li>
                    <?php
                }
                if ( $can_manage_btn ) {
	                ?>
	                <li class="rename_file"><a href="#" data-type="<?php echo esc_attr( $type ); ?>" class="ac-document-rename"><?php esc_html_e( 'Rename', 'buddyboss' ); ?></a></li>
	                <li class="move_file"><a href="#" class="<?php echo $move_class; ?>"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a></li>
	                <?php
	                if ( 'document' === $type && 0 === $group_id ) {
		                ?>
                        <li class="privacy_file"><a href="#" class="ac-document-privacy"><?php esc_html_e( 'Edit Privacy', 'buddyboss' ); ?></a></li>
		                <?php
	                }
	                ?>
	                <li class="delete_file"><a class="document-file-delete" data-item-preview-attachment-id="<?php echo esc_attr( bp_get_document_preview_attachment_id() ); ?>" data-item-attachment-id="<?php echo esc_attr( bp_get_document_attachment_id() ); ?>" data-item-id="<?php echo esc_attr( bp_get_document_id() ); ?>" data-type="<?php echo esc_attr( $type ); ?>" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a></li>
		            <?php
                }
                ?>
            </ul>
        </div>
    </div>
</div>
