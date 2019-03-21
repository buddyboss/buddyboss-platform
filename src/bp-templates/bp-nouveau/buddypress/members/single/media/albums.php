<?php
/**
 * BuddyBoss - Members Media Albums
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php global $media_album_template; ?>

<?php if ( bp_is_my_profile() ) : ?>

    <div class="flex">
        <div class="push-right bb-media-actions">
            <a href="#" id="bb-create-album" class="bb-create-album button small outline">+ <?php _e( 'Create Album', 'buddyboss' ); ?></a>
            <a href="#" id="bb-add-media" class="bb-add-media button small outline"><?php _e( 'Add Media', 'buddyboss' ); ?></a>
        </div>
    </div>

    <?php bp_get_template_part( 'members/single/media/uploader' ); ?>
    <?php bp_get_template_part( 'members/single/media/create-album' ); ?>

<?php endif; ?>

<?php bp_nouveau_member_hook( 'before', 'media_album_content' ); ?>

<?php if ( bp_has_albums() ) : ?>

    <div id="members-photos-dir-list" class="bb-member-photos bb-photos-dir-list">

		<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        <ul class="bb-photo-list grid">
			<?php endif; ?>

			<?php
			while ( bp_album() ) :
				bp_the_album();
				?>

                <div id="members-albums-dir-list" class="bb-member-albums bb-albums-dir-list">
                    <ul class="bb-member-albums-items" ref="albumsList" aria-live="assertive" aria-relevant="all">
                        <li class="album-single-view no-photos">

                            <div class="bb-single-album-header text-center">
                                <h4 class="bb-title"><?php bp_album_title(); ?></h4>
                                <p>
                                    <span><?php echo bp_core_time_since( $media_album_template->album->date_created ); ?></span><span class="bb-sep">&middot;</span><span><?php echo $media_album_template->album->total_items; ?> <?php _e( 'photos', 'buddyboss' ); ?></span>
                                </p>
                            </div>

                            <div class="bb-album-actions">
                                <a class="bb-delete button small outline error" href="#"><?php _e( 'Delete Album', 'buddyboss' ); ?></a>
                                <a class="bb-add-photos button small outline" href="#"><?php _e( 'Add Photos', 'buddyboss' ); ?></a>
			                    <?php $privacy_options = BP_Media_Privacy::instance()->get_visibility_options(); ?>
                                <select>
				                    <?php foreach ( $privacy_options as $k => $option ) {
					                    ?>
                                        <option value="<?php echo $k; ?>"><?php echo $option; ?></option>
					                    <?php
				                    } ?>
                                </select>
                            </div>

<!--                            <ul class="bb-album-photos-list" ref="albumPhotosList" aria-live="assertive" aria-relevant="all">-->
<!---->
<!--                                <li v-for="(date_photos,index_dt) in date_wise_photos">-->
<!---->
<!--				                    --><?php //do_action( 'buddyboss_media_before_album_photos_list_date' ); ?>
<!---->
<!--                                    <header class="bb-member-photos-header flex align-items-center">-->
<!--                                        <div class="bb-photos-date">{{dateheading(index_dt)}}</div>-->
<!--                                        <div class="push-right bb-photos-meta">-->
<!--                                            <a data-balloon="--><?php //_e( 'Delete', 'buddyboss' ); ?><!--" data-balloon-pos="up" class="bb-delete" href="#" v-if="can_edit" v-on:click.prevent="deleteSelected($event,index_dt)">&nbsp;</a>-->
<!--                                            <a data-balloon="--><?php //_e( 'Select All', 'buddyboss' ); ?><!--" data-balloon-pos="up" class="bb-select" href="#" v-if="can_edit && date_selected_all.indexOf(index_dt) == -1" v-on:click.prevent="selectDated(index_dt)">&nbsp;</a>-->
<!--                                            <a data-balloon="--><?php //_e( 'Unselect All', 'buddyboss' ); ?><!--" data-balloon-pos="up" class="bb-select selected" v-if="can_edit && date_selected_all.length && date_selected_all.indexOf(index_dt) != -1" href="#" v-on:click.prevent="deselectDated(index_dt)">&nbsp;</a>-->
<!--                                        </div>-->
<!--                                    </header>-->
<!---->
<!--                                    <ul class="bb-photo-list grid">-->
<!--                                        <li v-for="(photo,index) in date_photos" v-bind:key="photo.id" class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3">-->
<!---->
<!--						                    --><?php //do_action( 'buddyboss_media_before_album_photos_list_photo_item' ); ?>
<!---->
<!--                                            <div class="bb-photo-thumb" :class="{ selected : photo.selected }">-->
<!--                                                <a class="bb-photo-cover-wrap" :class="{ loading : photo.index === selectedPhotoIndex }" href="#" @click.prevent="openTheater($event,photo.index)"><img :src="photo.thumb" /></a>-->
<!--                                                <div class="bb-media-check-wrap">-->
<!--                                                    <input v-bind:id="'bb-photo-' + photo.id" class="bb-custom-check" type="checkbox" v-if="can_edit" :value="{id:photo.id,index:photo.index}" v-model="checkedPhotos" />-->
<!--                                                    <label v-bind:for="'bb-photo-' + photo.id"></label>-->
<!--                                                </div>-->
<!--                                            </div>-->
<!---->
<!--						                    --><?php //do_action( 'buddyboss_media_after_album_photos_list_photo_item' ); ?>
<!---->
<!--                                        </li>-->
<!--                                    </ul>-->
<!---->
<!--				                    --><?php //do_action( 'buddyboss_media_after_album_photos_list_date' ); ?>
<!---->
<!--                                </li>-->
<!--                            </ul>-->
<!--                        </li>-->
<!--                    </ul>-->

                </div>

			<?php endwhile; ?>

			<?php if ( bp_album_has_more_items() ) : ?>

                <li class="load-more">
                    <a href="<?php bp_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
                </li>

			<?php endif; ?>

			<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        </ul>
	<?php endif; ?>

    </div>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-media-album-none' ); ?>

<?php endif; ?>


<?php
bp_nouveau_member_hook( 'after', 'media_album_content' );
