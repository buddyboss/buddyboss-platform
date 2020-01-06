<?php
/**
 * BuddyBoss - Media Single Album
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php
global $media_album_template;
if  ( function_exists( 'bp_is_group_single' ) && bp_is_group_single() && bp_is_group_document_folder() ) {
	$action_variables = bp_action_variables();
	$album_id = (int) $action_variables[1];
} else  {
	$album_id = (int) bp_action_variable( 0 );
}

$bradcrumbs = bp_media_document_bradcrumb( $album_id );

?>

<?php if ( bp_has_albums( array( 'include' => $album_id, 'type' => 'document' ) ) ) : ?>
	<?php
	while ( bp_album() ) :
		bp_the_album();

	    $total_media = $media_album_template->album->media['total'];
		?>
        <div id="bp-media-single-folder">
            <div class="album-single-view" <?php echo $total_media == 0 ? 'no-photos' : ''; ?>>
                <div class="bp-media-header-wrap">
                    <div class="bb-single-album-header text-center">
                        <h4 class="bb-title" id="bp-single-album-title"><?php bp_album_title(); ?></h4>
	                    <?php
	                        if ( '' !== $bradcrumbs ) {
	                        	?>
		                        
                                <?php echo  $bradcrumbs; ?>         
                                
	                        	<?php
	                        }
	                    ?>
                        <?php if ( bp_is_my_profile() || ( bp_is_group() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) : ?>
                            <!-- <input type="text" value="<?php //bp_album_title(); ?>" placeholder="<?php //_e( 'Title', 'buddyboss' ); ?>" id="bb-album-title" style="display: none;" />
                            <a href="#" id="bp-edit-folder-title"><?php //_e( 'edit', 'buddyboss' ); ?></a>
                            <a href="#" id="bp-save-folder-title" style="display: none;" ><?php //_e( 'save', 'buddyboss' ); ?></a>
                            <a href="#" id="bp-cancel-edit-album-title" style="display: none;" ><?php //_e( 'cancel', 'buddyboss' ); ?></a> -->
                        <?php endif; ?>
                        <!-- <p>
                            <span><?php //bp_core_format_date( $media_album_template->album->date_created ); ?></span><span class="bb-sep">&middot;</span><span><?php //printf( _n( '%s document', '%s documents', $media_album_template->album->media['total'], 'buddyboss' ), number_format_i18n( $media_album_template->album->media['total'] ) ); ?></span>
                        </p> -->
                    </div> <!-- .bb-single-album-header -->

                    <?php if ( bp_is_my_profile() || ( bp_is_group() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) : ?>

                        <div class="bb-media-actions">
                            <!-- <a class="bb-delete button small outline error" id="bb-delete-folder" href="#">
                                <i class="bb-icon-upload"></i>
                                <?php //_e( 'Delete Folder', 'buddyboss' ); ?>
                            </a> -->

                            <a class="bp-add-document button small outline" id="bp-add-document" href="#" >
                                <i class="bb-icon-upload"></i><?php _e( 'Add Documents', 'buddyboss' ); ?>
                            </a>

                            <a href="#" id="bb-create-folder-child" class="bb-create-folder button small outline">
                                <i class="bb-icon-plus"></i><?php _e( 'Create Folder', 'buddyboss' ); ?>
                            </a>

                            <?php if ( bp_is_my_profile() && ! bp_is_group() ) : ?>

                                <?php $privacy_options = BP_Media_Privacy::instance()->get_visibility_options(); ?>

                                <select id="bb-folder-privacy">
                                    <?php foreach ( $privacy_options as $k => $option ) { ?>
                                        <?php $selected = ''; if ( $k == bp_get_album_privacy() ) $selected = 'selected="selectred"' ; ?>
                                        <option <?php echo $selected; ?> value="<?php echo $k; ?>"><?php echo $option; ?></option>
                                    <?php } ?>
                                </select>

                            <?php endif; ?>

                            <div class="media-folder_items">
                                <div class="media-folder_actions">
                                    <a href="#" class="media-folder_action__anchor">
                                        <i class="bb-icon-menu-dots-v"></i>
                                    </a>
                                    <div class="media-folder_action__list">
                                        <ul>
                                            <li>
                                                <!-- <div class="media-edit-folder">
                                                    <input type="text" value="<?php //bp_album_title(); ?>" placeholder="<?php //_e( 'Title', 'buddyboss' ); ?>" id="bb-album-title" style="display: none;" />
                                                    <a href="#" id="bp-edit-folder-title"><i class="bb-icon-edit"></i><?php //_e( 'Edit Folder Name', 'buddyboss' ); ?></a>
                                                    <a href="#" id="bp-save-folder-title" style="display: none;" ><?php //_e( 'save', 'buddyboss' ); ?></a>
                                                    <a href="#" id="bp-cancel-edit-album-title" style="display: none;" ><?php //_e( 'cancel', 'buddyboss' ); ?></a>
                                                </div> -->
                                                <a id="bp-edit-folder-open" href="#"><i class="bb-icon-edit"></i> Edit Folder</a>
                                            </li>
                                            <li><a href="#" id="bb-delete-folder"><i class="bb-icon-trash"></i><?php _e( 'Delete Folder', 'buddyboss' ); ?></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div> <!-- .media-folder_items -->

                        </div> <!-- .bb-media-actions -->

                    <?php bp_get_template_part( 'media/document-uploader' ); ?>
                    <?php bp_get_template_part( 'media/create-child-folder' ); ?>
                    <?php bp_get_template_part( 'media/edit-child-folder' ); ?>

                    <?php endif; ?>
                </div> <!-- .bp-media-header-wrap -->

                <?php //bp_get_template_part( 'media/actions' ); ?>

                <div id="media-stream" class="media" data-bp-list="media" data-bp-media-type="document">

                    <div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-document-loading' ); ?></div>

                </div>

            </div>
        </div>
	<?php endwhile; ?>
<?php endif; ?>
