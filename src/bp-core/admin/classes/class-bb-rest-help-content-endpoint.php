<?php
/**
 * BuddyBoss Help Content REST proxy endpoint.
 *
 * Server-side proxy for the BuddyBoss public Knowledge Base API
 * (`https://buddyboss.com/wp-json/wp/v2/ht-kb/<id>`). The KB endpoint serves
 * cached responses without an `Access-Control-Allow-Origin` header — the CORS
 * header is added at the PHP layer but stripped by the LiteSpeed cache when
 * a response is replayed from cache. Direct cross-origin fetches from the
 * admin React app are therefore blocked by the browser even though the
 * underlying CORS rule on buddyboss.com allows them.
 *
 * This controller solves that by fetching the KB article server-side via
 * `wp_remote_get()` and exposing the result on a same-origin REST route.
 * The React Help slider hits `/wp-json/buddyboss/v1/help-content/<id>` —
 * a same-origin call — so CORS is not a factor at all.
 *
 * Side benefits:
 *  - One outbound fetch per article per site (transient-cached for 12h),
 *    not one per admin per click.
 *  - All buddyboss.com communication is observable in WP-CLI, debug.log,
 *    and Query Monitor.
 *  - If buddyboss.com later adds rate-limits, IP filters, or auth, only
 *    PHP needs to change.
 *
 * @package BuddyBoss\Core\Administration
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_REST_Help_Content_Endpoint
 *
 * Read-only proxy for `wp/v2/ht-kb/<id>` on buddyboss.com.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_REST_Help_Content_Endpoint extends WP_REST_Controller {

	/**
	 * Upstream KB endpoint base URL.
	 *
	 * Filterable via `bb_help_content_upstream_base` so a site can point at a
	 * staging mirror or a self-hosted KB without forking this controller.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const DEFAULT_UPSTREAM = 'https://buddyboss.com/wp-json/wp/v2/ht-kb';

	/**
	 * Transient cache TTL for proxied responses.
	 *
	 * 12 hours strikes a balance: KB articles change rarely, admins re-open
	 * the same Help slider in bursts (working through a panel, then leaving),
	 * and the upstream LiteSpeed cache is itself ~1h — TTLing longer than
	 * upstream is fine because the body content doesn't change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int Seconds.
	 */
	const TRANSIENT_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Prefix for the per-article transient key.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'bb_help_content_';

	/**
	 * Constructor.
	 *
	 * Uses the platform-api namespace when available so the route lives at
	 * `buddyboss/v1/help-content/<id>` — consistent with the rest of the
	 * BuddyBoss REST surface. Falls back to a private `bb/v1` namespace
	 * when platform-api is not loaded so the admin Help slider still works
	 * on platform-only installs.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		if ( function_exists( 'bp_rest_namespace' ) && function_exists( 'bp_rest_version' ) ) {
			$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		} else {
			$this->namespace = 'bb/v1';
		}
		$this->rest_base = 'help-content';
	}

	/**
	 * Register the route.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'BuddyBoss KB article ID.', 'buddyboss' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
							'minimum'           => 1,
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Permission gate.
	 *
	 * The Help slider is rendered only in the WP-admin Settings 2.0 React
	 * app, so we gate on `manage_options` — the same capability that
	 * already protects every Settings 2.0 AJAX endpoint
	 * (see BB_Admin_Settings_Ajax::bb_verify_request).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param WP_REST_Request $request Current REST request.
	 *
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		unset( $request );
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'bb_rest_help_content_forbidden',
				__( 'Sorry, you are not allowed to access help content.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Fetch and return a help article.
	 *
	 * Hits the per-site transient cache first; on miss, calls
	 * `wp_remote_get()` against the upstream KB endpoint and writes the
	 * normalized payload back to the transient.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param WP_REST_Request $request Current REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$article_id = (int) $request->get_param( 'id' );
		if ( $article_id <= 0 ) {
			return new WP_Error(
				'bb_rest_help_content_invalid_id',
				__( 'Invalid help article ID.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		$transient_key = self::TRANSIENT_PREFIX . $article_id;
		$cached        = get_transient( $transient_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return rest_ensure_response( $cached );
		}

		$payload = $this->fetch_upstream( $article_id );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		set_transient( $transient_key, $payload, self::TRANSIENT_TTL );

		return rest_ensure_response( $payload );
	}

	/**
	 * Fetch a single article from the upstream KB endpoint and normalize the body.
	 *
	 * Returns the same shape the React `fetchHelpContent()` already consumes
	 * (`{ id, title, content, videoId, imageUrl }`). Network errors become
	 * `WP_Error` with HTTP status code preserved so the React layer's existing
	 * error branch still works without changes.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $article_id Validated KB article ID.
	 *
	 * @return array|WP_Error Normalized payload or upstream error.
	 */
	protected function fetch_upstream( $article_id ) {
		/**
		 * Filter the upstream KB base URL.
		 *
		 * Lets a site point at a staging mirror or self-hosted KB clone
		 * without forking this controller. Trailing slash is stripped before
		 * appending the article ID.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $base       Default upstream base URL.
		 * @param int    $article_id Article being fetched.
		 */
		$base = apply_filters( 'bb_help_content_upstream_base', self::DEFAULT_UPSTREAM, $article_id );
		$base = is_string( $base ) ? rtrim( $base, '/' ) : self::DEFAULT_UPSTREAM;
		$url  = $base . '/' . $article_id;

		$args = array(
			'timeout'     => 8,
			'redirection' => 3,
			// Identify ourselves so buddyboss.com can debug bad requests.
			'user-agent'  => 'BuddyBoss-Platform-Help-Proxy/' . ( defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0' ) . '; ' . home_url(),
			'headers'     => array(
				'Accept' => 'application/json',
			),
		);

		/**
		 * Filter the wp_remote_get() args used to fetch a help article.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $args       Request args.
		 * @param int    $article_id Article being fetched.
		 * @param string $url        Full upstream URL.
		 */
		$args = apply_filters( 'bb_help_content_request_args', $args, $article_id, $url );

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'bb_rest_help_content_upstream_error',
				/* translators: %s: error message from wp_remote_get */
				sprintf( __( 'Failed to load help content: %s', 'buddyboss' ), $response->get_error_message() ),
				array( 'status' => 502 )
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( 404 === $status ) {
			return new WP_Error(
				'bb_rest_help_content_not_found',
				__( 'Help content not found.', 'buddyboss' ),
				array( 'status' => 404 )
			);
		}
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'bb_rest_help_content_upstream_status',
				/* translators: %d: HTTP status code returned by upstream */
				sprintf( __( 'Help content service returned an unexpected status (%d).', 'buddyboss' ), $status ),
				array( 'status' => 502 )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'bb_rest_help_content_upstream_malformed',
				__( 'Help content service returned an unexpected payload.', 'buddyboss' ),
				array( 'status' => 502 )
			);
		}

		return $this->normalize_upstream_payload( $data );
	}

	/**
	 * Map the upstream `wp/v2/ht-kb` envelope onto our wire shape.
	 *
	 * The upstream envelope nests title/content under `.rendered` and stores
	 * the optional video ID + featured image inside an `acf` block. We
	 * flatten that into `{ id, title, content, videoId, imageUrl }` so the
	 * React side consumes one stable shape regardless of upstream schema
	 * changes.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $data Decoded upstream JSON.
	 *
	 * @return array
	 */
	protected function normalize_upstream_payload( $data ) {
		$title = '';
		if ( isset( $data['title']['rendered'] ) && is_string( $data['title']['rendered'] ) ) {
			$title = $data['title']['rendered'];
		}

		$content = '';
		if ( isset( $data['content']['rendered'] ) && is_string( $data['content']['rendered'] ) ) {
			$content = $data['content']['rendered'];
		}

		$video_id = '';
		if ( isset( $data['acf']['video_id'] ) && is_string( $data['acf']['video_id'] ) ) {
			$video_id = $data['acf']['video_id'];
		}

		$image_url = '';
		if ( isset( $data['acf']['featured_image'] ) && is_string( $data['acf']['featured_image'] ) ) {
			$image_url = $data['acf']['featured_image'];
		}

		return array(
			'id'       => isset( $data['id'] ) ? (int) $data['id'] : 0,
			'title'    => $title,
			'content'  => $content,
			'videoId'  => $video_id,
			'imageUrl' => $image_url,
		);
	}

	/**
	 * Schema for a help article payload.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bb_help_content',
			'type'       => 'object',
			'properties' => array(
				'id'       => array(
					'description' => __( 'Upstream KB article ID.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'title'    => array(
					'description' => __( 'Article title (HTML-rendered).', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'content'  => array(
					'description' => __( 'Article body HTML — sanitize before rendering.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'videoId'  => array(
					'description' => __( 'Optional YouTube video ID associated with the article.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'imageUrl' => array(
					'description' => __( 'Optional featured image URL.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $this->schema );
	}
}
