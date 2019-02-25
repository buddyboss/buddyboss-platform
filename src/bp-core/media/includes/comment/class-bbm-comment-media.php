<?php
/**
 * Handle activity reply media
 *
 * @class       BBM_Comment_Media
 * @category    Class
 * @author      BuddyBoss
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BBM_Comment_Media Class
 */
class BBM_Comment_Media {

    /** @var BBM_BBPress_Media The single instance of the class */
    protected static $_instance = null;


    function __construct() {
        //add_action( 'init', array( $this, 'hooks' ) );
        $this->hooks();
    }

    /**
     * Main BBM_Comment_Media Instance
     *
     * Ensures only one instance of BBM_Attachments is loaded or can be loaded.
     * @static
     * @return BBM_Comment_Media Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function hooks() {
        // Front End Assets
        if ( ! is_admin() && ! is_network_admin() )
        {

            // Script templates
            add_action( 'wp_footer', array( $this, 'script_templates' ) );
        }

        add_action( 'init', array( $this, 'bp_ready_hooks' ), 100 );
    }

    public function bp_ready_hooks() {
        add_filter( 'bp_activity_get_where_conditions', array( $this, 'include_activity_comments_type' ), 10, 5 );
        add_filter( 'bp_activity_comment_content', array( buddyboss_media()->types->photo->hooks, 'bp_get_activity_comment_content' ), 1, 1 );
    }

    /**
     * Print inline templates
     * @return void
     */
    public function script_templates() {

        //Check show lightbox option is checked
        $show_uploadbox = buddyboss_media()->option('show_uploadbox');

        ?>
        <script type="text/html" id="buddyboss-comment-media-tpl-add-photo">
            <div class="buddyboss-comment-media-add-photo">

                <!-- Fake add photo button will be clicked from js -->
                <button type="button" class="open-uploader-button buddyboss-comment-media-add-photo-button" style="<?php echo ( ! empty( $show_uploadbox ) ) ? 'display:none;' : ''; ?>"></button>
                <button type="button" id="browse-file-button" class="browse-file-button buddyboss-comment-media-add-photo-button" style="<?php echo ( 'yes' === $show_uploadbox  ) ? '' :  'display:none;'; ?>"></button>

                <div class="buddyboss-media-progress">
                    <div class="buddyboss-media-progress-value">0%</div>
                    <progress class="buddyboss-media-progress-bar" value="0" max="100"></progress>
                </div>
                <div class="buddyboss-comment-media-photo-uploader"></div>
            </div><!-- #buddyboss-media-add-photo -->
        </script>

        <script type="text/html" id="buddyboss-comment-media-tpl-preview">
            <div class="buddyboss-comment-media-preview">
                <div class="clearfix buddyboss-comment-media-preview-inner">

                </div>

                <?php $component_slug = buddyboss_media_component_slug(); ?>

                <?php if( bp_is_my_profile() && bp_is_current_component( $component_slug ) ):?>
                    <div id="buddyboss-comment-media-bulk-uploader-reception-fake" class="image-drop-box">
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

        <div id="buddyboss-comment-media-bulk-uploader-wrapper" style="display:none">
            <div id="buddyboss-comment-media-bulk-uploader">
                <div class="buddyboss-comment-media-bulk-uploader-uploaded">
                    <textarea class="buddyboss-comment-media-bulk-uploader-text" placeholder="<?php esc_attr_e( 'Say something about the photo(s)...', 'buddyboss-media' ) ;?>"></textarea>
                    <div class="images clearfix">

                    </div>
                </div>
                <div id="buddyboss-comment-media-bulk-uploader-reception" class="image-drop-box">
                    <h3 class="buddyboss-media-drop-instructions"><?php _e( 'Drop files anywhere to upload', 'buddyboss-media' );?></h3>
                    <p class="buddyboss-media-drop-separator"><?php _e( 'or', 'buddyboss-media' );?></p>
                    <a class="logo-comment-file-browser-button browse-file-button" title="Select image" > <?php _e( 'Select Files', 'buddyboss-media' );?></a>
                </div>

                <input type="submit" id="buddyboss-comment-media-attach" value="<?php esc_attr_e( 'Post', 'buddyboss-media' );?>" />

            </div>
        </div>
    <?php endif; ?>

        <?php
    }

    /**
     * Remove the 'activity_comment'  type from NOT IN MySQL WHERE conditions for the Activity items get method.
     * @param $where_conditions
     * @param $r
     * @param $select_sql
     * @param $from_sql
     * @param $join_sql
     *
     * @return mixed
     */
    public function include_activity_comments_type( $where_conditions, $r, $select_sql, $from_sql, $join_sql ) {

        if ( ( bp_is_current_component( buddyboss_media()->option('component-slug') ) )
             || is_page( buddyboss_media()->option('all-media-page') ) ) {
            $where_conditions['excluded_types'] = str_replace( 'activity_comment', '', $where_conditions['excluded_types'] );
        }

        return $where_conditions;
    }

}

BBM_Comment_Media::instance();