<?php
/**
 * Holds Global Dynamic Labels functionality.
 *
 * @package BuddyBoss/Core
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Component_Label' ) ) :

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
	 * Constructor function.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		/* Do nothing here */
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
     * @param  string $key Key name of the setting field. Should be lowercase and without any special characters or spaces.
     *
     * @return string The label entered on the settings page.
     */
    public static function get_label( $key = null ) {

		$labels = array(
			'activity'          => __( 'Activity', 'buddyboss' ),
			'activities'        => __( 'Activities', 'buddyboss' ),
			'activity_post'     => __( 'Activity Post', 'buddyboss' ),
			'activity_posts'    => __( 'Activity Posts', 'buddyboss' ),
			'activity_comment'  => __( 'Activity Comment', 'buddyboss' ),
		    'activity_comments' => __( 'Activity Comments', 'buddyboss' ),
			'block'             => __( 'Block', 'buddyboss' ),
			'blocks'            => __( 'Blocks', 'buddyboss' ),
			'blog'              => __( 'Blog', 'buddyboss' ),
			'blogs'             => __( 'Blogs', 'buddyboss' ),
			'connection'        => __( 'Connection', 'buddyboss' ),
			'connections'       => __( 'Connections', 'buddyboss' ),
			'discussion'        => __( 'Discussion', 'buddyboss' ),
			'discussions'       => __( 'Discussions', 'buddyboss' ),
			'document'          => __( 'Document', 'buddyboss' ),
		    'documents'         => __( 'Documents', 'buddyboss' ),
			'email_invites'     => __( 'Email Invites', 'buddyboss' ),
			'follower'          => __( 'Follower', 'buddyboss' ),
			'followers'         => __( 'Followers', 'buddyboss' ),
			'following'         => __( 'Following', 'buddyboss' ),
			'followings'        => __( 'Followings', 'buddyboss' ),
			'forum'             => __( 'Forum', 'buddyboss' ),
			'forums'            => __( 'Forums', 'buddyboss' ),
			'group'             => __( 'Group', 'buddyboss' ),
			'groups'            => __( 'Groups', 'buddyboss' ),
			'gifs'              => __( 'GIFs', 'buddyboss' ),
			'home'              => __( 'Home', 'buddyboss' ),
			'login'             => __( 'Login', 'buddyboss' ),
			'media'             => __( 'Media', 'buddyboss' ),
			'member'            => __( 'Member', 'buddyboss' ),
			'members'           => __( 'Members', 'buddyboss' ),
			'message'           => __( 'Message', 'buddyboss' ),
			'messages'          => __( 'Messages', 'buddyboss' ),
			'news_feed'         => __( 'News Feed', 'buddyboss' ),
			'notification'      => __( 'Notification', 'buddyboss' ),
			'notifications'     => __( 'Notifications', 'buddyboss' ),
			'photo'             => __( 'Photo', 'buddyboss' ),
			'photos'            => __( 'Photos', 'buddyboss' ),
			'profile'           => __( 'Profile', 'buddyboss' ),
			'profiles'          => __( 'Profiles', 'buddyboss' ),
			'register'          => __( 'Register', 'buddyboss' ),
			'registration'      => __( 'Registration', 'buddyboss' ),
			'report'            => __( 'Report', 'buddyboss' ),
			'reports'           => __( 'Reports', 'buddyboss' ),
			'reply'             => __( 'Reply', 'buddyboss' ),
			'replies'           => __( 'Replies', 'buddyboss' ),
			'social_group'      => __( 'Social Group', 'buddyboss' ),
			'social_groups'     => __( 'Social Groups', 'buddyboss' ),
			'suspend'           => __( 'Suspend', 'buddyboss' ),
			'subscribe'         => __( 'Subscribe', 'buddyboss' ),
			'video'             => __( 'Video', 'buddyboss' ),
			'videos'            => __( 'Videos', 'buddyboss' )
		);

	    if ( isset( $labels[ $key ] ) ) {
		    $label = $labels[ $key ];
	    } elseif ( ! empty( $key ) ) {

			// Return the $key after translation if not empty as fallback.
		    $label = __( $key, 'buddyboss' );
	    } else {

		    // Return the $key itself as fallback.
		    $label = $key;
	    }

		/**
		 * Filters the value of label.
		 *
		 * @since BuddyBoss [BBVERSION]
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
	 * @param string $key The key name of the label. Should be lowercase and without any special characters or spaces.
	 *
	 * @return string $key A lowercase string suitable for use as a slug.
	 */
	public static function label_to_lower( $key ) {
		$label = strtolower( self::get_label( $key ) );

		/**
		 * Filters value of label after converting it to the lowercase.
		 *
		 * @param string $label Label entered on settings page.
		 * @param string $key   Key name of setting field.
		 */
		return apply_filters( 'bb_label_to_lower', $label, $key );
	}
}
endif; // End class_exists check.
