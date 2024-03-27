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
            'groups' => __( 'Groups', 'buddyboss' ),
            'group' => __( 'Group', 'buddyboss' ),
            'social_groups' => __( 'Social Groups', 'buddyboss' ),
            'social_group' => __( 'Social Group', 'buddyboss' ),
            'forums' => __( 'Forums', 'buddyboss' ),
            'forum' => __( 'Forum', 'buddyboss' ),
            'discussions' => __( 'Discussions', 'buddyboss' ),
            'discussion' => __( 'Discussion', 'buddyboss' ),
            'reactions' => __( 'Reactions', 'buddyboss' ),
            'friends' => __( 'Connections', 'buddyboss' ),
            'friend' => __( 'Connection', 'buddyboss' ),
            'members' => __( 'Members', 'buddyboss' ),
            'member' => __( 'Member', 'buddyboss' ),
            'blog' => __( 'Blog', 'buddyboss' ),
            'photo' => __( 'Photo', 'buddyboss' ),
            'photos' => __( 'Photo', 'buddyboss' ),
            'document' => __( 'Document', 'buddyboss' ),
            'documents' => __( 'Documents', 'buddyboss' ),
            'video' => __( 'Video', 'buddyboss' ),
            'videos' => __( 'Video', 'buddyboss' ),
            'notifications' => __( 'Notifications', 'buddyboss' ),
            'notification' => __( 'Notification', 'buddyboss' ),
            'messages' => __( 'Messages', 'buddyboss' ),
            'message' => __( 'Message', 'buddyboss' ),
            'suspend' => __( 'Suspend', 'buddyboss' ),
            'block' => __( 'Block', 'buddyboss' ),
            'report' => __( 'Report', 'buddyboss' ),
            'moderation' => __( 'Moderation', 'buddyboss' ),
            'register' => __( 'Register', 'buddyboss' ),
            'registration' => __( 'Registration', 'buddyboss' ),
            'login' => __( 'Login', 'buddyboss' ),
            'profile' => __( 'Profile', 'buddyboss' ),
            'gifs' => __( 'GIFs', 'buddyboss' ),
            'gif' => __( 'GIF', 'buddyboss' ),
            'media' => __( 'Media', 'buddyboss' ),
            'following' => __( 'Following', 'buddyboss' ),
            'follower' => __( 'Follower', 'buddyboss' ),
            'activity_comment' => __( 'Activity Comment', 'buddyboss' ),
            'activity_comments' => __( 'Activity Comments', 'buddyboss' ),
            'subscribe' => __( 'Subscribe', 'buddyboss' ),
            'email_invites' => __( 'Email Invites', 'buddyboss' ),
            'email_invite' => __( 'Email Invite', 'buddyboss' ),
            'invites' => __( 'Invites', 'buddyboss' ),
            'labs' => __( 'Labs', 'buddyboss' ),
            'replies' => __( 'Replies', 'buddyboss' ),
            'reply' => __( 'Reply', 'buddyboss' ),
            'activity' => __( 'Activity1', 'buddyboss' ),
            'activity_post' => __( 'Activity Post', 'buddyboss' ),
            'activity_posts' => __( 'Activity Posts', 'buddyboss' ),
            'performance' => __( 'Performance', 'buddyboss' ),
            'general' => __( 'General', 'buddyboss' ),
            'news_feed' => __( 'News Feed', 'buddyboss' ),
            'home' => __( 'Home', 'buddyboss' ),
        );
        

        if ( isset( $labels[ $key ] ) ){
            $label = $labels[ $key ];
        } elseif ( $key === null ) {
            $label = $labels;
        } else {
            $label = __( $key, 'buddyboss' ); // Return the $key itself by adding text-domain domain.
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