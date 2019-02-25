<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

if ( !class_exists( 'BuddyBoss_Media_Albums' ) ):

	class BuddyBoss_Media_Albums {

		/**
		 * The loop iterator.
		 *
		 * @access public
		 * @var int
		 */
		var $current_album = -1;

		/**
		 * The number of groups returned by the paged query.
		 *
		 * @access public
		 * @var int
		 */
		var $album_count;

		/**
		 * Array of albums located by the query.
		 *
		 * @access public
		 * @var array
		 */
		var $albums;

		/**
		 * The album object currently being iterated on.
		 *
		 * @access public
		 * @var object
		 */
		var $album;

		/**
		 * A flag for whether the loop is currently being iterated.
		 *
		 * @access public
		 * @var bool
		 */
		var $in_the_loop;

		/**
		 * The page number being requested.
		 *
		 * @access public
		 * @var public
		 */
		var $pag_page;

		/**
		 * The number of items being requested per page.
		 *
		 * @access public
		 * @var public
		 */
		var $pag_num;

		/**
		 * An HTML string containing pagination links.
		 *
		 * @access public
		 * @var string
		 */
		var $pag_links;

		/**
		 * The total number of albums matching the query parameters.
		 *
		 * @access public
		 * @var int
		 */
		var $total_album_count;

		/**
		 * Array to hold avatar image for each album in paginated result set.
		 *
		 * @var array
		 */
		var $album_photos;

		/**
		 * Constructor method.
		 */
		function __construct( $args = array() ) {
			$defaults = array(
				'page'			 => 1,
				'per_page'		 => 20,
				'page_arg'		 => 'grpage',
				'user_id'		 => 0,
				'group_id'       => '',
				'search_terms'	 => '',
				'include'		 => false,
				'privacy'		 => 'public',
				'orderby'		 => 'a.date_created',
				'order'			 => 'DESC',
			);

			$args = wp_parse_args( $args, $defaults );

			$this->pag_page	 = isset( $_REQUEST[ $args[ 'page_arg' ] ] ) ? intval( $_REQUEST[ $args[ 'page_arg' ] ] ) : $args[ 'page' ];
			$this->pag_num	 = isset( $_REQUEST[ 'num' ] ) ? intval( $_REQUEST[ 'num' ] ) : $args[ 'per_page' ];

			$this->albums = $this->fetch_albums( $args );

			$this->total_album_count = (int) $this->albums[ 'total' ];
			$this->albums			 = $this->albums[ 'albums' ];
			$this->album_count		 = count( $this->albums );

			//seprate query to fetch first image per album
			$this->fetch_album_photos();

			// Build pagination links
			if ( (int) $this->total_album_count && (int) $this->pag_num ) {
				$pag_args = array(
					$args[ 'page_arg' ]	 => '%#%',
					'num'				 => $this->pag_num,
				);

				if ( defined( 'DOING_AJAX' ) && true === (bool) DOING_AJAX ) {
					$base = esc_url( remove_query_arg( 's', wp_get_referer() ) );
				} else {
					$base = '';
				}

				if ( !empty( $search_terms ) ) {
					$pag_args[ 's' ] = $search_terms;
				}

				$paginate_links_args = array(
					'base'		 => esc_url( add_query_arg( $pag_args, $base ) ),
					'format'	 => '',
					'total'		 => ceil( (int) $this->total_album_count / (int) $this->pag_num ),
					'current'	 => $this->pag_page,
					'prev_text'	 => _x( '&larr;', 'Album pagination previous text', 'buddyboss-media' ),
					'next_text'	 => _x( '&rarr;', 'Album pagination next text', 'buddyboss-media' ),
					'mid_size'	 => 1
				);
				$this->pag_links	 = paginate_links( $paginate_links_args );
			}
		}

		protected function fetch_albums( $args ) {
			global $wpdb, $current_user;
			$query_placeholders = array();

			$columns_all	 = 'a.*';
			$columns_count	 = 'COUNT(*)';

			$TABLES = "{$wpdb->prefix}buddyboss_media_albums a";

			$WHERE = array();
			if ( $args[ 'user_id' ] ) {
				$WHERE[]				 = 'a.user_id=%d';
				$query_placeholders[]	 = $args[ 'user_id' ];
			}

			if ( $args[ 'group_id' ] ) {
				$WHERE[]				 = 'a.group_id=%d';
				$query_placeholders[]	 = $args[ 'group_id' ];
			} else if ( bp_is_active( 'groups' ) ) { //We must ensure that buddypress group component is active

					//Exclude private/hidden groups albums for non logged in users
					if ( ! is_user_logged_in() ) {

						$WHERE[] = "( a.group_id NOT IN ( SELECT id FROM {$wpdb->base_prefix}bp_groups WHERE status != 'public' )
					OR a.group_id IS NULL )";
					} else {

						//Exclude private/hidden groups albums if current user is not a member of those groups
						$WHERE[] = "( a.group_id NOT IN ( SELECT id FROM {$wpdb->base_prefix}bp_groups WHERE status != 'public' AND id NOT IN
					( SELECT group_id FROM  {$wpdb->base_prefix}bp_groups_members WHERE user_id = $current_user->ID ) )
					OR a.group_id IS NULL)";
					}
			}

			if ( $args[ 'privacy' ] !== "'all'" ) {
                $privacy_conditions = array( 'a.privacy IN ( ' . $args[ 'privacy' ] . ')' );
                //also include private albums if it belongs to logged in user
                if( is_user_logged_in() ){
                    $privacy_conditions[] = "a.user_id=" . bp_loggedin_user_id();
                }

				$WHERE[] = '( ' . implode( ' OR ', $privacy_conditions ) . ' )';
			}

			if ( $args[ 'search_terms' ] ) {
				$WHERE[]				 = 'a.title LIKE %%%s%%';
				$query_placeholders[]	 = $args[ 'search_terms' ];
			}

			if ( $args[ 'include' ] ) {
				//@todo make this better
				$WHERE[] = 'a.id IN (' . implode( ',', $args[ 'include' ] ) . ')';
			}

			$WHERE = implode( ' AND ', $WHERE );

			$lower_limit = ( $this->pag_page - 1 ) * $this->pag_num;
			$LIMIT		 = "LIMIT {$lower_limit}, {$this->pag_num}";

			//echo '<pre>' . $wpdb->prepare( "SELECT {$columns_all} FROM {$TABLES} WHERE {$WHERE} ORDER BY {$args['orderby']} {$args['order']} {$LIMIT}", $query_placeholders ) . '</pre>';
			$sql_results = "SELECT {$columns_all} FROM {$TABLES} WHERE {$WHERE} ORDER BY {$args[ 'orderby' ]} {$args[ 'order' ]} {$LIMIT}";
			$sql_count	 = "SELECT {$columns_count} FROM {$TABLES} WHERE {$WHERE}";

			//Don't prepare statements that lack placeholders
			if ( ! empty( $query_placeholders ) ) {
				$sql_results   = $wpdb->prepare( $sql_results, $query_placeholders );
				$sql_count     = $wpdb->prepare( $sql_count, $query_placeholders );
			}

			$total_albums	 = $wpdb->get_var( $sql_count );
			$paged_albums	 = $wpdb->get_results( $sql_results );

			return array( 'albums' => $paged_albums, 'total' => $total_albums );
		}

		/**
		 * Whether there are albums available in the loop.
		 *
		 * @see buddyboss_media_has_albums()
		 *
		 * @return bool True if there are items in the loop, otherwise false.
		 */
		function has_albums() {
			if ( $this->album_count )
				return true;

			return false;
		}

		/**
		 * Set up the next album and iterate index.
		 *
		 * @return object The next album to iterate over.
		 */
		function next_album() {
			$this->current_album++;
			$this->album = $this->albums[ $this->current_album ];

			return $this->album;
		}

		/**
		 * Rewind the albums and reset albums index.
		 */
		function rewind_albums() {
			$this->current_album = -1;
			if ( $this->album_count > 0 ) {
				$this->album = $this->albums[ 0 ];
			}
		}

		/**
		 * Whether there are albums left in the loop to iterate over.
		 *
		 * @return bool True if there are more albums to show, otherwise false.
		 */
		function albums() {
			if ( $this->current_album + 1 < $this->album_count ) {
				return true;
			} elseif ( $this->current_album + 1 == $this->album_count ) {
				$this->rewind_albums();
			}

			$this->in_the_loop = false;
			return false;
		}

		/**
		 * Set up the current album inside the loop.
		 */
		function the_album() {
			$this->in_the_loop	 = true;
			$this->album		 = $this->next_album();
		}

		protected function fetch_album_photos() {
			//get attachment id of first activity related the album, for each album in paginated_query
			//get image for those attachment and save as album_id=>image url
			if ( $this->album_count > 0 ) {
				$album_ids = array();
				foreach ( $this->albums as $album ) {
					if ( $album->total_items > 0 )
						$album_ids[] = $album->id;
				}

				if ( !empty( $album_ids ) ) {
					global $wpdb;
					//$meta_keys		 = buddyboss_media_compat( 'activity.item_keys' );
					//$meta_keys_csv	 = "'" . implode( "','", $meta_keys ) . "'";
					$sql			 = "SELECT MAX(m.media_id) AS mediaId, m.album_id  FROM {$wpdb->prefix}buddyboss_media m INNER JOIN {$wpdb->posts} p ON p.ID = m.media_id WHERE p.post_type = 'attachment'
										AND m.album_id IN( " . implode( ',', $album_ids ) . ")
					                    GROUP BY m.album_id ";
					$results		 = $wpdb->get_results( $sql );

					if ( ! empty( $results ) ) {
						foreach ( $results as $result ) {
							$this->album_photos[ $result->album_id ] = $result->mediaId;
						}
					}
				}
			}
		}

		public function album_avatar( $album = '' ) {
			$image_url = '';

			if ( !$album )
				$album = $this->album;

			$attachment_id = isset( $this->album_photos[ $album->id ] ) && !empty( $this->album_photos[ $album->id ] ) ? $this->album_photos[ $album->id ] : '';
			if ( $attachment_id ) {
				$image_url = wp_get_attachment_image_src( $attachment_id );
				if ( $image_url ) {
					$image_url = $image_url[ 0 ];
				}
			}

			return $image_url;
		}

	}

	endif;

function buddyboss_media_has_albums( $args = '' ) {
	global $buddyboss_media_albums_template;

	$privacy = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'members';
	}

	if ( bp_is_user() ) {
		if ( bp_is_my_profile() ) {
			$privacy = array( 'all' );
		} else if ( bp_is_active( 'friends' ) && friends_check_friendship( bp_displayed_user_id(), bp_loggedin_user_id() ) ) {
			$privacy[] = 'friends';
		}
	}

    if( bp_is_group() && is_user_logged_in() ){
        $is_member = groups_is_user_member( bp_loggedin_user_id(), bp_get_group_id() );
        if( $is_member ){
            $privacy[] = 'grouponly';
        }
    }

	$default_args = array(
		'privacy'	 => "'" . implode( "','", $privacy ) . "'"
	);

	if ( bp_is_group() ) {
		$default_args['group_id'] = bp_get_current_group_id();
	} else {
		$default_args['user_id'] = bp_displayed_user_id();
	}
	// Parse defaults and requested arguments
	$args = wp_parse_args( $args, $default_args );



	//this should be important
	$args = apply_filters( 'buddyboss_media_albums_loop_args', $args );

	// Setup the albums template global
	$buddyboss_media_albums_template = new BuddyBoss_Media_Albums( $args );

	// Filter and return whether or not the albums loop has albums in it
	return apply_filters( 'buddyboss_media_has_albums', $buddyboss_media_albums_template->has_albums(), $buddyboss_media_albums_template, $args );
}

/**
 * Check whether there are more albums to iterate over.
 *
 * @return bool
 */
function buddyboss_media_albums() {
	global $buddyboss_media_albums_template;
	return $buddyboss_media_albums_template->albums();
}

/**
 * Set up the current album inside the loop.
 *
 * @return object
 */
function buddyboss_media_the_album() {
	global $buddyboss_media_albums_template;
	return $buddyboss_media_albums_template->the_album();
}

function buddyboss_media_albums_pagination_links() {
	echo buddyboss_media_albums_get_pagination_links();
}

function buddyboss_media_albums_get_pagination_links() {
	global $buddyboss_media_albums_template;

	return $buddyboss_media_albums_template->pag_links;
}

function buddyboss_media_album_id() {
	echo buddyboss_media_album_get_id();
}

function buddyboss_media_album_get_id() {
	global $buddyboss_media_albums_template;
	return $buddyboss_media_albums_template->album->id;
}

function buddyboss_media_album_permalink() {
	echo buddyboss_media_album_get_permalink();
}

function buddyboss_media_album_get_permalink() {
	global $buddyboss_media_albums_template;

	if ( bp_is_active('groups') && $buddyboss_media_albums_template->album->group_id ) {

		$group = groups_get_group( array( 'group_id' => $buddyboss_media_albums_template->album->group_id ) );
		$group_link = bp_get_group_permalink( $group );
		$permalink = trailingslashit( $group_link . buddyboss_media_component_slug() .'/albums/' . $buddyboss_media_albums_template->album->id . '/' );

	} else {
		$user_id	 = $buddyboss_media_albums_template->album->user_id;
		$permalink	 = bp_core_get_user_domain( $user_id ) . buddyboss_media_component_slug() . '/albums/' . $buddyboss_media_albums_template->album->id . '/';
	}
	return apply_filters( 'buddyboss_media_album_permalink', $permalink );
}

function buddyboss_media_album_avatar( $args = '' ) {
	echo buddyboss_media_album_get_avatar( $args );
}

function buddyboss_media_album_get_avatar( $args = '' ) {
	$defaults = array(
		'height' => 50,
		'width'	 => 50,
		'class'	 => 'buddyboss_media_album_avatar avatar',
	);

	$args = wp_parse_args( $args, $defaults );

	global $buddyboss_media_albums_template;
	$url = $buddyboss_media_albums_template->album_avatar();

	if ( !$url ) {
		$url = apply_filters( 'buddyboss_media_album_default_image', BUDDYBOSS_MEDIA_PLUGIN_URL . 'assets/img/placeholder-150x150.png' );
		$args[ 'class' ] .= ' fallback_image';
	}

	return "<img src='" . esc_url( $url ) . "' alt='" . esc_attr( $buddyboss_media_albums_template->album->title ) . "' class='" . esc_attr( $args[ 'class' ] ) . "' height='" . esc_attr( $args[ 'height' ] ) . "' width='" . esc_attr( $args[ 'width' ] ) . "'>";
}

function buddyboss_media_album_title() {
	echo buddyboss_media_album_get_title();
}

function buddyboss_media_album_get_title() {
	global $buddyboss_media_albums_template;
	return stripcslashes( $buddyboss_media_albums_template->album->title );
}

function buddyboss_media_album_description() {
	echo buddyboss_media_album_get_description();
}

function buddyboss_media_album_get_description() {
	global $buddyboss_media_albums_template;
	return stripcslashes( $buddyboss_media_albums_template->album->description );
}

function buddyboss_media_album_short_description( $characters_count = 100 ) {
	echo buddyboss_media_album_get_short_description( $characters_count );
}

function buddyboss_media_album_get_short_description( $characters_count = 100 ) {
	$shortened_description	 = '';
	$description			 = buddyboss_media_album_get_description();
	if ( $description ) {
		$description			 = wp_strip_all_tags( $description, true );
		$shortened_description	 = substr( $description, 0, $characters_count );

		if ( strlen( $description ) > $characters_count ) {
			$shortened_description .= '..';
		}
	}

	return $shortened_description;
}

function buddyboss_media_album_date() {
	echo buddyboss_media_album_get_date();
}

function buddyboss_media_album_get_date() {
	global $buddyboss_media_albums_template;
	return sprintf( __( 'created %s ago', 'buddyboss-media' ), human_time_diff( strtotime( $buddyboss_media_albums_template->album->date_created ) ) );
}

function buddyboss_media_album_photos_count() {
	echo buddyboss_media_album_get_photos_count();
}

function buddyboss_media_album_get_photos_count() {
	global $buddyboss_media_albums_template, $wpdb;
    $photos_count = $buddyboss_media_albums_template->album->total_items;
    if( !$photos_count ){
        /**
         * Due to a bug in previous versions, total_items of album wouldn't update correctly.
         * So, old albums would still show count as 0.
         * Lets fix that
         */
        $sql			 = "SELECT COUNT(m.id) FROM {$wpdb->prefix}buddyboss_media m INNER JOIN {$wpdb->posts} p ON p.ID = m.media_id WHERE p.post_type = 'attachment' AND m.album_id = %d";
        $sql			 = $wpdb->prepare( $sql, $buddyboss_media_albums_template->album->id );
        $photos_count	 = $wpdb->get_var( $sql );
        if( !is_wp_error( $photos_count ) && $photos_count > 0 ){
            //update count in albums table so that we dont have to do this again
            $wpdb->update(
                $wpdb->prefix . 'buddyboss_media_albums',
                array(
                    'total_items' => $photos_count,
                ),
                array( 'id' => $buddyboss_media_albums_template->album->id ),
                array(
                    '%d'
                ),
                array( '%d' )
            );
        }
    }
	return sprintf( _n( '%s photo', '%s photos', $photos_count, 'buddyboss-media' ), $photos_count );
}

function buddyboss_media_album_privacy() {
	echo buddyboss_media_album_get_privacy();
}

function buddyboss_media_album_get_privacy() {
	global $buddyboss_media_albums_template;
	return $buddyboss_media_albums_template->album->privacy;
}

function buddyboss_media_btn_move_media() {
	//presence of this activity meta ensures that current acrivity is indeed a buddyboss media photo upload
	if ( buddyboss_media_user_can_move_media() ):
		?>
		<?php $album_id = (int) bp_activity_get_meta( bp_get_activity_id(), 'buddyboss_media_album_id', true ); ?>

		<?php
		//Enabled Group Album in media admin media page
		if ( bp_is_active( 'groups' ) && bp_is_group_single() && ( false === buddyboss_media()->is_group_media_enabled() || false === buddyboss_media()->is_group_albums_enabled() ) ) {
			return false;
		}
		?>

		<a href="#" class="button bp-secondary-action buddyboss_media_move" onclick="return buddyboss_media_initiate_media_move( this );" data-activity_id="<?php bp_activity_id(); ?>" data-album_id="<?php echo $album_id; ?>" title="<?php _e( 'Album', 'buddyboss-media' ); ?>">
			<?php _e( 'Album', 'buddyboss-media' ); ?>
		</a>
		<?php
	endif;
}

add_action( 'bp_activity_entry_meta', 'buddyboss_media_btn_move_media' );

function buddyboss_media_btn_delete_album( $album_id = false ) {
	if ( !$album_id ) {
		$album_id = buddyboss_media_album_get_id();
		//pick author id from global activity and avoid quering database again
		if ( $album_id ) {
			global $buddyboss_media_albums_template;
			$album_author = $buddyboss_media_albums_template->album->user_id;
		}
	}

	if ( !$album_id )
		return '';

	global $wpdb, $bp;
	//is user allowed to delete this album?
	$is_allowed = false;

	if ( !$album_author )
		$album_author = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}buddyboss_media_albums WHERE id=%d", $album_id ) );

	// Allow album author or administrator user to delete the album
	if ( $album_author == bp_loggedin_user_id()
		|| current_user_can( 'manage_options' ) )
		$is_allowed = true;

	if ( !$is_allowed )
		return '';

	$albums_url			 = bp_displayed_user_domain() . buddyboss_media_component_slug() . '/albums/';
	$delete_album_url	 = esc_url( add_query_arg( 'delete', $album_id, $albums_url ) );
	$delete_album_url	 = esc_url( add_query_arg( 'nonce', wp_create_nonce( 'bboss_media_delete_album' ), $delete_album_url ) );

	$confimation_message = __( 'Are you sure you want to delete this album? When you delete an album, all its photos go under global uploads.', 'buddyboss-media' );

	$anchor = "<a class='button album-delete bp-title-button' href='" . esc_url( $delete_album_url ) . "' onclick='return confirm(\"" . $confimation_message . "\");' >" . __( 'Delete Album', 'buddyboss-media' ) . "</a>";
	echo apply_filters( 'buddyboss_media_btn_delete_album', $anchor, $album_id );
}
