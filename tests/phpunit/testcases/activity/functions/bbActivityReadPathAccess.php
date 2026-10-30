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
				'component'     => buddypress()->groups->id,
				'type'          => 'activity_update',
				'user_id'       => $this->author,
				'item_id'       => $this->group_id,
				'hide_sitewide' => true,
				'content'       => 'private group post',
			)
		);

		$this->comment = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $this->root,
				'content'     => 'private group comment',
			)
		);

		$this->public_post = self::factory()->activity->create(
			array(
				'component' => buddypress()->activity->id,
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
	 * Render a feed's items the way BP_Activity_Feed::output() does, without its die().
	 *
	 * The args match bp_activity_action_*_feed(); setup_properties() adds the stream default.
	 */
	protected function render_feed( $viewer_id, $displayed_id, $activity_args, $ungated = false ) {
		$this->set_current_user( $viewer_id );
		buddypress()->displayed_user->id = $displayed_id;

		$feed = ( new ReflectionClass( 'BP_Activity_Feed' ) )->newInstanceWithoutConstructor();
		foreach ( array( 'id' => 'personal', 'title' => 'Feed', 'link' => home_url(), 'description' => 'Feed', 'ttl' => 30, 'update_period' => 'hourly', 'update_frequency' => 2 ) as $key => $value ) {
			$feed->{$key} = $value;
		}
		$feed->activity_args = bp_parse_args(
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
		buddypress()->displayed_user->id = 0;

		return $xml;
	}

	/**
	 * The personal feed of bp_activity_action_personal_feed(): the 'feed' action is the scope.
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
	 * The mentions feed of bp_activity_action_mentions_feed() for $mentioned_id.
	 */
	protected function mentions_feed( $viewer_id, $mentioned_id, $ungated = false ) {
		return $this->render_feed(
			$viewer_id,
			$mentioned_id,
			array(
				// The feed searches '@username'; test users get a mention name that differs.
				'search_terms' => '@' . bp_activity_get_user_mentionname( $mentioned_id ),
				'scope'        => 'mentions',
			),
			$ungated
		);
	}

	public function test_mentions_feed_skips_private_group_comment() {
		$mentioned = $this->stranger;
		bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $this->root,
				'content'     => '@' . bp_activity_get_user_mentionname( $mentioned ) . ' mention in private group',
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
				'component' => buddypress()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'privacy'   => 'friends',
				'content'   => 'friends only post',
			)
		);
		bp_activity_new_comment(
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
				'component' => buddypress()->groups->id,
				'type'      => 'activity_update',
				'user_id'   => $this->author,
				'item_id'   => $group,
				'content'   => 'once public group post',
			)
		);
		bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $post,
				'content'     => 'comment from public days',
			)
		);

		groups_edit_group_settings( $group, false, 'private' );

		// The status change hides the group's rows but not the comment row.
		$this->assertStringContainsString( 'comment from public days', $this->personal_feed( 0, true ) );

		$this->assertStringNotContainsString( 'comment from public days', $this->personal_feed( 0 ) );
		$this->assertStringContainsString( 'comment from public days', $this->personal_feed( $this->member ) );
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
			bb_nouveau_ajax_activity_sync_from_modal();
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
		$request->set_param( 'url', bp_core_get_user_domain( $this->author ) . bp_get_activity_slug() . '/' . $activity_id . '/' );

		return rest_do_request( $request );
	}

	public function test_oembed_refuses_private_group_post_and_comment_to_guest() {
		if ( ! bp_is_active( 'activity', 'embeds' ) ) {
			$this->markTestSkipped( 'Activity embeds are off.' );
		}

		$this->assertSame( 404, $this->oembed( 0, $this->root )->get_status() );
		$this->assertSame( 404, $this->oembed( 0, $this->comment )->get_status() );
		$this->assertSame( 404, $this->oembed( $this->stranger, $this->comment )->get_status() );
	}

	public function test_oembed_accepts_public_post_and_member_view() {
		// Rendering the 200 response needs wp-embed.js, which the test install lacks, so ask
		// the URL validator that gates the route.
		$extension = ( new ReflectionClass( 'BP_Activity_oEmbed_Extension' ) )->newInstanceWithoutConstructor();
		$validate  = new ReflectionMethod( $extension, 'validate_url_to_item_id' );
		$validate->setAccessible( true );
		$url = function ( $activity_id ) {
			return bp_core_get_user_domain( $this->author ) . bp_get_activity_slug() . '/' . $activity_id . '/';
		};

		$this->set_current_user( 0 );
		$this->assertSame( $this->public_post, $validate->invoke( $extension, $url( $this->public_post ) ) );
		$this->assertFalse( $validate->invoke( $extension, $url( $this->comment ) ) );

		$this->set_current_user( $this->member );
		$this->assertSame( $this->comment, $validate->invoke( $extension, $url( $this->comment ) ) );
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

			// No component registered the scope: nothing to limit.
			$this->assertSame( array(), apply_filters( "bp_activity_set_{$scope}_scope_args", array(), array() ) );
		}
	}
}
