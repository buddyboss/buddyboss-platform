<?php
/**
 * Performance Lab -- realistic community seeder.
 *
 * TEMPORARY diagnostic tooling. See `bb-perf-lab.php` for removal instructions.
 *
 * @package    BuddyBoss\PerfLab
 * @subpackage PerfLab
 * @since      BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fills the install with something that behaves like a real community.
 *
 * A demo site with a few dozen rows cannot show what a field selection is worth.
 * Every index fits in memory, every join is trivial, and the endpoint spends its
 * time on framework overhead that no selection can touch. The saving is real but
 * invisible.
 *
 * What makes it visible is not volume on its own. Ten times the activities makes
 * the query slower and leaves field building exactly where it was. What moves
 * field building is *richness per item*: metadata, attachments, comments,
 * reactions, and above all a dense social graph, because the per-user fields
 * -- `followers`, `following`, `friendship_status`, `avatar_urls` -- are the
 * expensive ones and they are the ones a selection can decline to build.
 *
 * So this seeds both, and leans on richness.
 *
 * The work runs in chunks driven by the browser, because a shared host will
 * time out long before fifty thousand rows are in. Every run records the ID
 * ranges it created, so a run can be undone without touching anything that was
 * already there.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Perf_Lab_Seeder {

	/**
	 * Rows written per chunk, per phase.
	 *
	 * Activities are a plain bulk insert and go in large batches. Users and
	 * groups go through the BuddyBoss APIs -- so that member types, xprofile
	 * data and group metadata are all built the way the product builds them --
	 * and those are far slower per row.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	const CHUNK = array(
		'users'      => 40,
		'groups'     => 15,
		'members'    => 200,
		'graph'      => 120,
		'activities' => 1500,
		'comments'   => 800,
		'threads'    => 150,
		'reactions'  => 400,
	);

	/**
	 * Phases, in the order they run.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	const PHASES = array( 'users', 'groups', 'members', 'graph', 'activities', 'comments', 'threads', 'reactions' );

	/**
	 * Start a new seeding job.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $plan {
	 *     Optional. What to create.
	 *
	 *     @type int $users      Members to add. Default 400.
	 *     @type int $groups     Groups to add. Default 30.
	 *     @type int $activities Activities to add. Default 50000.
	 *     @type int $comments   Comments to add. Default 8000.
	 *     @type int $follows    Follows per member. Default 25.
	 *     @type int $friends    Friendships per member. Default 12.
	 *     @type int $reactions  Favourites to hand out. Default 15000.
	 *     @type int $meta       Metadata rows per activity. Default 4.
	 * }
	 *
	 * @return array The job.
	 */
	public static function start( $plan = array() ) {
		$plan = wp_parse_args(
			$plan,
			array(
				'users'      => 400,
				'groups'     => 30,
				'activities' => 50000,
				'comments'   => 8000,
				'follows'    => 25,
				'friends'    => 12,
				'reactions'  => 15000,
				'meta'       => 4,
			)
		);

		foreach ( $plan as $key => $value ) {
			$plan[ $key ] = max( 0, (int) $value );
		}

		$job = array(
			'id'       => (string) wp_generate_password( 8, false ),
			'plan'     => $plan,
			'phase'    => 0,
			'done'     => array_fill_keys( self::PHASES, 0 ),
			'created'  => array(
				'user_ids'      => array(),
				'group_ids'     => array(),
				'activity_from' => 0,
				'activity_to'   => 0,
			),
			'started'  => time(),
			'finished' => 0,
			'log'      => array(),
		);

		update_option( BB_PERF_LAB_JOB, $job, false );

		return $job;
	}

	/**
	 * Read the current job.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array|null
	 */
	public static function job() {
		$job = get_option( BB_PERF_LAB_JOB, null );

		return is_array( $job ) ? $job : null;
	}

	/**
	 * Work on the job for one chunk, then hand control back.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array|WP_Error Progress, or an error when there is no job.
	 */
	public static function tick() {
		$job = self::job();

		if ( null === $job ) {
			return new WP_Error( 'bb_perf_lab_no_job', __( 'There is no seeding job to continue.', 'buddyboss' ) );
		}

		if ( ! empty( $job['finished'] ) ) {
			return self::progress( $job );
		}

		/*
		 * Seeding writes tens of thousands of rows. Leaving the usual invalidation
		 * and counting hooks in place would have the install recount its way
		 * through every one of them, turning minutes into hours, and none of it
		 * survives the cache flush the benchmark does anyway.
		 */
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		remove_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );

		$started = microtime( true );

		// Keep going while there is time in hand, so one round trip does real work.
		while ( microtime( true ) - $started < 12 ) {
			if ( ! isset( self::PHASES[ $job['phase'] ] ) ) {
				$job['finished'] = time();
				break;
			}

			$phase  = self::PHASES[ $job['phase'] ];
			$target = self::target( $job, $phase );

			if ( $job['done'][ $phase ] >= $target ) {
				++$job['phase'];
				continue;
			}

			$method = 'seed_' . $phase;
			$size   = min( self::CHUNK[ $phase ], $target - $job['done'][ $phase ] );

			$job = self::$method( $job, $size );

			$job['done'][ $phase ] += $size;
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		update_option( BB_PERF_LAB_JOB, $job, false );

		return self::progress( $job );
	}

	/**
	 * How many rows a phase has to produce.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $job   The job.
	 * @param string $phase Phase name.
	 *
	 * @return int
	 */
	protected static function target( $job, $phase ) {
		$plan = $job['plan'];

		switch ( $phase ) {
			case 'users':
				return $plan['users'];
			case 'groups':
				return $plan['groups'];
			case 'members':
				// Every group gets a slice of the membership.
				return $plan['groups'] * 12;
			case 'graph':
				return $plan['users'];
			case 'activities':
				return $plan['activities'];
			case 'comments':
				return $plan['comments'];
			case 'threads':
				// Comment trees are rebuilt for a share of the parents.
				return (int) ceil( $plan['comments'] / 6 );
			case 'reactions':
				return $plan['reactions'];
		}

		return 0;
	}

	/**
	 * Summarise the job for the browser.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job The job.
	 *
	 * @return array
	 */
	protected static function progress( $job ) {
		$total = 0;
		$done  = 0;

		foreach ( self::PHASES as $phase ) {
			$target = self::target( $job, $phase );
			$total += $target;
			$done  += min( $job['done'][ $phase ], $target );
		}

		return array(
			'id'       => $job['id'],
			'phase'    => isset( self::PHASES[ $job['phase'] ] ) ? self::PHASES[ $job['phase'] ] : 'done',
			'done'     => $done,
			'total'    => $total,
			'percent'  => $total > 0 ? round( ( $done / $total ) * 100, 1 ) : 100,
			'finished' => ! empty( $job['finished'] ),
			'detail'   => $job['done'],
			'created'  => array(
				'users'      => count( $job['created']['user_ids'] ),
				'groups'     => count( $job['created']['group_ids'] ),
				'activities' => $job['created']['activity_to'] > 0
					? ( $job['created']['activity_to'] - $job['created']['activity_from'] + 1 )
					: 0,
			),
		);
	}

	// ---- Phases ----

	/**
	 * Add members, with the profile data a real member carries.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job  The job.
	 * @param int   $size Members to add.
	 *
	 * @return array The job.
	 */
	protected static function seed_users( $job, $size ) {
		$types = bp_get_member_types();
		$types = ! empty( $types ) ? array_keys( $types ) : array();

		for ( $i = 0; $i < $size; $i++ ) {
			$n     = $job['done']['users'] + $i;
			$login = sprintf( 'perf_%s_%d', $job['id'], $n );
			$first = self::pick( self::$first_names );
			$last  = self::pick( self::$last_names );

			$user_id = wp_insert_user(
				array(
					'user_login'      => $login,
					'user_pass'       => wp_generate_password( 16 ),
					'user_email'      => $login . '@perf.example',
					'display_name'    => $first . ' ' . $last,
					'first_name'      => $first,
					'last_name'       => $last,
					'role'            => 'subscriber',
					'user_registered' => self::past_datetime( wp_rand( 30, 900 ) ),
				)
			);

			if ( is_wp_error( $user_id ) ) {
				continue;
			}

			$job['created']['user_ids'][] = $user_id;

			update_user_meta( $user_id, 'bb_perf_lab_seed', $job['id'] );

			// Profile data, so the xprofile joins have something to find.
			xprofile_set_field_data( 1, $user_id, $first );
			xprofile_set_field_data( 2, $user_id, $last );
			xprofile_set_field_data( 3, $user_id, $first . ' ' . $last );

			if ( ! empty( $types ) ) {
				bp_set_member_type( $user_id, self::pick( $types ) );
			}

			// Recent activity, which is what member directories sort on.
			bp_update_user_last_activity( $user_id, self::past_datetime( wp_rand( 0, 45 ) ) );
		}

		return $job;
	}

	/**
	 * Add groups.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job  The job.
	 * @param int   $size Groups to add.
	 *
	 * @return array The job.
	 */
	protected static function seed_groups( $job, $size ) {
		$users    = self::user_pool( $job );
		$statuses = array( 'public', 'public', 'public', 'private', 'hidden' );

		if ( empty( $users ) ) {
			return $job;
		}

		for ( $i = 0; $i < $size; $i++ ) {
			$n    = $job['done']['groups'] + $i;
			$name = self::pick( self::$group_words ) . ' ' . self::pick( self::$group_nouns );

			$group_id = groups_create_group(
				array(
					'creator_id'   => self::pick( $users ),
					'name'         => $name . ' #' . $n,
					'slug'         => sanitize_title( $name . '-' . $job['id'] . '-' . $n ),
					'description'  => self::paragraph( 2 ),
					'status'       => self::pick( $statuses ),
					'enable_forum' => 0,
					'date_created' => self::past_datetime( wp_rand( 60, 800 ) ),
				)
			);

			if ( empty( $group_id ) || is_wp_error( $group_id ) ) {
				continue;
			}

			$job['created']['group_ids'][] = $group_id;

			groups_update_groupmeta( $group_id, 'bb_perf_lab_seed', $job['id'] );
			groups_update_groupmeta( $group_id, 'last_activity', self::past_datetime( wp_rand( 0, 40 ) ) );
		}

		return $job;
	}

	/**
	 * Put members into groups.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job  The job.
	 * @param int   $size Memberships to add.
	 *
	 * @return array The job.
	 */
	protected static function seed_members( $job, $size ) {
		global $wpdb;

		$users  = self::user_pool( $job );
		$groups = $job['created']['group_ids'];

		if ( empty( $users ) || empty( $groups ) ) {
			return $job;
		}

		$rows = array();

		for ( $i = 0; $i < $size; $i++ ) {
			$group_id = self::pick( $groups );
			$user_id  = self::pick( $users );
			$is_admin = 0 === wp_rand( 0, 11 ) ? 1 : 0;
			$is_mod   = ( 0 === $is_admin && 0 === wp_rand( 0, 9 ) ) ? 1 : 0;

			$rows[] = $wpdb->prepare(
				'(%d, %d, %d, %d, %s, %d, %d, %d)',
				$group_id,
				$user_id,
				$is_admin,
				$is_mod,
				self::past_datetime( wp_rand( 1, 500 ) ),
				1,
				0,
				0
			);
		}

		if ( empty( $rows ) ) {
			return $job;
		}

		$table = $wpdb->prefix . 'bp_groups_members';

		$sql = "INSERT INTO {$table} (group_id, user_id, is_admin, is_mod, date_modified, is_confirmed, is_banned, invite_sent) VALUES " . implode( ',', $rows );

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $job;
	}

	/**
	 * Build the social graph: follows and friendships.
	 *
	 * These are what make the per-member fields expensive, and so what makes
	 * declining to build them worth something.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job  The job.
	 * @param int   $size Members to wire up.
	 *
	 * @return array The job.
	 */
	protected static function seed_graph( $job, $size ) {
		global $wpdb;

		$users = self::user_pool( $job );
		$count = count( $users );

		if ( $count < 3 ) {
			return $job;
		}

		$follow_rows = array();
		$friend_rows = array();

		$follows = min( $job['plan']['follows'], $count - 1 );
		$friends = min( $job['plan']['friends'], $count - 1 );

		for ( $i = 0; $i < $size; $i++ ) {
			$index = $job['done']['graph'] + $i;

			if ( ! isset( $users[ $index ] ) ) {
				break;
			}

			$follower = $users[ $index ];

			for ( $f = 0; $f < $follows; $f++ ) {
				$leader = self::pick( $users );

				if ( $leader === $follower ) {
					continue;
				}

				$follow_rows[] = $wpdb->prepare( '(%d, %d)', $leader, $follower );
			}

			for ( $f = 0; $f < $friends; $f++ ) {
				$friend = self::pick( $users );

				if ( $friend === $follower ) {
					continue;
				}

				$friend_rows[] = $wpdb->prepare(
					'(%d, %d, %d, %d, %s)',
					$follower,
					$friend,
					1,
					0,
					self::past_datetime( wp_rand( 1, 600 ) )
				);
			}
		}

		if ( ! empty( $follow_rows ) && self::table_exists( $wpdb->prefix . 'bp_follow' ) ) {
			$table = $wpdb->prefix . 'bp_follow';
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "INSERT IGNORE INTO {$table} (leader_id, follower_id) VALUES " . implode( ',', $follow_rows ) );
		}

		if ( ! empty( $friend_rows ) && self::table_exists( $wpdb->prefix . 'bp_friends' ) ) {
			$table = $wpdb->prefix . 'bp_friends';
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "INSERT INTO {$table} (initiator_user_id, friend_user_id, is_confirmed, is_limited, date_created) VALUES " . implode( ',', $friend_rows ) );
		}

		return $job;
	}

	/**
	 * Add activities, with the spread of types a real feed carries.
	 *
	 * Written straight to the table. `bp_activity_add()` would fire the whole
	 * notification, mention and moderation chain per row, which at this volume
	 * takes hours and produces the same rows.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job  The job.
	 * @param int   $size Activities to add.
	 *
	 * @return array The job.
	 */
	protected static function seed_activities( $job, $size ) {
		global $wpdb;

		$users  = self::user_pool( $job );
		$groups = $job['created']['group_ids'];

		if ( empty( $users ) ) {
			return $job;
		}

		$table = $wpdb->prefix . 'bp_activity';
		$rows  = array();

		for ( $i = 0; $i < $size; $i++ ) {
			$user_id = self::pick( $users );
			$shape   = self::activity_shape( $groups );
			$content = self::activity_content( $shape['type'], $users );

			$rows[] = $wpdb->prepare(
				'(%d, %s, %s, %s, %s, %s, %s, %d, %d, %s, %s, %d, %d, %d, %d, %s, %s)',
				$user_id,
				$shape['component'],
				$shape['type'],
				self::action_text( $user_id, $shape['type'] ),
				'',
				$content,
				'',
				$shape['item_id'],
				0,
				self::past_datetime( wp_rand( 0, 700 ), true ),
				self::past_datetime( wp_rand( 0, 700 ), true ),
				0,
				0,
				0,
				0,
				$shape['privacy'],
				'published'
			);
		}

		if ( empty( $rows ) ) {
			return $job;
		}

		$sql = "INSERT INTO {$table} (user_id, component, type, action, post_title, content, primary_link, item_id, secondary_item_id, date_recorded, date_updated, hide_sitewide, mptt_left, mptt_right, is_spam, privacy, status) VALUES " . implode( ',', $rows );

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$first = (int) $wpdb->insert_id;
		$last  = $first + count( $rows ) - 1;

		if ( 0 === $job['created']['activity_from'] ) {
			$job['created']['activity_from'] = $first;
		}

		$job['created']['activity_to'] = $last;

		self::seed_activity_meta( $job, $first, $last );

		return $job;
	}

	/**
	 * Give a run of activities their metadata.
	 *
	 * Metadata is where a lot of an activity's cost hides: the endpoint reads it
	 * for the embed URL, the attachments, the edit history and the closed-comment
	 * state, and a feed of bare rows never exercises any of that.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job   The job.
	 * @param int   $first First activity ID.
	 * @param int   $last  Last activity ID.
	 *
	 * @return void
	 */
	protected static function seed_activity_meta( $job, $first, $last ) {
		global $wpdb;

		$per   = max( 1, (int) $job['plan']['meta'] );
		$table = $wpdb->prefix . 'bp_activity_meta';
		$rows  = array();

		for ( $id = $first; $id <= $last; $id++ ) {
			$meta = array(
				'bb_perf_lab_seed'            => $job['id'],
				'bp_activity_reactions_count' => (string) wp_rand( 0, 40 ),
			);

			if ( wp_rand( 0, 4 ) > 2 ) {
				$meta['_link_embed'] = 'https://example.com/story/' . wp_rand( 1000, 99999 );
			}

			if ( wp_rand( 0, 6 ) > 4 ) {
				$meta['_is_edited'] = self::past_datetime( wp_rand( 0, 60 ) );
			}

			if ( wp_rand( 0, 9 ) > 7 ) {
				$meta['bb_is_closed_comments'] = '1';
			}

			if ( wp_rand( 0, 3 ) > 2 ) {
				$meta['bp_activity_mentioned_users'] = wp_json_encode( array( wp_rand( 2, 50 ) ) );
			}

			// Top up to the requested density with inert keys, so the metadata
			// read costs what a mature install's would.
			$extra = $per - count( $meta );

			for ( $e = 0; $e < $extra; $e++ ) {
				$meta[ 'bb_perf_filler_' . $e ] = wp_generate_password( 24, false );
			}

			foreach ( $meta as $key => $value ) {
				$rows[] = $wpdb->prepare( '(%d, %s, %s)', $id, $key, $value );
			}

			// Flush periodically so a wide range cannot build a giant statement.
			if ( count( $rows ) >= 2000 ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( "INSERT INTO {$table} (activity_id, meta_key, meta_value) VALUES " . implode( ',', $rows ) );
				$rows = array();
			}
		}

		if ( ! empty( $rows ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "INSERT INTO {$table} (activity_id, meta_key, meta_value) VALUES " . implode( ',', $rows ) );
		}
	}

	/**
	 * Hang comments off the activities already seeded.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job  The job.
	 * @param int   $size Comments to add.
	 *
	 * @return array The job.
	 */
	protected static function seed_comments( $job, $size ) {
		global $wpdb;

		$users   = self::user_pool( $job );
		$parents = self::parent_pool( $job );

		if ( empty( $users ) || empty( $parents ) ) {
			return $job;
		}

		$table = $wpdb->prefix . 'bp_activity';
		$rows  = array();

		for ( $i = 0; $i < $size; $i++ ) {
			$parent  = self::pick( $parents );
			$user_id = self::pick( $users );

			$rows[] = $wpdb->prepare(
				'(%d, %s, %s, %s, %s, %s, %s, %d, %d, %s, %s, %d, %d, %d, %d, %s, %s)',
				$user_id,
				'activity',
				'activity_comment',
				self::action_text( $user_id, 'activity_comment' ),
				'',
				self::paragraph( 1 ),
				'',
				$parent,
				$parent,
				self::past_datetime( wp_rand( 0, 300 ), true ),
				self::past_datetime( wp_rand( 0, 300 ), true ),
				0,
				0,
				0,
				0,
				'public',
				'published'
			);
		}

		if ( empty( $rows ) ) {
			return $job;
		}

		$sql = "INSERT INTO {$table} (user_id, component, type, action, post_title, content, primary_link, item_id, secondary_item_id, date_recorded, date_updated, hide_sitewide, mptt_left, mptt_right, is_spam, privacy, status) VALUES " . implode( ',', $rows );

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$first                         = (int) $wpdb->insert_id;
		$job['created']['activity_to'] = max( (int) $job['created']['activity_to'], $first + count( $rows ) - 1 );

		return $job;
	}

	/**
	 * Rebuild the comment trees, so threaded reads behave like the real thing.
	 *
	 * Comments went in as plain rows; without their nested-set bounds a threaded
	 * request would find nothing under its parents.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job  The job.
	 * @param int   $size Parents to rebuild.
	 *
	 * @return array The job.
	 */
	protected static function seed_threads( $job, $size ) {
		global $wpdb;

		$from = (int) $job['created']['activity_from'];
		$to   = (int) $job['created']['activity_to'];

		if ( $from < 1 || $to < $from ) {
			return $job;
		}

		$offset = (int) $job['done']['threads'];

		// A range scan over rows this job just wrote; there is no cache to consult.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$parents = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT item_id FROM {$wpdb->prefix}bp_activity WHERE type = 'activity_comment' AND id BETWEEN %d AND %d ORDER BY item_id ASC LIMIT %d OFFSET %d",
				$from,
				$to,
				$size,
				$offset
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( (array) $parents as $parent_id ) {
			BP_Activity_Activity::rebuild_activity_comment_tree( (int) $parent_id );
		}

		return $job;
	}

	/**
	 * Hand out favourites.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job  The job.
	 * @param int   $size Favourites to add.
	 *
	 * @return array The job.
	 */
	protected static function seed_reactions( $job, $size ) {
		global $wpdb;

		$users   = self::user_pool( $job );
		$parents = self::parent_pool( $job );

		if ( empty( $users ) || empty( $parents ) ) {
			return $job;
		}

		$favourites = array();
		$counts     = array();

		for ( $i = 0; $i < $size; $i++ ) {
			$user_id     = self::pick( $users );
			$activity_id = self::pick( $parents );

			$favourites[ $user_id ][] = $activity_id;

			if ( ! isset( $counts[ $activity_id ] ) ) {
				$counts[ $activity_id ] = 0;
			}

			++$counts[ $activity_id ];
		}

		foreach ( $favourites as $user_id => $ids ) {
			$existing = (array) bp_get_user_meta( $user_id, 'bp_favorite_activities', true );
			$merged   = array_values( array_unique( array_merge( array_filter( $existing ), $ids ) ) );

			bp_update_user_meta( $user_id, 'bp_favorite_activities', $merged );
		}

		$table = $wpdb->prefix . 'bp_activity_meta';
		$rows  = array();

		foreach ( $counts as $activity_id => $count ) {
			$rows[] = $wpdb->prepare( '(%d, %s, %s)', $activity_id, 'favorite_count', (string) $count );
		}

		if ( ! empty( $rows ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "INSERT INTO {$table} (activity_id, meta_key, meta_value) VALUES " . implode( ',', $rows ) );
		}

		return $job;
	}

	// ---- Teardown ----

	/**
	 * Remove everything the current job created.
	 *
	 * Only rows inside the ID ranges this run recorded are touched, so an
	 * install that had content before seeding keeps it.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Counts of what was removed.
	 */
	public static function purge() {
		global $wpdb;

		$job = self::job();

		if ( null === $job ) {
			return array(
				'activities' => 0,
				'users'      => 0,
				'groups'     => 0,
			);
		}

		$removed = array(
			'activities' => 0,
			'users'      => 0,
			'groups'     => 0,
		);

		$from = (int) $job['created']['activity_from'];
		$to   = (int) $job['created']['activity_to'];

		if ( $from > 0 && $to >= $from ) {
			$removed['activities'] = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bp_activity WHERE id BETWEEN %d AND %d", $from, $to ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bp_activity_meta WHERE activity_id BETWEEN %d AND %d", $from, $to ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		foreach ( (array) $job['created']['group_ids'] as $group_id ) {
			groups_delete_group( (int) $group_id );
			++$removed['groups'];
		}

		require_once ABSPATH . 'wp-admin/includes/user.php';

		foreach ( (array) $job['created']['user_ids'] as $user_id ) {
			wp_delete_user( (int) $user_id );
			++$removed['users'];
		}

		delete_option( BB_PERF_LAB_JOB );

		return $removed;
	}

	// ---- Content ----

	/**
	 * Decide what kind of activity to write next.
	 *
	 * The mix follows what a working community actually produces: mostly status
	 * updates, a good share of them inside groups, and a tail of attachments and
	 * forum posts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $groups Group IDs available.
	 *
	 * @return array {
	 *     @type string $component Component the activity belongs to.
	 *     @type string $type      Activity type.
	 *     @type int    $item_id   Primary item, e.g. the group.
	 *     @type string $privacy   Privacy setting.
	 * }
	 */
	protected static function activity_shape( $groups ) {
		$roll = wp_rand( 1, 100 );

		if ( $roll <= 44 ) {
			return array(
				'component' => 'activity',
				'type'      => 'activity_update',
				'item_id'   => 0,
				'privacy'   => self::pick( array( 'public', 'public', 'public', 'loggedin', 'friends', 'onlyme' ) ),
			);
		}

		if ( $roll <= 74 && ! empty( $groups ) ) {
			return array(
				'component' => 'groups',
				'type'      => 'activity_update',
				'item_id'   => self::pick( $groups ),
				'privacy'   => 'public',
			);
		}

		if ( $roll <= 82 ) {
			return array(
				'component' => 'activity',
				'type'      => 'activity_update',
				'item_id'   => 0,
				'privacy'   => 'media',
			);
		}

		if ( $roll <= 88 ) {
			return array(
				'component' => 'activity',
				'type'      => 'activity_update',
				'item_id'   => 0,
				'privacy'   => 'document',
			);
		}

		if ( $roll <= 92 ) {
			return array(
				'component' => 'activity',
				'type'      => 'activity_update',
				'item_id'   => 0,
				'privacy'   => 'video',
			);
		}

		if ( $roll <= 96 ) {
			return array(
				'component' => 'forums',
				'type'      => 'bbp_topic_create',
				'item_id'   => wp_rand( 1, 400 ),
				'privacy'   => 'public',
			);
		}

		return array(
			'component' => 'members',
			'type'      => self::pick( array( 'new_member', 'updated_profile', 'new_avatar', 'friendship_created' ) ),
			'item_id'   => 0,
			'privacy'   => 'public',
		);
	}

	/**
	 * Body text for an activity.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $type  Activity type.
	 * @param array  $users Member IDs, for mentions.
	 *
	 * @return string
	 */
	protected static function activity_content( $type, $users ) {
		if ( in_array( $type, array( 'new_member', 'new_avatar', 'updated_profile', 'friendship_created' ), true ) ) {
			return '';
		}

		$content = self::paragraph( wp_rand( 1, 4 ) );

		// A mention now and then, since resolving them is real work.
		if ( 0 === wp_rand( 0, 5 ) && ! empty( $users ) ) {
			$user = get_userdata( self::pick( $users ) );

			if ( $user ) {
				$content = '@' . $user->user_login . ' ' . $content;
			}
		}

		if ( 0 === wp_rand( 0, 7 ) ) {
			$content .= ' https://example.com/read/' . wp_rand( 100, 99999 );
		}

		return $content;
	}

	/**
	 * The rendered action line an activity carries.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int    $user_id Author.
	 * @param string $type    Activity type.
	 *
	 * @return string
	 */
	protected static function action_text( $user_id, $type ) {
		$name = bp_core_get_user_displayname( $user_id );

		switch ( $type ) {
			case 'activity_comment':
				return sprintf( '%s posted a new activity comment', esc_html( $name ) );
			case 'bbp_topic_create':
				return sprintf( '%s started a discussion', esc_html( $name ) );
			case 'new_member':
				return sprintf( '%s became a registered member', esc_html( $name ) );
			default:
				return sprintf( '%s posted an update', esc_html( $name ) );
		}
	}

	/**
	 * A paragraph of plausible community chatter.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $sentences How many sentences.
	 *
	 * @return string
	 */
	protected static function paragraph( $sentences ) {
		$out   = array();
		$total = max( 1, (int) $sentences );

		for ( $i = 0; $i < $total; $i++ ) {
			$out[] = self::pick( self::$sentences );
		}

		return implode( ' ', $out );
	}

	// ---- Helpers ----

	/**
	 * Members available to the job, falling back to whoever already exists.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job The job.
	 *
	 * @return array Member IDs.
	 */
	protected static function user_pool( $job ) {
		if ( ! empty( $job['created']['user_ids'] ) ) {
			return $job['created']['user_ids'];
		}

		static $fallback = null;

		if ( null === $fallback ) {
			$fallback = get_users(
				array(
					'fields' => 'ID',
					'number' => 200,
				)
			);
			$fallback = array_map( 'intval', (array) $fallback );
		}

		return $fallback;
	}

	/**
	 * Activities that can take comments and favourites.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $job The job.
	 *
	 * @return array Activity IDs.
	 */
	protected static function parent_pool( $job ) {
		static $pool = null;

		if ( null !== $pool ) {
			return $pool;
		}

		global $wpdb;

		$from = (int) $job['created']['activity_from'];
		$to   = (int) $job['created']['activity_to'];

		if ( $from < 1 || $to < $from ) {
			return array();
		}

		$sql = $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}bp_activity WHERE id BETWEEN %d AND %d AND type = 'activity_update' ORDER BY id ASC LIMIT 4000",
			$from,
			$to
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Likewise: freshly written rows, read once and held in a static.
		$ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$pool = array_map( 'intval', (array) $ids );

		return $pool;
	}

	/**
	 * Whether a table is present.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $table Table name.
	 *
	 * @return bool
	 */
	protected static function table_exists( $table ) {
		global $wpdb;

		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * A datetime some days in the past.
	 *
	 * Activities are spread across a long window on purpose: a feed whose rows
	 * all share a timestamp sorts and paginates nothing like a real one.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int  $days_ago How many days back.
	 * @param bool $jitter   Optional. Scatter within the day. Default false.
	 *
	 * @return string MySQL datetime.
	 */
	protected static function past_datetime( $days_ago, $jitter = false ) {
		$stamp = time() - ( (int) $days_ago * DAY_IN_SECONDS );

		if ( $jitter ) {
			$stamp -= wp_rand( 0, DAY_IN_SECONDS );
		}

		return gmdate( 'Y-m-d H:i:s', $stamp );
	}

	/**
	 * One item from a list, at random.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $choices List to choose from.
	 *
	 * @return mixed
	 */
	protected static function pick( $choices ) {
		$choices = array_values( (array) $choices );

		if ( empty( $choices ) ) {
			return '';
		}

		return $choices[ wp_rand( 0, count( $choices ) - 1 ) ];
	}

	/**
	 * First names.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	protected static $first_names = array(
		'Amara',
		'Bilal',
		'Cora',
		'Dmitri',
		'Elena',
		'Farid',
		'Greta',
		'Hana',
		'Idris',
		'Jonas',
		'Kaya',
		'Lucia',
		'Malik',
		'Nadia',
		'Oscar',
		'Priya',
		'Quinn',
		'Rosa',
		'Samir',
		'Tomas',
		'Ursula',
		'Viktor',
		'Wren',
		'Yusuf',
	);

	/**
	 * Last names.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	protected static $last_names = array(
		'Abbott',
		'Bergström',
		'Castellanos',
		'Duarte',
		'Eriksen',
		'Fontaine',
		'Grimaldi',
		'Halvorsen',
		'Ivanov',
		'Jankowski',
		'Kovács',
		'Lindqvist',
		'Moreau',
		'Nakamura',
		'Okonkwo',
		'Petrov',
		'Rahman',
		'Sørensen',
	);

	/**
	 * Words that start a group name.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	protected static $group_words = array(
		'Weekend',
		'Northside',
		'Open',
		'Quiet',
		'First',
		'Coastal',
		'Winter',
		'Practical',
		'Late Night',
		'Sunday',
		'Downtown',
		'Amateur',
	);

	/**
	 * Words that finish a group name.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	protected static $group_nouns = array(
		'Runners',
		'Readers',
		'Builders',
		'Cooks',
		'Photographers',
		'Gardeners',
		'Cyclists',
		'Writers',
		'Woodworkers',
		'Birdwatchers',
		'Climbers',
	);

	/**
	 * Sentences the generated posts are assembled from.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	protected static $sentences = array(
		'Finally got the back garden cleared out this weekend.',
		'Does anyone here have a recommendation for a decent second-hand shop nearby?',
		'Six months in and I am still learning something new every week.',
		'Took the long route home and it was absolutely worth it.',
		'Thanks to everyone who turned up on Saturday, that was a good turnout.',
		'Reposting this because the first one went up with the wrong date.',
		'Small win: the thing I have been putting off for a month is done.',
		'If anyone is free Thursday evening there is a spare place going.',
		'Not sure this is the right group for it, but worth asking.',
		'Update on the earlier post -- it turned out to be much simpler than I thought.',
		'Third attempt at this and I think I have finally got it right.',
		'Genuinely surprised by how much difference the small change made.',
		'Question for the more experienced folks here before I commit to anything.',
		'Photos from last month, finally sorted through them.',
		'Starting again from scratch after the last attempt went sideways.',
		'Whoever suggested the earlier start time was completely right.',
	);
}
