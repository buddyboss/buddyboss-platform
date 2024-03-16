<?php
/**
 * Holds Global Dynamic Labels functionality.
 *
 * @package BuddyBoss/Core
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * BB_Component_Label class.
 */
class BB_Component_Label {

	/**
	 * Class instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Using Singleton, see instance().
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		// Using Singleton, see instance().
	}

	/**
	 * Get the instance of the class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return object
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

    /**
     * Get label based on key name.
     *
     * Retrieves the label entered on the settings page based on the provided key name.
     *
     * @since [BBVERSION]
     *
     * @param  string  $key Key name of the setting field. Should be lowercase and without any special characters or spaces.
     * @return string  The label entered on the settings page.
     */
    public static function get_label( $key = null ) {

        $labels = array(
            'groups' => esc_html__( 'Groups', 'buddyboss' ),
            'group' => esc_html__( 'Group', 'buddyboss' ),
            'social_groups' => esc_html__( 'Social Groups', 'buddyboss' ),
            'social_group' => esc_html__( 'Social Group', 'buddyboss' ),
            'forums' => esc_html__( 'Forums', 'buddyboss' ),
            'forum' => esc_html__( 'Forum', 'buddyboss' ),
            'discussions' => esc_html__( 'Discussions', 'buddyboss' ),
            'discussion' => esc_html__( 'Discussion', 'buddyboss' ),
            'reactions' => esc_html__( 'Reactions', 'buddyboss' ),
            'friends' => esc_html__( 'Connections', 'buddyboss' ),
            'friend' => esc_html__( 'Connection', 'buddyboss' ),
            'members' => esc_html__( 'Members', 'buddyboss' ),
            'member' => esc_html__( 'Member', 'buddyboss' ),
            'blog' => esc_html__( 'Blog', 'buddyboss' ),
            'photo' => esc_html__( 'Photo', 'buddyboss' ),
            'photos' => esc_html__( 'Photo', 'buddyboss' ),
            'document' => esc_html__( 'Document', 'buddyboss' ),
            'documents' => esc_html__( 'Documents', 'buddyboss' ),
            'video' => esc_html__( 'Video', 'buddyboss' ),
            'videos' => esc_html__( 'Video', 'buddyboss' ),
            'notifications' => esc_html__( 'Notifications', 'buddyboss' ),
            'notification' => esc_html__( 'Notification', 'buddyboss' ),
            'messages' => esc_html__( 'Messages', 'buddyboss' ),
            'message' => esc_html__( 'Message', 'buddyboss' ),
            'suspend' => esc_html__( 'Suspend', 'buddyboss' ),
            'block' => esc_html__( 'Block', 'buddyboss' ),
            'report' => esc_html__( 'Report', 'buddyboss' ),
            'moderation' => esc_html__( 'Moderation', 'buddyboss' ),
            'register' => esc_html__( 'Register', 'buddyboss' ),
            'registration' => esc_html__( 'Registration', 'buddyboss' ),
            'login' => esc_html__( 'Login', 'buddyboss' ),
            'profile' => esc_html__( 'Profile', 'buddyboss' ),
            'gifs' => esc_html__( 'GIFs', 'buddyboss' ),
            'gif' => esc_html__( 'GIF', 'buddyboss' ),
            'media' => esc_html__( 'Media', 'buddyboss' ),
            'following' => esc_html__( 'Following', 'buddyboss' ),
            'follower' => esc_html__( 'Follower', 'buddyboss' ),
            'activity_comment' => esc_html__( 'Activity Comment', 'buddyboss' ),
            'activity_comments' => esc_html__( 'Activity Comments', 'buddyboss' ),
            'subscribe' => esc_html__( 'Subscribe', 'buddyboss' ),
            'email_invites' => esc_html__( 'Email Invites', 'buddyboss' ),
            'email_invite' => esc_html__( 'Email Invite', 'buddyboss' ),
            'invites' => esc_html__( 'Invites', 'buddyboss' ),
            'labs' => esc_html__( 'Labs', 'buddyboss' ),
            'replies' => esc_html__( 'Replies', 'buddyboss' ),
            'reply' => esc_html__( 'Reply', 'buddyboss' ),
            'activity' => esc_html__( 'Activity', 'buddyboss' ),
            'activity_post' => esc_html__( 'Activity Post', 'buddyboss' ),
            'activity_posts' => esc_html__( 'Activity Posts', 'buddyboss' ),
            'performance' => esc_html__( 'Performance', 'buddyboss' ),
            'general' => esc_html__( 'General', 'buddyboss' ),
            'news_feed' => esc_html__( 'News Feed', 'buddyboss' ),
            'home' => esc_html__( 'Home', 'buddyboss' ),
        );
        

        if ( isset( $labels[ $key ] ) ){
            $label = $labels[ $key ];
        } elseif ( $key === null ) {
            $label = $labels;
        } else {
            $label = esc_html__( $key, 'buddyboss' ); // Return the $key itself by adding text-domain domain.
        }

        /**
         * Filters the value of label settings entered in the settings page. Used to filter label value in get_label function.
         *
         * @param string $label Label entered on settings page.
         * @param string $key   Key name of setting field.
         */
        return apply_filters( 'bb_get_label', $label, $key );
    }

    /**
     * Generate a slug-ready string.
     *
     * Retrieves a lowercase string suitable for use as a slug.
     *
     * @since [BBVERSION]
     *
     * @param string $key The key name of the setting field. Should be lowercase and without any special characters or spaces.
     * @return string A lowercase string suitable for use as a slug.
     */
    public static function label_to_lower( $key ) {
        $label = strtolower( self::get_label( $key ) );
        /**
         * Filters value of label after converting it to the lowercase. Used to filter label values in label_to_lower function.
         *
         * @param string $label Label entered on settings page.
         * @param string $key   Key name of setting field.
         */
        return apply_filters( 'bb_label_to_lower', $label, $key );
    }
}