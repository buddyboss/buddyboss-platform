<?php
/**
 * Tests for bb_core_wordfence_rest_author_scan_compatibility().
 *
 * The filter drops the `author` query var for BuddyBoss REST requests so
 * Wordfence's author-scan block does not 404 them, and must leave every other
 * request (front-end `?author=N`, core and third-party REST routes) untouched.
 *
 * @group core
 * @group compatibility
 */
class BP_Tests_Core_WordfenceAuthorScanCompat extends BP_UnitTestCase {

	/**
	 * BuddyBoss and BuddyBoss App REST routes lose the `author` query var.
	 *
	 * @dataProvider data_exempt_routes
	 *
	 * @param string $rest_route REST route query var.
	 */
	public function test_drops_author_for_buddyboss_rest_routes( $rest_route ) {
		$query_vars = bb_core_wordfence_rest_author_scan_compatibility(
			array(
				'rest_route' => $rest_route,
				'author'     => '84',
			)
		);

		$this->assertArrayNotHasKey( 'author', $query_vars );
		$this->assertSame( $rest_route, $query_vars['rest_route'] );
	}

	/**
	 * Routes exempted from the author-scan block.
	 *
	 * @return array
	 */
	public function data_exempt_routes() {
		return array(
			'buddyboss pretty'    => array( '/buddyboss/v1/topics' ),
			'buddyboss plain'     => array( 'buddyboss/v1/topics' ),
			'buddyboss-app route' => array( '/buddyboss-app/learndash/v1/courses' ),
		);
	}

	/**
	 * Every other request keeps `author`, so Wordfence still blocks it.
	 *
	 * @dataProvider data_untouched_query_vars
	 *
	 * @param array $query_vars Incoming query vars.
	 */
	public function test_keeps_author_for_other_requests( $query_vars ) {
		$this->assertSame( $query_vars, bb_core_wordfence_rest_author_scan_compatibility( $query_vars ) );
	}

	/**
	 * Requests the filter must not change.
	 *
	 * @return array
	 */
	public function data_untouched_query_vars() {
		return array(
			'front-end author scan'   => array( array( 'author' => '84' ) ),
			'core REST route'         => array(
				array(
					'rest_route' => '/wp/v2/posts',
					'author'     => '1',
				),
			),
			'look-alike namespace'    => array(
				array(
					'rest_route' => '/buddyboss-apps/x',
					'author'     => '84',
				),
			),
			'namespace without slash' => array(
				array(
					'rest_route' => '/buddyboss',
					'author'     => '84',
				),
			),
			'no author'               => array( array( 'rest_route' => '/buddyboss/v1/topics' ) ),
		);
	}

	/**
	 * An array `rest_route` (`?rest_route[]=…`) is ignored instead of fataling.
	 */
	public function test_array_rest_route_does_not_fatal() {
		$query_vars = array(
			'rest_route' => array( '/buddyboss/v1/topics' ),
			'author'     => '84',
		);

		$this->assertSame( $query_vars, bb_core_wordfence_rest_author_scan_compatibility( $query_vars ) );
	}
}
