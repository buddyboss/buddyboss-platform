<?php
/**
 * Handle bbPress Reply Attachments
 *
 * @class       BBM_Attachments
 * @category    Class
 * @author      BuddyBoss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BBM_BBPress_Media Class
 */
class BBM_BBPress_Media {

	/** @var BBM_BBPress_Media The single instance of the class */
	protected static $_instance = null;


	function __construct() {
		//add_action( 'bbp_init', array( $this, 'hooks' ) );
		$this->hooks();
	}

	/**
	 * Main BBM_Attachments Instance
	 *
	 * Ensures only one instance of BBM_Attachments is loaded or can be loaded.
	 * @static
	 * @return BBM_Attachments Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}


	public function hooks() {


		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_footer', array( $this, 'script_templates' ) );

		add_action( 'bbp_theme_before_reply_form_submit_wrapper', array( $this, 'embed_form' ) );
		add_action( 'bbp_theme_before_topic_form_submit_wrapper', array( $this, 'embed_form' ) );

		add_action( 'edit_post', array( $this, 'edit_reply' ), 5, 2 );
		add_action( 'bbp_new_reply', array( $this, 'new_reply' ), 5, 5 );
		add_action( 'bbp_new_topic', array( $this, 'new_topic' ), 10, 4);


		add_filter( 'bbp_get_reply_content', array( $this, 'embed_attachments' ), 10, 2 );
		add_filter( 'bbp_get_topic_content', array( $this, 'embed_attachments' ), 10, 2 );

		add_filter( 'bp_before_activity_add_parse_args', array( $this, 'append_media_content' ), 10, 1 );

	}

	/**
	 *  Load script and style
	 */
	public function load_scripts() {

		if ( bbm_is_bbpress() ) {
			$this->assets();
		}
	}


	/**
	 * Load CSS/JS
	 * @return void
	 */
	public function assets() {


		if ( bbm_is_bbpress() ) {

			wp_localize_script( 'buddyboss-media-main', 'bbpress_media', 'true' );
		}
		// Localization
	}

	/**
	 * Print inline templates
	 * @return void
	 */
	public function script_templates() {

                if ( bbp_is_topic_tag_edit() ) {
                    return;
                }

		//Check show lightbox option is checked
		$show_uploadbox = buddyboss_media()->option('show_uploadbox');

		?>
		<script type="text/html" id="buddyboss-bbpress-media-tpl-add-photo">
			<div class="buddyboss-bbpress-media-add-photo">
				<!-- Fake add photo button will be clicked from js -->
				<button type="button" class="open-uploader-button buddyboss-bbpress-media-add-photo-button button submit" style="<?php echo ( ! empty( $show_uploadbox ) ) ? 'display:none;' : ''; ?>"></button>
				<button type="button" id="browse-file-button" class="browse-file-button buddyboss-bbpress-media-add-photo-button button submit" style="<?php echo ( 'yes' === $show_uploadbox  ) ? '' :  'display:none;'; ?>"></button>

				<div class="buddyboss-media-progress">
					<div class="buddyboss-media-progress-value">0%</div>
					<progress class="buddyboss-media-progress-bar" value="0" max="100"></progress>
				</div>
				<div class="buddyboss-bbpress-media-photo-uploader"></div>
			</div><!-- #buddyboss-media-add-photo -->
		</script>

		<script type="text/html" id="buddyboss-bbpress-media-tpl-preview">
			<div class="buddyboss-bbpress-media-preview">
				<div class="clearfix buddyboss-bbpress-media-preview-inner">

				</div>

				<?php $component_slug = buddyboss_media_component_slug(); ?>

				<?php if( bp_is_my_profile() && bp_is_current_component( $component_slug ) ):?>
					<div id="buddyboss-bbpress-media-bulk-uploader-reception-fake" class="image-drop-box">
						<h3 class="buddyboss-media-drop-instructions"><?php _e( 'Drop files anywhere to upload', 'buddyboss-media' );?></h3>
						<p class="buddyboss-media-drop-separator"><?php _e( 'or', 'buddyboss-media' );?></p>
						<a title="<?php _e( 'Select Files', 'buddyboss-media' );?>" class="browse-file-button button" href="#"> <?php _e( 'Select Files', 'buddyboss-media' );?></a>
					</div>

					<?php
					/* show only image drop zone and hide the rest of activity update form on uploads and albums section in user profile */
					?>
					<style type="text/css">
						body.bp-user.my-account.<?php echo $component_slug;?> #buddyboss-media-add-photo,
						body.bp-user.my-account.<?php echo $component_slug;?> #whats-new,
						body.bp-user.my-account.<?php echo $component_slug;?> #whats-new-options{
							display: none !important;
						}
					</style>

				<?php endif; ?>
			</div><!-- #buddyboss-media-preview -->
		</script>

		<?php if( is_user_logged_in() ):?>

		<div id="buddyboss-bbpress-media-bulk-uploader-wrapper" style="display:none">
			<div id="buddyboss-bbpress-media-bulk-uploader">
				<div class="buddyboss-bbpress-media-bulk-uploader-uploaded">
					<div class="images clearfix">

					</div>
				</div>
				<div id="buddyboss-bbpress-media-bulk-uploader-reception" class="image-drop-box">
					<h3 class="buddyboss-media-drop-instructions"><?php _e( 'Drop files anywhere to upload', 'buddyboss-media' );?></h3>
					<p class="buddyboss-media-drop-separator"><?php _e( 'or', 'buddyboss-media' );?></p>
					<a class="logo-bbpress-file-browser-button browse-file-button" title="Select image" > <?php _e( 'Select Files', 'buddyboss-media' );?></a>
				</div>

				<input type="submit" id="buddyboss-bbpress-media-attach" value="<?php esc_attr_e( 'Attach Photos', 'buddyboss-media' );?>" />

			</div>
		</div>
	<?php endif; ?>

		<?php
	}

	/**
	 * Convenince method for getting main plugin options.
	 *
	 * @since BuddyBoss Wall (1.0.0)
	 */
	public function option( $key ) {
		return buddyboss_media()->option( $key );
	}

	/**
	 * New reply
	 * @param $reply_id
	 * @param $topic_id
	 * @param $forum_id
	 * @param $anonymous_data
	 * @param $reply_author
	 */
	public function new_reply( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author ) {

		$this->save_attachments( $reply_id );
	}

	/**
	 * New topic
	 * @param $topic_id
	 * @param $forum_id
	 * @param $anonymous_data
	 * @param $topic_author
	 */
	public function new_topic( $topic_id, $forum_id, $anonymous_data, $topic_author ) {
		$this->save_attachments( $topic_id );
	}

	/**
	 * Edit reply
	 * @param $reply_id
	 * @param $reply
	 */
	public function edit_reply( $reply_id, $reply ) {

		$this->save_attachments( $reply_id );
	}

	/**
	 * Attach media in reply on save
	 * @param $post_id
	 */
	public function save_attachments( $post_id ) {

		if (  isset( $_REQUEST['_wpnonce_bbm_bbpress_attachments_update'] )
			&& wp_verify_nonce( $_REQUEST['_wpnonce_bbm_bbpress_attachments_update'], 'bbm_bbpress_attachments_update' ) ) {

			if ( ! empty( $_REQUEST ) && ! empty( $_REQUEST['bbm_bbpress_attachments'] ) ) {
				$reply_attachments = $_REQUEST['bbm_bbpress_attachments'];

				//Save all attachment ids in reply meta
				update_post_meta( $post_id, 'bbm_bbpress_attachment_ids', $reply_attachments );
			} else {
				delete_post_meta( $post_id, 'bbm_bbpress_attachment_ids' );
			}

		}
	}



	/**
	 * Reply: Attached photo grid
	 *
	 * @param $content
	 * @param $id
	 *
	 * @return string
	 */
	public function embed_attachments( $content, $id ) {
		global $current_user;

		// Do not embed attachment in wp-admin area
		if ( is_admin() ) {
			return $content;
		}

		$attachments = get_post_meta( $id, 'bbm_bbpress_attachment_ids', true );

		if ( ! empty( $attachments ) ) {

			$img_size       = apply_filters( 'bbm_bbpress_embed_image_size', 'thumbnail' );//hardcoded !?
			$attachments[0] = isset( $attachments[0] ) ? $attachments[0] : '';
			$attachments[1] = isset( $attachments[1] ) ? $attachments[1] : '';

			$image1 = wp_get_attachment_image_src( $attachments[0], 'full' );
			$w1     = $image1[1];
			$h1     = $image1[2];

			$image2 = wp_get_attachment_image_src( $attachments[1], 'full' );
			$w2     = $image2[1];
			$h2     = $image2[2];

			$two_imgs_name = 'activity-2-thumbnail';

			// tall images
			if ( $w1 < $h1 && $w2 < $h2 ) {
				$two_imgs_name = 'activity-2-thumbnail-tall';
			}

			$filesizes = array();
			switch ( count( array_filter( $attachments ) ) ) {
				case 1:
					$filesizes = array( $img_size );
					$filenames = array( 'activity-thumbnail' );
					break;
				case 2:
					$filesizes = array( array( $w1 / 2, $h1 / 2 ), array( $w1 / 2, $h1 / 2 ) );
					$filenames = array( $two_imgs_name, $two_imgs_name );
					break;
				case 3:
					$filesizes = array( $img_size, array( $w1 / 2, $h1 / 2 ), array( $w1 / 2, $h1 / 2 ) );
					$filenames = array(
						'activity-thumbnail gallery-type',
						'activity-3-thumbnail',
						'activity-3-thumbnail'
					);
					break;
				default:
					$filesizes = array(
						$img_size,
						array( $w1 / 3, $h1 / 3 ),
						array( $w1 / 3, $h1 / 3 ),
						array( $w1 / 3, $h1 / 3 )
					);
					$filenames = array(
						'activity-thumbnail gallery-type',
						'activity-4-thumbnail',
						'activity-4-thumbnail',
						'activity-4-thumbnail'
					);
					break;
			}

			$img_counter   = 0;
			$all_imgs_html = '';
			$total_img_counter  = sizeof( $attachments );

			foreach ( $attachments as $attachment ) {

				$media_id = $attachment;

				$image = wp_get_attachment_image_src( $media_id, $filesizes[0] );

				if ( ! empty( $image ) && is_array( $image ) && count( $image ) > 2 ) {
					$src = $image[0];

					$full          = wp_get_attachment_image_src( $media_id, 'full' );

					$width_markup  = '';
					$height_markup = '';

					//hide more than 4 images
					$maybe_display_none = $img_counter > 3 ? ' style="display:none"' : '';

					/** Photo swipe meta data ************************************/
					$act_id         = (int) get_post_meta( $id, '_bbp_activity_id', true );
					$comment_count  = (int) get_post_meta( $id, '_bbp_reply_count', true );
					$reply          = '?bbp_reply_to=' . $id . '#new-post';
					$user_favs      = bp_get_user_meta( $current_user->ID, 'bp_favorite_activities', true );
					$user_favs      = $user_favs && is_array( $user_favs ) ? array_flip( $user_favs ) : '';
					$favorite_count = bp_activity_get_meta( $act_id, 'favorite_count' );

					// if the user has not previously favorited the item.
					if ( is_array( $user_favs ) && isset( $user_favs[ $act_id ] ) ) {
						$data_fav = 'bbm-unfav';
					} else {
						$data_fav = 'bbm-fav';
					}

					if ( $full !== false && is_array( $full ) && count( $full ) > 2 ) {

						$filesize = isset( $filenames[$img_counter] ) ? $filenames[$img_counter] :  $filenames[0];

						$all_imgs_html .= '<a class="buddyboss-media-photo-wrap size-' . $filesize . '" ' . $height_markup . '  href="' . $full[0] . '" ' . $maybe_display_none . '>';

						if ( 4 < $total_img_counter  && 3 === $img_counter  ) {
							$left_img_count = $total_img_counter - 4;
							$all_imgs_html .= '<span class="size-activity-4-count"><span class="size-activity-4-count-a"><span class="size-activity-4-count-b">+'. $left_img_count .'</span></span></span>';
						}

						$all_imgs_html .= '<img data-photo-id="' . $media_id . '" class="buddyboss-media-photo" src="' . $src . '"' . $width_markup . ' data-comment-count="' . $comment_count . '" data-permalink="'. $reply .'"
						 data-media="'. $act_id .'" data-favorite-count="'.$favorite_count.'" data-bbmfav="'.$data_fav.'" data-comment-count="'.$comment_count.'" href="" /></a>';
					} else {
						$filesize = isset( $filenames[$img_counter] ) ? $filenames[$img_counter] :  $filenames[0];

						$all_imgs_html .= '<img ' . $maybe_display_none . ' data-photo-id="' . $media_id . '"  data-comment-count="' . $comment_count . '"  class="buddyboss-media-photo size-' . $filesize . '" src="' . $src . '"' . $width_markup . '
						 data-permalink="'. $reply .'"  data-media="'. $act_id .'" data-favorite-count="'.$favorite_count.'" data-bbmfav="'.$data_fav.'" data-comment-count="'.$comment_count.'" href="" />';
					}
				}
				$img_counter ++;
			}

			$content .= "<div class='buddyboss-media-photos-wrap-container'>" . $all_imgs_html . "</div>";
		}

		return $content;
	}

	/**
	 *  Inject photo upload markup in reply form
	 */
	public function embed_form() {
		global $post;

		wp_nonce_field( 'post_update', '_wpnonce_post_update' );
		wp_nonce_field( 'bbm_bbpress_attachments_update', '_wpnonce_bbm_bbpress_attachments_update' ); ?>

		<!-- Media preview panel -->
		<!-- <div id="whats-new-content"></div> -->
			<div class="buddyboss-bbpress-media-preview">
			<div class="clearfix buddyboss-bbpress-media-preview-inner">
			<?php if ( bbp_is_reply_edit() || bbp_is_topic_edit() ):

				$attachment_ids = get_post_meta( $post->ID, 'bbm_bbpress_attachment_ids', true );

				if ( ! empty( $attachment_ids ) ):
				 foreach ( $attachment_ids as $attachment_id ):
						$attachment_thumb_url = wp_get_attachment_thumb_url( $attachment_id ); ?>

						<div data-fileid='<?php echo $attachment_id ?>' class='file uploading'><img src='<?php echo $attachment_thumb_url; ?>'>
							<a href='#' onclick='return window.BuddyBoss_Comment_Media_Uploader.removeUploaded(<?php echo $attachment_id ?>);' class='delete'>+</a>
							<input type='hidden' value='<?php echo $attachment_id; ?>' name='bbm_bbpress_attachments[]'>
						</div>

					<?php endforeach;
				endif;
			endif; ?>

			</div>
			</div>
		<!-- Media preview panel -->
		<?php
	}

	/**
	 * Append img in bbpress reply activity content
	 * @param $r
	 * @return mixed
	 */
	public function append_media_content( $r ) {

		$type = array( 'bbp_topic_edit', 'bbp_topic_create', 'bbp_reply_edit', 'bbp_reply_create' );
		if ( in_array( $r['type'], $type ) ) {

			$media_html = '';

			if( 'bbpress' === $r['component'] ) {
				$reply_id = $r['item_id'];
			} else {
				$reply_id = $r['secondary_item_id'];
			}

			$_POST['pics_uploaded'] = array();
			//Get attachment ids from meta
			$attachment_ids	 		= get_post_meta( $reply_id, 'bbm_bbpress_attachment_ids', true );

			if ( ! empty( $attachment_ids ) ) {
				//Append img in reply activity content
				$r['content'] .=  bbm_generate_media_activity_content( $attachment_ids );
			}
		}
		return $r;
	}
}

$bbpress_media_support = buddyboss_media()->option('bbpress_media_support');
//Check for bbPress media support is enabled
if( ! empty( $bbpress_media_support ) && is_plugin_active('bbpress/bbpress.php') ) {
	BBM_BBPress_Media::instance();
}

?>
