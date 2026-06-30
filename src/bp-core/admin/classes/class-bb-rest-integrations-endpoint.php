<?php
/**
 * BuddyBoss Integrations REST proxy endpoint.
 *
 * Server-side proxy for the BuddyBoss public Integrations directory on
 * `https://buddyboss.com` (`wp/v2/integrations`, `wp/v2/integrations_category`,
 * `wp/v2/integrations_collection`). The upstream serves cached responses without
 * an `Access-Control-Allow-Origin` header — the CORS header is added at the PHP
 * layer but stripped by the LiteSpeed cache when a response is replayed from
 * cache — so direct cross-origin fetches from the admin React app are blocked by
 * the browser. This is the same constraint the Knowledge Base proxy solved (see
 * BB_REST_Help_Content_Endpoint); the Integrations marketplace reuses the same
 * proven shape with its own cache namespace, filters, and error codes so the two
 * features stay independently flushable.
 *
 * The React Integrations screen POSTs a path-only URL fragment (e.g.
 * `/wp-json/wp/v2/integrations?page=2&search=crm`) to
 * `/wp-json/buddyboss/v1/integrations/proxy` — same-origin, so CORS is not a
 * factor — and this controller prepends the buddyboss.com base before fetching.
 *
 * SSRF guardrails — the client never controls the egress host:
 *  - Clients pass a path-only URL fragment in the POST body.
 *  - The host (`https://buddyboss.com` by default) is server-controlled and only
 *    adjustable via the `bb_integrations_proxy_base` filter.
 *  - The client-supplied path is validated (must start with one `/`, no `..`
 *    segments, no control chars, no fragment, length-bounded) before concat to
 *    defend against protocol-relative tricks (e.g. `//evil.com/...`) and request
 *    splitting.
 *  - After concat, `wp_http_validate_url()` runs as a final sanity check.
 *
 * @package BuddyBoss\Core\Administration
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_REST_Integrations_Endpoint
 *
 * Read-only proxy for buddyboss.com Integrations directory endpoints.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_REST_Integrations_Endpoint extends WP_REST_Controller {

	/**
	 * Default upstream base URL.
	 *
	 * Hard-coded so the host is never derived from client input. Filterable via
	 * `bb_integrations_proxy_base` for staging/self-hosted mirrors.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const DEFAULT_PROXY_BASE = 'https://buddyboss.com';

	/**
	 * Transient cache TTL for proxied responses.
	 *
	 * 12 hours: the integrations directory changes rarely and admins browse it
	 * in bursts. Matches the Knowledge Base proxy so cache behaviour is uniform
	 * across the two buddyboss.com seams.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int Seconds.
	 */
	const TRANSIENT_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Prefix for the per-URL transient key.
	 *
	 * Distinct from the help-content prefix so an integrations cache flush never
	 * sweeps KB caches and vice-versa.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'bb_integrations_';

	/**
	 * Maximum length of a client-supplied URL path.
	 *
	 * Generous enough for `?integrations_category=…` filter strings but tight
	 * enough that pathological inputs never reach `wp_remote_get()`.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	const MAX_PATH_LENGTH = 2048;

	/**
	 * Transient cache TTL for upstream failures (negative cache).
	 *
	 * Short so a transient upstream hiccup self-heals quickly, but long enough
	 * that a persistently slow/broken upstream isn't re-fetched (8s timeout) on
	 * every admin page load while it's down.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int Seconds.
	 */
	const TRANSIENT_NEGATIVE_TTL = MINUTE_IN_SECONDS;

	/**
	 * Transient cache TTL for free-text `search=` queries.
	 *
	 * Search strings are volatile and effectively unbounded in key-space (one
	 * wp_options row per unique query), so they get a short TTL while the
	 * unfiltered list/category responses keep the long {@see self::TRANSIENT_TTL}.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int Seconds.
	 */
	const TRANSIENT_SEARCH_TTL = 10 * MINUTE_IN_SECONDS;

	/**
	 * How long a stale cached response survives past its soft-freshness window.
	 *
	 * Stored data outlives its `_fresh_until` mark by this much so a single
	 * request can revalidate while the rest serve the stale copy
	 * (stale-while-revalidate) instead of all hammering the slow upstream.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int Seconds.
	 */
	const STALE_GRACE = HOUR_IN_SECONDS;

	/**
	 * Refresh-lock TTL (thundering-herd guard).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int Seconds.
	 */
	const LOCK_TTL = 30;

	/**
	 * Maximum upstream body size accepted/cached, in bytes.
	 *
	 * Caps `wp_remote_get()` and is re-checked on the returned body so a
	 * pathological upstream cannot balloon a 12h transient.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	const MAX_RESPONSE_BYTES = 2 * MB_IN_BYTES;

	/**
	 * Constructor.
	 *
	 * Uses the platform-api namespace when available so the route lives at
	 * `buddyboss/v1/integrations/...` — consistent with the rest of the
	 * BuddyBoss REST surface and with the value `bb-admin-settings-page.php`
	 * localizes into `bbAdminData.apiUrl` (`rest_url( 'buddyboss/v1/' )`).
	 * Falls back to `buddyboss/v1` when the helpers are absent — matching the
	 * fallback in bb-admin-integrations-page.php so the registered route and the
	 * URL the React app calls never diverge.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		if ( function_exists( 'bp_rest_namespace' ) && function_exists( 'bp_rest_version' ) ) {
			$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		} else {
			$this->namespace = 'buddyboss/v1';
		}
		$this->rest_base = 'integrations';
	}

	/**
	 * Register the proxy route.
	 *
	 * Single endpoint: POST /integrations/proxy. The React screen POSTs a
	 * path-only URL fragment; the controller validates it, prepends the
	 * server-controlled `https://buddyboss.com` base, fetches with a 12h
	 * transient cache, and returns `{ body, headers, status }`.
	 *
	 * @since BuddyBoss [BBVERSION]
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
							'description'       => __( 'Path-only URL fragment under buddyboss.com (e.g. /wp-json/wp/v2/integrations).', 'buddyboss' ),
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
	 * The Integrations screen is rendered only in the WP-admin React app, so we
	 * gate on `manage_options` — the same capability that protects every
	 * Settings 2.0 endpoint. Anonymous callers fail the check and get
	 * `rest_authorization_required_code()` (401 logged-out, 403 logged-in
	 * non-admin).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param WP_REST_Request $request Current REST request.
	 *
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		// $request is unused — the capability gate is global, not per-item.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'bb_rest_integrations_forbidden',
				__( 'Sorry, you are not allowed to access integrations.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Universal proxy handler.
	 *
	 * Validates the client-supplied path, builds the upstream URL by prepending
	 * the server-controlled base, fetches once per URL with a 12h transient
	 * cache, and returns the upstream JSON wrapped in a `{ body, headers, status }`
	 * envelope. The `headers` sidecar exposes `x-wp-total` and `x-wp-totalpages`
	 * so the React listing pagination can read them without another round-trip.
	 *
	 * @since BuddyBoss [BBVERSION]
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
		$lock_key      = $transient_key . '_lock';
		// Volatile search queries get a short TTL; everything else the long one.
		$ttl = ( false !== strpos( $target, 'search=' ) ) ? self::TRANSIENT_SEARCH_TTL : self::TRANSIENT_TTL;

		$cached = get_transient( $transient_key );
		if ( is_array( $cached ) ) {
			// Replay a recently-cached upstream failure without re-hitting the
			// slow/broken upstream (negative cache, short TTL).
			if ( isset( $cached['error'] ) && is_array( $cached['error'] ) ) {
				return new WP_Error(
					$cached['error']['code'],
					$cached['error']['message'],
					array( 'status' => $cached['error']['status'] )
				);
			}
			if ( isset( $cached['body'] ) ) {
				$is_fresh = isset( $cached['_fresh_until'] ) && time() < (int) $cached['_fresh_until'];
				// Fresh → serve. Stale → also serve, unless we win the refresh
				// lock (stale-while-revalidate: one request rebuilds while the
				// rest keep serving the old copy instead of all firing the 8s
				// upstream fetch at once).
				if ( $is_fresh || false !== get_transient( $lock_key ) ) {
					return rest_ensure_response( $cached );
				}
				set_transient( $lock_key, 1, self::LOCK_TTL );
			}
		}

		$envelope = $this->fetch_remote( $target );
		if ( is_wp_error( $envelope ) ) {
			delete_transient( $lock_key );
			// Ride out a transient upstream blip on the previous good copy.
			if ( is_array( $cached ) && isset( $cached['body'] ) ) {
				return rest_ensure_response( $cached );
			}
			$error_data = $envelope->get_error_data();
			set_transient(
				$transient_key,
				array(
					'error' => array(
						'code'    => $envelope->get_error_code(),
						'message' => $envelope->get_error_message(),
						'status'  => ( is_array( $error_data ) && isset( $error_data['status'] ) ) ? (int) $error_data['status'] : 502,
					),
				),
				self::TRANSIENT_NEGATIVE_TTL
			);
			return $envelope;
		}

		// Soft-freshness mark; the hard TTL outlasts it so stale data survives
		// long enough for a single revalidation pass.
		$envelope['_fresh_until'] = time() + $ttl;
		set_transient( $transient_key, $envelope, $ttl + self::STALE_GRACE );
		delete_transient( $lock_key );

		return rest_ensure_response( $envelope );
	}

	/**
	 * Validate the client-supplied path fragment.
	 *
	 * The path must: be a non-empty string ≤ MAX_PATH_LENGTH; start with exactly
	 * ONE forward slash (two leading slashes are rejected to defeat protocol-
	 * relative `//evil.com` references); contain no `..` segments (directory
	 * traversal); no control characters (HTTP request splitting); no `#`
	 * fragment (meaningless to wp_remote_get and a footgun); and match one of the
	 * allowed path prefixes (the public Integrations directory endpoints) so an
	 * admin cannot turn the proxy into a general-purpose buddyboss.com fetcher.
	 *
	 * @since BuddyBoss [BBVERSION]
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
		// Reject control characters anywhere in the path (0x00..0x1F + 0x7F DEL).
		if ( preg_match( '/[\x00-\x1F\x7F]/', $raw ) ) {
			return $this->path_rejected_error();
		}
		// Reject `..` segments bordered by start, `/`, or end.
		if ( preg_match( '#(^|/)\.\.(/|$)#', $raw ) ) {
			return $this->path_rejected_error();
		}
		if ( false !== strpos( $raw, '#' ) ) {
			return $this->path_rejected_error();
		}

		/**
		 * Filter the allowed upstream path prefixes for the integrations proxy.
		 *
		 * Each entry is matched against the start of the client-supplied path.
		 * The defaults cover the public Integrations directory surface
		 * (`integrations`, `integrations_category`, `integrations_collection`),
		 * all of which share the `/wp-json/wp/v2/integrations` prefix. Anything
		 * outside the allowlist is rejected so the proxy cannot be used to fetch
		 * arbitrary buddyboss.com URLs.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string[] $prefixes Allowed path prefixes.
		 */
		$allowed_prefixes = apply_filters(
			'bb_integrations_allowed_path_prefixes',
			array( '/wp-json/wp/v2/integrations' )
		);

		$allowed = false;
		foreach ( (array) $allowed_prefixes as $prefix ) {
			if ( is_string( $prefix ) && '' !== $prefix && 0 === strpos( $raw, $prefix ) ) {
				// Require a path-segment boundary after the prefix so
				// `/wp-json/wp/v2/integrationsEVIL` can't ride in on a substring
				// match — the next char must end the path or start the query.
				$next = substr( $raw, strlen( $prefix ), 1 );
				if ( '' === $next || '/' === $next || '?' === $next ) {
					$allowed = true;
					break;
				}
			}
		}
		if ( ! $allowed ) {
			return $this->path_rejected_error();
		}

		return $raw;
	}

	/**
	 * Build the full upstream URL from a validated path fragment.
	 *
	 * Concatenates the server-controlled base (filterable, never client-
	 * controlled) with the validated path and runs `wp_http_validate_url()` as a
	 * final sanity check.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $path Validated path fragment (starts with exactly one slash).
	 *
	 * @return string|WP_Error Full upstream URL or rejection error.
	 */
	protected function build_proxy_target( $path ) {
		/**
		 * Filter the upstream proxy base URL.
		 *
		 * Lets a site point at a staging mirror without forking this controller.
		 * The base is server-side only — NEVER influenced by client input.
		 * Return an absolute https URL with no trailing slash.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $base Default upstream base URL.
		 */
		$base = apply_filters( 'bb_integrations_proxy_base', self::DEFAULT_PROXY_BASE );
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
	 * Returns the same generic message regardless of which check failed so an
	 * attacker cannot enumerate the rules.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return WP_Error
	 */
	protected function path_rejected_error() {
		return new WP_Error(
			'bb_rest_integrations_url_not_allowed',
			__( 'The requested integrations URL is not allowed.', 'buddyboss' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Fetch the remote URL and pack the response into a proxy envelope.
	 *
	 * Returns the upstream body verbatim (JSON-decoded to an array when possible)
	 * plus only the pagination headers the listing needs.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $target Full upstream URL.
	 *
	 * @return array|WP_Error Envelope `{body, headers, status}` or fetch error.
	 */
	protected function fetch_remote( $target ) {
		$args = array(
			'timeout'             => 8,
			// No redirect-follow: the upstream REST endpoints answer 200 directly,
			// so a 30x Location could only steer the server-side fetch somewhere
			// unintended. Paired with reject_unsafe_urls this closes the SSRF vector.
			'redirection'         => 0,
			'reject_unsafe_urls'  => true,
			// Cap the body so a pathological/compromised upstream can't balloon the
			// cached transient (re-checked on the returned body below).
			'limit_response_size' => self::MAX_RESPONSE_BYTES,
			'user-agent'          => 'BuddyBoss-Platform-Integrations-Proxy/' . ( defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0' ) . '; ' . home_url(),
			'headers'             => array(
				'Accept' => 'application/json',
			),
		);

		/**
		 * Filter the wp_remote_get() args used to fetch an integrations URL.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $args   Request args.
		 * @param string $target Full upstream URL.
		 */
		$args = apply_filters( 'bb_integrations_request_args', $args, $target );

		$response = wp_remote_get( $target, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'bb_rest_integrations_upstream_error',
				/* translators: %s: error message from wp_remote_get */
				sprintf( __( 'Failed to load integrations: %s', 'buddyboss' ), $response->get_error_message() ),
				array( 'status' => 502 )
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( 404 === $status ) {
			return new WP_Error(
				'bb_rest_integrations_not_found',
				__( 'Integration not found.', 'buddyboss' ),
				array( 'status' => 404 )
			);
		}
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'bb_rest_integrations_upstream_status',
				/* translators: %d: HTTP status code returned by upstream */
				sprintf( __( 'Integrations service returned an unexpected status (%d).', 'buddyboss' ), $status ),
				array( 'status' => 502 )
			);
		}

		$raw_body = wp_remote_retrieve_body( $response );

		// Belt-and-suspenders: some transports ignore limit_response_size, so bail
		// before decoding/caching an oversized body.
		if ( strlen( $raw_body ) > self::MAX_RESPONSE_BYTES ) {
			return new WP_Error(
				'bb_rest_integrations_too_large',
				__( 'Integrations service returned an unexpectedly large response.', 'buddyboss' ),
				array( 'status' => 502 )
			);
		}

		$decoded = json_decode( $raw_body, true );
		$body    = is_array( $decoded ) ? $decoded : $raw_body;

		// Surface only the pagination headers the listing reads.
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array
	 */
	public function get_proxy_response_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bb_integrations_proxy',
			'type'       => 'object',
			'properties' => array(
				'body'    => array(
					'description' => __( 'Upstream response body, JSON-decoded when possible.', 'buddyboss' ),
					'type'        => array( 'object', 'array', 'string' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'headers' => array(
					'description' => __( 'Selected pagination headers from the upstream response.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'status'  => array(
					'description' => __( 'Upstream HTTP status code.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}
}
