<?php

/**
 * PROD-10649: the RSS feeds, the modal sync AJAX, the activity oEmbed route and the
 * video/document scopes follow the read rules of the activity and its root post.
 *
 * @group activity
 */
class BP_Tests_Activity_Functions_BbActivityReadPathAccess extends BP_UnitTestCase {

	protected $author;
	protected $member;
	protected $stranger;
	protected $group_id;
	protected $root;
	protected $comment;
	protected $public_post;

	public function setUp(): void {
		parent::setUp();

		$this->author   = self::factory()->user->create();
		$this->member   = self::factory()->user->create();
		$this->stranger = self::factory()->user->create();

		$this->group_id = self::factory()->group->create(
			array(
				'creator_id' => $this->author,
				'status'     => 'private',
			)
		);
		self::add_user_to_group( $this->member, $this->group_id );

		$this->root = self::factory()->activity->create(
			array(
				'component'     => bboss()->groups->id,
				'type'          => 'activity_update',
				'user_id'       => $this->author,
				'item_id'       => $this->group_id,
				'hide_sitewide' => true,
				'content'       => 'private group post',
			)
		);

		$this->comment = bboss_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $this->root,
				'content'     => 'private group comment',
			)
		);

		$this->public_post = self::factory()->activity->create(
			array(
				'component' => bboss()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'content'   => 'public profile post',
			)
		);
	}

	public function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	/**
	 * Render a feed's items the way BBoss_Activity_Feed::output() does, without its die().
	 *
	 * The args match bp_activity_action_*_feed(); setup_properties() adds the stream default.
	 */
	protected function render_feed( $viewer_id, $displayed_id, $activity_args, $ungated = false ) {
		$this->set_current_user( $viewer_id );
		bboss()->displayed_user->id = $displayed_id;

		$feed = ( new ReflectionClass( 'BBoss_Activity_Feed' ) )->newInstanceWithoutConstructor();
		foreach ( array( 'id' => 'personal', 'title' => 'Feed', 'link' => home_url(), 'description' => 'Feed', 'ttl' => 30, 'update_period' => 'hourly', 'update_frequency' => 2 ) as $key => $value ) {
			$feed->{$key} = $value;
		}
		$feed->activity_args = bboss_parse_args(
			$activity_args,
			array(
				'max'              => 50,
				'per_page'         => 50,
				'display_comments' => 'stream',
			)
		);

		$output = new ReflectionMethod( $feed, 'output' );
		$output->setAccessible( true );

		if ( $ungated ) {
			add_filter( 'bb_activity_user_can_view', '__return_true' );
		}

		ob_start();
		@$output->invoke( $feed ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- header() calls after test output has started.
		$xml = ob_get_clean();

		remove_filter( 'bb_activity_user_can_view', '__return_true' );
		bboss()->displayed_user->id = 0;

		return $xml;
	}

	/**
	 * The personal feed of bboss_activity_action_personal_feed(): the 'feed' action is the scope.
	 */
	protected function personal_feed( $viewer_id, $ungated = false ) {
		return $this->render_feed(
			$viewer_id,
			$this->author,
			array(
				'user_id' => $this->author,
				'scope'   => 'feed',
			),
			$ungated
		);
	}

	/**
	 * The mentions feed of bboss_activity_action_mentions_feed() for $mentioned_id.
	 */
	protected function mentions_feed( $viewer_id, $mentioned_id, $ungated = false ) {
		return $this->render_feed(
			$viewer_id,
			$mentioned_id,
			array(
				// The feed searches '@username'; test users get a mention name that differs.
				'search_terms' => '@' . bboss_activity_get_user_mentionname( $mentioned_id ),
				'scope'        => 'mentions',
			),
			$ungated
		);
	}

	public function test_mentions_feed_skips_private_group_comment() {
		$mentioned = $this->stranger;
		bboss_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $this->root,
				'content'     => '@' . bboss_activity_get_user_mentionname( $mentioned ) . ' mention in private group',
			)
		);

		// Without the per-item check the scope lists it, with the root post's text.
		$this->assertStringContainsString( 'mention in private group', $this->mentions_feed( 0, $mentioned, true ) );

		$this->assertStringNotContainsString( 'mention in private group', $this->mentions_feed( 0, $mentioned ) );
		$this->assertStringNotContainsString( 'mention in private group', $this->mentions_feed( $mentioned, $mentioned ) );
		$this->assertStringContainsString( 'mention in private group', $this->mentions_feed( $this->member, $mentioned ) );
	}

	public function test_personal_feed_skips_friends_only_post_and_comment_for_guest() {
		$friends_post = self::factory()->activity->create(
			array(
				'component' => bboss()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'privacy'   => 'friends',
				'content'   => 'friends only post',
			)
		);
		bboss_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $friends_post,
				'content'     => 'friends only comment',
			)
		);

		$this->assertStringContainsString( 'friends only comment', $this->personal_feed( 0, true ) );

		$xml = $this->personal_feed( 0 );
		$this->assertStringContainsString( 'public profile post', $xml );
		$this->assertStringNotContainsString( 'friends only comment', $xml );
		$this->assertStringNotContainsString( 'friends only post', $xml );

		$this->assertStringContainsString( 'friends only comment', $this->personal_feed( $this->author ) );
	}

	public function test_personal_feed_skips_comment_from_group_made_private() {
		$group = self::factory()->group->create(
			array(
				'creator_id' => $this->author,
				'status'     => 'public',
			)
		);
		self::add_user_to_group( $this->member, $group );
		$post = self::factory()->activity->create(
			array(
				'component' => bboss()->groups->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'item_id'   => $group,
				'content'   => 'once public group post',
			)
		);
		bboss_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $post,
				'content'     => 'comment from public days',
			)
		);

		bboss_groups_edit_group_settings( $group, false, 'private' );

		// The status change hides the group's rows but not the comment row. The query leaves the
		// comment out anyway, because its post is hidden, even without the feed's own check.
		$this->assertStringNotContainsString( 'comment from public days', $this->personal_feed( 0, true ) );

		$this->assertStringNotContainsString( 'comment from public days', $this->personal_feed( 0 ) );

		// A sitewide feed leaves it out for members too, like the post itself and like comments
		// written after the change; members read both in the group's own feed.
		$this->assertStringNotContainsString( 'comment from public days', $this->personal_feed( $this->member ) );
	}

	/**
	 * Call the modal sync AJAX handler and return its decoded JSON response.
	 */
	protected function sync_from_modal( $user_id, $activity_id, $scope = '' ) {
		$this->set_current_user( $user_id );

		$_POST = array(
			'nonce'       => wp_create_nonce( 'bp_nouveau_activity' ),
			'activity_id' => $activity_id,
		);
		if ( $scope ) {
			$_POST['scope']    = $scope;
			$_REQUEST['scope'] = $scope;
		}
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$die_handler = function () {
			return function () {
				throw new WPDieException( 'ajax' );
			};
		};
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $die_handler );

		ob_start();
		try {
			bboss_nouveau_ajax_activity_sync_from_modal();
		} catch ( WPDieException $e ) {
			unset( $e );
		}
		$response = ob_get_clean();

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', $die_handler );
		unset( $_REQUEST['scope'] );

		return json_decode( $response, true );
	}

	public function test_sync_from_modal_refuses_private_group_comment_to_guest() {
		$response = $this->sync_from_modal( 0, $this->comment, 'just-me,media' );

		$this->assertFalse( $response['success'] );
		$this->assertStringNotContainsString( 'private group comment', wp_json_encode( $response ) );
	}

	public function test_sync_from_modal_refuses_private_group_post_to_non_member() {
		$response = $this->sync_from_modal( $this->stranger, $this->root );

		$this->assertFalse( $response['success'] );
	}

	public function test_sync_from_modal_renders_private_group_post_for_member() {
		$response = $this->sync_from_modal( $this->member, $this->root );

		// The test install has no activity templates, so the rendered HTML is empty.
		$this->assertTrue( $response['success'] );
	}

	/**
	 * Ask the activity oEmbed REST route for an activity.
	 */
	protected function oembed( $user_id, $activity_id ) {
		$this->set_current_user( $user_id );

		$request = new WP_REST_Request( 'GET', '/oembed/1.0/embed/activity' );
		$request->set_param( 'url', bboss_core_get_user_domain( $this->author ) . bboss_get_activity_slug() . '/' . $activity_id . '/' );

		return rest_do_request( $request );
	}

	public function test_oembed_refuses_private_group_post_and_comment_to_guest() {
		if ( ! bboss_is_active( 'activity', 'embeds' ) ) {
			$this->markTestSkipped( 'Activity embeds are off.' );
		}

		$this->assertSame( 404, $this->oembed( 0, $this->root )->get_status() );
		$this->assertSame( 404, $this->oembed( 0, $this->comment )->get_status() );
		$this->assertSame( 404, $this->oembed( $this->stranger, $this->comment )->get_status() );
	}

	public function test_oembed_accepts_public_post_and_member_view() {
		// Rendering the 200 response needs wp-embed.js, which the test install lacks, so ask
		// the URL validator that gates the route.
		$extension = ( new ReflectionClass( 'BBoss_Activity_oEmbed_Extension' ) )->newInstanceWithoutConstructor();
		$validate  = new ReflectionMethod( $extension, 'validate_url_to_item_id' );
		$validate->setAccessible( true );
		$url = function ( $activity_id ) {
			return bboss_core_get_user_domain( $this->author ) . bboss_get_activity_slug() . '/' . $activity_id . '/';
		};

		$this->set_current_user( 0 );
		$this->assertSame( $this->public_post, $validate->invoke( $extension, $url( $this->public_post ) ) );
		$this->assertFalse( $validate->invoke( $extension, $url( $this->comment ) ) );

		$this->set_current_user( $this->member );
		$this->assertSame( $this->comment, $validate->invoke( $extension, $url( $this->comment ) ) );
	}

	/**
	 * Call an AJAX handler and return its decoded JSON response.
	 */
	protected function call_ajax( $handler ) {
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$die_handler = function () {
			return function () {
				throw new WPDieException( 'ajax' );
			};
		};
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $die_handler );

		ob_start();
		try {
			call_user_func( $handler );
		} catch ( WPDieException $e ) {
			unset( $e );
		}
		$response = ob_get_clean();

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', $die_handler );

		return json_decode( $response, true );
	}

	/**
	 * The activity loop AJAX as it runs when the Referer is a single activity page.
	 */
	protected function single_activity_loop( $user_id, $activity_id ) {
		$this->set_current_user( $user_id );

		$bp                    = bboss();
		$bp->current_component = 'activity';
		$bp->current_action    = (string) $activity_id;

		$_POST = array(
			'object' => 'activity',
			'nonce'  => wp_create_nonce( 'bp_nouveau_activity' ),
			'scope'  => 'media',
		);

		$response = $this->call_ajax( 'bp_nouveau_ajax_object_template_loader' );

		$bp->current_component = '';
		$bp->current_action    = '';

		return $response;
	}

	public function test_single_activity_loop_refuses_private_group_items() {
		$this->assertTrue( bboss_is_active( 'activity' ) );

		$this->assertFalse( $this->single_activity_loop( 0, $this->root )['success'] );
		$this->assertFalse( $this->single_activity_loop( 0, $this->comment )['success'] );
		$this->assertFalse( $this->single_activity_loop( $this->stranger, $this->root )['success'] );
	}

	public function test_single_activity_loop_runs_for_member() {
		$response = $this->single_activity_loop( $this->member, $this->root );

		// The loop runs; the test install has no templates, so its HTML may be empty.
		$this->assertTrue( $response['success'] );
	}

	/**
	 * Ask the reactions AJAX for an activity's reactions.
	 */
	protected function reactions( $user_id, $activity_id ) {
		$this->set_current_user( $user_id );

		$_POST = array(
			'_wpnonce'  => wp_create_nonce( 'bp_nouveau_activity' ),
			'item_id'   => $activity_id,
			'item_type' => 'activity_comment',
		);

		return $this->call_ajax( 'bb_get_activity_reaction_ajax_callback' );
	}

	public function test_reactions_list_follows_root_privacy() {
		if ( ! function_exists( 'bboss_get_activity_reaction_ajax_callback' ) ) {
			$this->markTestSkipped( 'Reactions are not loaded.' );
		}

		$only_me = self::factory()->activity->create(
			array(
				'component' => bboss()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'privacy'   => 'onlyme',
			)
		);
		$comment = bboss_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $only_me,
				'content'     => 'only me comment',
			)
		);

		$refused = __( 'Activity not found.', 'buddyboss-platform' );

		$this->assertSame( $refused, $this->reactions( 0, $comment )['data']['message'] );
		$this->assertSame( $refused, $this->reactions( $this->stranger, $comment )['data']['message'] );
		$this->assertSame( $refused, $this->reactions( $this->stranger, $this->comment )['data']['message'] );

		// The author gets past the access check; there are no reactions to list.
		$this->assertNotSame( $refused, $this->reactions( $this->author, $comment )['data']['message'] );
		$this->assertNotSame( $refused, $this->reactions( $this->member, $this->comment )['data']['message'] );
	}

	public function test_video_and_document_scopes_match_only_requested_ids() {
		$registered = array( 'column' => 'privacy', 'value' => 'video' );
		$no_match   = array(
			'column' => 'id',
			'value'  => 0,
		);

		foreach ( array( 'video', 'document' ) as $scope ) {
			$add_scope = function () use ( $registered ) {
				return $registered;
			};
			add_filter( "bp_activity_set_{$scope}_scope_args", $add_scope, 10, 2 );

			$this->assertSame( $no_match, apply_filters( "bp_activity_set_{$scope}_scope_args", array(), array( 'filter' => array() ) ) );
			$this->assertSame( $registered, apply_filters( "bp_activity_set_{$scope}_scope_args", array(), array( 'in' => array( 5 ) ) ) );

			remove_filter( "bp_activity_set_{$scope}_scope_args", $add_scope, 10 );

			// No component handles the scope (it is off): still nothing matches without IDs.
			$this->assertSame( $no_match, apply_filters( "bp_activity_set_{$scope}_scope_args", array(), array() ) );
			$this->assertSame( array(), apply_filters( "bp_activity_set_{$scope}_scope_args", array(), array( 'in' => array( 5 ) ) ) );
		}
	}

	/**
	 * Build the activity loop query string the way the activity_filter AJAX does, then run it.
	 *
	 * @return int[] IDs of the activities the loop returns.
	 */
	protected function filtered_loop_ids( $user_id, $post ) {
		$this->set_current_user( $user_id );

		$bp                    = bboss();
		$bp->current_component = 'activity';
		$bp->current_action    = '';

		$_POST = array_merge(
			array(
				'object' => 'activity',
				'scope'  => 'all',
				'page'   => 1,
			),
			$post
		);

		$query_string = bboss_nouveau_ajax_querystring( '', 'activity' );
		$ids          = array();
		if ( bboss_has_activities( $query_string ) ) {
			while ( bboss_activities() ) {
				bboss_the_activity();
				$ids[] = (int) bboss_get_activity_id();
			}
		}

		$bp->current_component = '';

		return $ids;
	}

	public function test_activity_filter_ignores_loop_args_injected_into_its_values() {
		$injected = '&include=' . $this->root . ',' . $this->comment . '&show_hidden=1&display_comments=stream&scope=';

		foreach ( array( 0, $this->stranger ) as $viewer ) {
			foreach ( array( 'filter', 'scope', 'order_by' ) as $key ) {
				$value = 'scope' === $key ? 'all' . $injected : ( 'order_by' === $key ? 'date_recorded' . $injected : $injected );
				$ids   = $this->filtered_loop_ids( $viewer, array( $key => $value ) );

				$this->assertNotContains( $this->root, $ids, "Private group post leaked through `{$key}` to user {$viewer}." );
				$this->assertNotContains( $this->comment, $ids, "Private group comment leaked through `{$key}` to user {$viewer}." );
			}
		}

		// The same injection through a heartbeat request.
		$ids = $this->filtered_loop_ids( 0, array( 'data' => array( 'bp_heartbeat' => array( 'filter' => $injected ) ) ) );
		$this->assertNotContains( $this->root, $ids );
	}

	public function test_activity_filter_keeps_request_values_as_values() {
		$this->set_current_user( $this->member );
		bboss()->current_component = 'activity';

		$_POST        = array(
			'object'   => 'activity',
			'filter'   => 'activity_update,new_avatar',
			'scope'    => 'just-me',
			'order_by' => 'date_updated',
			'page'     => 1,
		);
		$query_string = wp_parse_args( bboss_nouveau_ajax_querystring( '', 'activity' ) );

		$this->assertSame( 'activity_update,new_avatar', $query_string['type'] );
		$this->assertSame( 'just-me', $query_string['scope'] );
		$this->assertSame( 'date_updated', $query_string['order_by'] );

		// An injected argument stays inside the value it was sent in.
		$_POST['filter'] = 'activity_update&include=' . $this->root . '&show_hidden=1';
		$query_string    = wp_parse_args( bboss_nouveau_ajax_querystring( '', 'activity' ) );

		$this->assertSame( $_POST['filter'], $query_string['type'] );
		$this->assertArrayNotHasKey( 'include', $query_string );
		$this->assertArrayNotHasKey( 'show_hidden', $query_string );

		bboss()->current_component = '';
	}

	public function test_loggedin_privacy_checks_the_given_user_without_a_session() {
		$loggedin_post = self::factory()->activity->create(
			array(
				'component' => bboss()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'privacy'   => 'loggedin',
			)
		);

		// CLI and cron run with no current user but pass the member they act for.
		$this->set_current_user( 0 );
		$this->assertTrue( bboss_activity_user_can_view( $loggedin_post, $this->member ) );
		$this->assertIsInt(
			bboss_activity_new_comment(
				array(
					'user_id'     => $this->member,
					'activity_id' => $loggedin_post,
					'content'     => 'comment without a session',
					'error_type'  => 'wp_error',
				)
			)
		);

		// A guest is still refused.
		$this->assertFalse( bboss_activity_user_can_view( $loggedin_post, 0 ) );
	}

	public function test_new_comment_refuses_a_comment_as_the_root() {
		$this->set_current_user( $this->member );

		$result = bboss_activity_new_comment(
			array(
				'user_id'     => $this->member,
				'activity_id' => $this->comment,
				'content'     => 'comment hung off a comment',
				'error_type'  => 'wp_error',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_parent_activity', $result->get_error_code() );

		// Replying to the comment through its root still works.
		$this->assertIsInt(
			bboss_activity_new_comment(
				array(
					'user_id'     => $this->member,
					'activity_id' => $this->root,
					'parent_id'   => $this->comment,
					'content'     => 'proper reply',
					'error_type'  => 'wp_error',
				)
			)
		);
	}

	public function test_listed_comment_follows_its_hidden_post() {
		// A comment written while the group was public keeps hide_sitewide = 0 after the group
		// is made private; only its post is hidden.
		$group   = self::factory()->group->create( array( 'creator_id' => $this->author ) );
		$post    = self::factory()->activity->create(
			array(
				'component' => bboss()->groups->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'item_id'   => $group,
			)
		);
		$comment = bboss_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $post,
				'content'     => 'comment from public days',
			)
		);
		bboss_groups_edit_group_settings( $group, false, 'private' );

		$this->assertSame( 0, (int) ( new BBoss_Activity_Activity( $comment ) )->hide_sitewide );

		$listed = function ( $args ) {
			return wp_list_pluck( bboss_activity_get( array_merge( array( 'display_comments' => 'stream', 'per_page' => 0, 'count_total' => true ), $args ) )['activities'], 'id' );
		};

		$this->assertNotContains( $comment, array_map( 'intval', $listed( array() ) ) );
		$this->assertContains( $comment, array_map( 'intval', $listed( array( 'show_hidden' => true ) ) ) );

		// Scopes switch show_hidden on and ask for hide_sitewide = 0 themselves.
		$this->set_current_user( $this->stranger );
		$this->assertNotContains( $comment, array_map( 'intval', $listed( array( 'scope' => 'public' ) ) ) );
		$this->assertNotContains( $comment, array_map( 'intval', $listed( array( 'scope' => 'just-me,public,mentions,friends,groups' ) ) ) );

		// Comments on visible posts are still listed.
		$this->assertContains( $this->comment, array_map( 'intval', $listed( array( 'show_hidden' => true ) ) ) );
		$public_comment = bboss_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $this->public_post,
				'content'     => 'public comment',
			)
		);
		$this->assertContains( $public_comment, array_map( 'intval', $listed( array() ) ) );
	}

	public function test_moderator_bypass_checks_the_given_user() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );

		// WP-CLI and cron: no current user, acting for a site admin who is not a member.
		$this->set_current_user( 0 );
		$this->assertTrue( bboss_activity_user_can_view( $this->root, $admin ) );

		// The admin's own session still sees everything.
		$this->set_current_user( $admin );
		$this->assertTrue( bboss_activity_user_can_view( $this->root ) );

		// A member's session does not borrow anyone's moderator rights.
		$this->set_current_user( $this->member );
		$this->assertFalse( bboss_activity_user_can_view( $this->root, $this->stranger ) );
	}

	public function test_activity_filter_query_string_ignores_the_arg_separator_setting() {
		$separator = ini_get( 'arg_separator.output' );
		ini_set( 'arg_separator.output', '&amp;' ); // phpcs:ignore WordPress.PHP.IniSet.Risky

		$this->set_current_user( $this->member );
		bboss()->current_component = 'activity';
		$_POST = array(
			'object' => 'activity',
			'filter' => 'activity_update',
			'scope'  => 'just-me',
			'page'   => 2,
		);

		$query_string = wp_parse_args( bboss_nouveau_ajax_querystring( '', 'activity' ) );

		ini_set( 'arg_separator.output', $separator ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		bboss()->current_component = '';

		$this->assertSame( '2', $query_string['page'] );
		$this->assertSame( 'just-me', $query_string['scope'] );
		$this->assertArrayNotHasKey( 'amp;page', $query_string );
	}

	public function test_request_scope_nothing_handles_falls_back_to_the_default() {
		$friends_post  = self::factory()->activity->create(
			array(
				'component' => bboss()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'privacy'   => 'friends',
			)
		);
		$loggedin_post = self::factory()->activity->create(
			array(
				'component' => bboss()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'privacy'   => 'loggedin',
			)
		);

		$this->assertSame( 'all', bboss_activity_get_request_scope( 'xyz' ) );
		$this->assertSame( 'all', bboss_activity_get_request_scope( 'all,xyz' ) );
		$this->assertSame( 'just-me', bboss_activity_get_request_scope( 'just-me,xyz' ) );
		$this->assertSame( 'favorites,mentions', bboss_activity_get_request_scope( 'favorites, mentions' ) );

		// The activity loop AJAX and heartbeat, as a guest.
		foreach ( array( 'xyz', 'all,xyz', 'video', 'document' ) as $scope ) {
			$listed = $this->filtered_loop_ids( 0, array( 'scope' => $scope ) );
			$this->assertNotContains( $friends_post, $listed, "scope={$scope}" );
			$this->assertNotContains( $loggedin_post, $listed, "scope={$scope}" );
		}
		$listed = $this->filtered_loop_ids( 0, array( 'data' => array( 'bp_heartbeat' => array( 'scope' => 'xyz' ) ) ) );
		$this->assertNotContains( $friends_post, $listed );

		// `?scope=` read by bp_has_activities() itself.
		$this->set_current_user( 0 );
		bboss()->current_component = 'activity';
		$_REQUEST['scope'] = 'xyz';
		$ids               = array();
		if ( bboss_has_activities( array( 'per_page' => 0 ) ) ) {
			while ( bboss_activities() ) {
				bboss_the_activity();
				$ids[] = (int) bboss_get_activity_id();
			}
		}
		unset( $_REQUEST['scope'] );
		bboss()->current_component = '';

		$this->assertNotContains( $friends_post, $ids );
		$this->assertNotContains( $loggedin_post, $ids );
		$this->assertContains( $this->public_post, $ids );
	}
}
