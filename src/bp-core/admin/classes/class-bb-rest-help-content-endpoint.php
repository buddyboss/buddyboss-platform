<?php
/**
 * BuddyBoss Help Content REST proxy endpoint.
 *
 * Server-side proxy for the BuddyBoss public Knowledge Base API on
 * `https://buddyboss.com`. The KB endpoint serves cached responses without an
 * `Access-Control-Allow-Origin` header — the CORS header is added at the PHP
 * layer but stripped by the LiteSpeed cache when a response is replayed from
 * cache. Direct cross-origin fetches from the admin React app are therefore
 * blocked by the browser even though the underlying CORS rule on
 * buddyboss.com allows them.
 *
 * This controller solves that by fetching the KB resource server-side via
 * `wp_remote_get()` and exposing the result on a same-origin REST route. The
 * React Help slider and the header Documentation modal both POST a path-only
 * URL fragment (e.g. `/wp-json/wp/v2/ht-kb/636101`) to
 * `/wp-json/buddyboss/v1/help-content/proxy` — same-origin, so CORS is not a
 * factor at all — and the controller prepends the buddyboss.com base before
 * fetching.
 *
 * Side benefits:
 *  - One outbound fetch per unique URL per site (transient-cached for 12h),
 *    not one per admin per click.
 *  - All buddyboss.com communication is observable in WP-CLI, debug.log,
 *    and Query Monitor.
 *  - If buddyboss.com later adds rate-limits, IP filters, or auth, only PHP
 *    needs to change.
 *  - Universal: same controller serves the per-feature Help slider AND the
 *    full Knowledge Base browsing experience (categories, category articles,
 *    article-by-slug) — every KB read flows through one auth-gated, cached,
 *    SSRF-safe seam.
 *
 * SSRF guardrails — the client never controls the egress host:
 *  - Clients pass a path-only URL fragment in the POST body.
 *  - The host (`https://buddyboss.com` by default) is server-controlled and
 *    only adjustable via the `bb_help_content_proxy_base` filter.
 *  - The client-supplied path is validated (must start with one `/`, no
 *    `..` segments, no control chars, no fragment, length-bounded) before
 *    concat to defend against protocol-relative tricks
 *    (e.g. `//evil.com/...`) and request-splitting.
 *  - After concat, `wp_http_validate_url()` runs as a final sanity check.
 *
 * Backward-compat: the legacy `GET /help-content/<id>` route is kept
 * alongside the new POST endpoint so any browser still running the previous
 * bundle does not break during deploy. Both routes share one fetch+cache
 * helper internally.
 *
 * @package BuddyBoss\Core\Administration
 *
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_REST_Help_Content_Endpoint
 *
 * Read-only proxy for buddyboss.com Knowledge Base endpoints.
 *
 * @since BuddyBoss 3.0.0
 */
class BB_REST_Help_Content_Endpoint extends WP_REST_Controller {

	/**
	 * Default upstream base URL.
	 *
	 * Hard-coded so the host is never derived from client input. Filterable
	 * via `bb_help_content_proxy_base` for staging/self-hosted KB mirrors.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var string
	 */
	const DEFAULT_PROXY_BASE = 'https://buddyboss.com';

	/**
	 * Transient cache TTL for proxied responses.
	 *
	 * 12 hours strikes a balance: KB articles change rarely, admins re-open
	 * the same Help slider in bursts (working through a panel, then leaving),
	 * and the upstream LiteSpeed cache is itself ~1h — TTLing longer than
	 * upstream is fine because the body content doesn't change.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var int Seconds.
	 */
	const TRANSIENT_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Prefix for the per-resource transient key.
	 *
	 * Both legacy per-id transients (`bb_help_content_<id>`) and new
	 * per-URL md5 transients (`bb_help_content_<md5>`) share this prefix
	 * so the existing `bb_clear_help_content_transients()` flush sweeps
	 * both layouts in one query.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'bb_help_content_';

	/**
	 * Maximum length of a client-supplied URL path.
	 *
	 * Generous enough for `?ht-kb-category[]=…` filter strings with dozens
	 * of IDs (the Documentation modal can pass entire descendant trees) but
	 * tight enough that pathological inputs never reach `wp_remote_get()`.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @var int
	 */
	const MAX_PATH_LENGTH = 2048;

	/**
	 * Constructor.
	 *
	 * Uses the platform-api namespace when available so the route lives at
	 * `buddyboss/v1/help-content/...` — consistent with the rest of the
	 * BuddyBoss REST surface. Falls back to a private `bb/v1` namespace
	 * when platform-api is not loaded so the admin Help slider still works
	 * on platform-only installs.
	 *
	 * @since BuddyBoss 3.0.0
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
	 * Register the proxy route.
	 *
	 * Single endpoint: POST /help-content/proxy. Both the per-feature Help
	 * slider AND the header Documentation modal POST a path-only URL
	 * fragment to it; the controller validates the path, prepends the
	 * server-controlled `https://buddyboss.com` base, fetches with a 12h
	 * transient cache, and returns `{ body, headers, status }`.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/proxy',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'proxy_request' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'url' => array(
							'description'       => __( 'Path-only URL fragment under buddyboss.com (e.g. /wp-json/wp/v2/ht-kb/123).', 'buddyboss-platform' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'wp_unslash',
						),
					),
				),
				'schema' => array( $this, 'get_proxy_response_schema' ),
			)
		);
	}

	/**
	 * Permission gate.
	 *
	 * The Help slider and Documentation modal are rendered only in the
	 * WP-admin Settings 2.0 React app, so we gate on `manage_options` —
	 * the same capability that already protects every Settings 2.0 AJAX
	 * endpoint (see BB_Admin_Settings_Ajax::bb_verify_request).
	 *
	 * Note on auth: REST requests are considered authenticated only when
	 * the WP-Admin cookie is presented AND the `X-WP-Nonce: wp_rest`
	 * header validates. The React clients (api.js, kbApi.js) include both
	 * via `credentials: 'same-origin'` + `bbAdminData.nonce`. Anonymous
	 * callers fail the capability check and get
	 * `rest_authorization_required_code()` (401 if logged-out, 403 if
	 * logged-in-but-not-admin).
	 *
	 * @since BuddyBoss 3.0.0
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
				__( 'Sorry, you are not allowed to access help content.', 'buddyboss-platform' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Universal proxy handler.
	 *
	 * Validates the client-supplied path, builds the upstream URL by
	 * prepending the server-controlled base, fetches once per URL with a
	 * 12h transient cache, and returns the upstream JSON wrapped in a
	 * `{ body, headers, status }` envelope. The `headers` sidecar exposes
	 * `x-wp-total` and `x-wp-totalpages` so the React kbApi pagination
	 * loop can read them without another network round-trip.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param WP_REST_Request $request Current REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function proxy_request( $request ) {
		$raw_path = $request->get_param( 'url' );
		$path     = $this->validate_proxy_path( $raw_path );
		if ( is_wp_error( $path ) ) {
			return $path;
		}

		$target = $this->build_proxy_target( $path );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$transient_key = self::TRANSIENT_PREFIX . md5( $target );
		$cached        = get_transient( $transient_key );
		if ( false !== $cached && is_array( $cached ) && isset( $cached['body'] ) ) {
			return rest_ensure_response( $cached );
		}

		$envelope = $this->fetch_remote( $target );
		if ( is_wp_error( $envelope ) ) {
			return $envelope;
		}

		set_transient( $transient_key, $envelope, self::TRANSIENT_TTL );

		return rest_ensure_response( $envelope );
	}

	/**
	 * Validate the client-supplied path fragment.
	 *
	 * The path must:
	 *  - Be a non-empty string.
	 *  - Be ≤ MAX_PATH_LENGTH chars.
	 *  - Start with exactly ONE forward slash. Two leading slashes (`//x`)
	 *    are rejected because after concat with the `https://buddyboss.com`
	 *    base the result becomes `https://buddyboss.com//x` — but more
	 *    importantly, browsers and PHP URL parsers can interpret protocol-
	 *    relative network-path references inconsistently, so the simplest
	 *    safe rule is "exactly one leading slash".
	 *  - Contain no `..` segments — defends against directory traversal in
	 *    rewrite-driven upstream routing.
	 *  - Contain no control characters (\r, \n, \0, etc.) — defends against
	 *    HTTP request splitting.
	 *  - Contain no `#` fragment — fragments are client-side only and
	 *    have no meaning in `wp_remote_get()`, so accepting them would be
	 *    a footgun (they're stripped by HTTP libraries anyway).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param mixed $raw Client-provided value.
	 *
	 * @return string|WP_Error Validated path on success.
	 */
	protected function validate_proxy_path( $raw ) {
		if ( ! is_string( $raw ) || '' === $raw ) {
			return $this->path_rejected_error();
		}
		if ( strlen( $raw ) > self::MAX_PATH_LENGTH ) {
			return $this->path_rejected_error();
		}
		if ( '/' !== substr( $raw, 0, 1 ) ) {
			return $this->path_rejected_error();
		}
		if ( '/' === substr( $raw, 1, 1 ) ) {
			return $this->path_rejected_error();
		}
		// Reject control characters anywhere in the path. Range covers
		// 0x00..0x1F plus 0x7F (DEL). `[:cntrl:]` would also work; the
		// explicit class is portable across PCRE versions.
		if ( preg_match( '/[\x00-\x1F\x7F]/', $raw ) ) {
			return $this->path_rejected_error();
		}
		// Reject `..` segments. Match `..` only when bordered by start,
		// `/`, or end so a query string containing `..` (which won't
		// happen in WP REST URLs but is technically valid for opaque
		// values) is not falsely rejected.
		if ( preg_match( '#(^|/)\.\.(/|$)#', $raw ) ) {
			return $this->path_rejected_error();
		}
		if ( false !== strpos( $raw, '#' ) ) {
			return $this->path_rejected_error();
		}
		return $raw;
	}

	/**
	 * Build the full upstream URL from a validated path fragment.
	 *
	 * Concatenates the server-controlled base (filterable, but never
	 * client-controlled) with the validated path and runs
	 * `wp_http_validate_url()` as a final sanity check. The base is
	 * trailing-slash-stripped before concat so the result is well-formed
	 * regardless of how the filter caller wrote it.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $path Validated path fragment (starts with exactly one slash).
	 *
	 * @return string|WP_Error Full upstream URL or rejection error.
	 */
	protected function build_proxy_target( $path ) {
		/**
		 * Filter the upstream proxy base URL.
		 *
		 * Lets a site point at a staging mirror or self-hosted KB clone
		 * without forking this controller. The base is server-side only —
		 * it is NEVER influenced by client input. Filter callers should
		 * return an absolute https URL with no trailing slash.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $base Default upstream base URL.
		 */
		$base = apply_filters( 'bb_help_content_proxy_base', self::DEFAULT_PROXY_BASE );
		$base = is_string( $base ) && '' !== $base ? rtrim( $base, '/' ) : self::DEFAULT_PROXY_BASE;

		$target = $base . $path;

		$validated = wp_http_validate_url( $target );
		if ( false === $validated ) {
			return $this->path_rejected_error();
		}

		return $validated;
	}

	/**
	 * Standard error response for any path validation failure.
	 *
	 * Returns the same generic message regardless of which check failed
	 * — we don't help an attacker enumerate the rules. Logged-out admins
	 * never reach this code path because the permission gate runs first.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return WP_Error
	 */
	protected function path_rejected_error() {
		return new WP_Error(
			'bb_rest_help_content_url_not_allowed',
			__( 'The requested help content URL is not allowed.', 'buddyboss-platform' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Fetch the remote URL and pack the response into a proxy envelope.
	 *
	 * Returns the upstream body verbatim (decoded to a PHP array if it was
	 * JSON, otherwise the raw string) so React consumers can read whatever
	 * shape the upstream returns. The `headers` sidecar exposes only the
	 * pagination headers needed by kbApi.js — exposing the full response
	 * header set would leak server identification, cookies, and cache hints
	 * with no consumer benefit.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $target Full upstream URL.
	 *
	 * @return array|WP_Error Envelope `{body, headers, status}` or fetch error.
	 */
	protected function fetch_remote( $target ) {
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
		 * Filter the wp_remote_get() args used to fetch a help-content URL.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array  $args   Request args.
		 * @param string $target Full upstream URL.
		 */
		$args = apply_filters( 'bb_help_content_request_args', $args, $target );

		$response = wp_remote_get( $target, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'bb_rest_help_content_upstream_error',
				/* translators: %s: error message from wp_remote_get */
				sprintf( __( 'Failed to load help content: %s', 'buddyboss-platform' ), $response->get_error_message() ),
				array( 'status' => 502 )
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( 404 === $status ) {
			return new WP_Error(
				'bb_rest_help_content_not_found',
				__( 'Help content not found.', 'buddyboss-platform' ),
				array( 'status' => 404 )
			);
		}
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'bb_rest_help_content_upstream_status',
				/* translators: %d: HTTP status code returned by upstream */
				sprintf( __( 'Help content service returned an unexpected status (%d).', 'buddyboss-platform' ), $status ),
				array( 'status' => 502 )
			);
		}

		$raw_body = wp_remote_retrieve_body( $response );

		// Try JSON first; fall back to raw string. KB endpoints we care
		// about always return JSON, but keeping a string fallback means
		// future non-JSON resources (e.g. `.txt` changelog) work without
		// changes here.
		$decoded = json_decode( $raw_body, true );
		$body    = is_array( $decoded ) ? $decoded : $raw_body;

		// Surface only the pagination headers — kbApi.js reads them to
		// decide whether to issue another page. Exposing the full header
		// set would leak server-software banners and caching hints with
		// zero consumer benefit.
		$headers       = array();
		$total_pages   = wp_remote_retrieve_header( $response, 'x-wp-totalpages' );
		$total_results = wp_remote_retrieve_header( $response, 'x-wp-total' );
		if ( '' !== $total_pages ) {
			$headers['x-wp-totalpages'] = (string) $total_pages;
		}
		if ( '' !== $total_results ) {
			$headers['x-wp-total'] = (string) $total_results;
		}

		return array(
			'body'    => $body,
			'headers' => $headers,
			'status'  => $status,
		);
	}

	/**
	 * Schema for the proxy envelope response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array
	 */
	public function get_proxy_response_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bb_help_content_proxy',
			'type'       => 'object',
			'properties' => array(
				'body'    => array(
					'description' => __( 'Upstream response body, JSON-decoded when possible.', 'buddyboss-platform' ),
					'type'        => array( 'object', 'array', 'string' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'headers' => array(
					'description' => __( 'Selected pagination headers from the upstream response.', 'buddyboss-platform' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'status'  => array(
					'description' => __( 'Upstream HTTP status code.', 'buddyboss-platform' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}

}
