<?php
/**
 * BuddyBoss Support Access.
 *
 * Lets a site administrator grant the BuddyBoss support team time-boxed,
 * token-based login access to the site. A dedicated support user account is
 * created once and kept; granting access generates a secure login URL whose
 * token is stored only as a SHA-256 hash. Access expires after a configurable
 * number of days and is enforced on every request (lazy expiry) plus actively
 * revoked by a wp-cron cleanup event.
 *
 * Security model:
 *  - Token is generated with wp_generate_password() (CSPRNG-backed).
 *  - Only hash( 'sha256', $token ) is persisted; the raw token lives only in
 *    the login URL handed to support.
 *  - Validation hashes the incoming token and compares with hash_equals()
 *    (timing-safe), then checks expiry, then authenticates the support user.
 *  - Disabling/expiry deletes the stored hash so the URL is permanently dead;
 *    the support user account itself is never deleted.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Support_Access.
 *
 * Singleton that owns the entire support-access lifecycle: the front-end
 * token-login handler, the expiry cron, and the admin AJAX endpoints used by
 * the Settings 2.0 "Support Access" screen. Procedural wrappers in
 * bb-support-access.php delegate to this class for backward compatibility.
 *
 * @since BuddyBoss 3.1.0
 */
class BB_Support_Access {

	/**
	 * Option key for the support-access state.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const OPTION = 'bb_support_access';

	/**
	 * Query var carrying the raw login token on the public login URL.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const QUERY_VAR = 'bb-support-access';

	/**
	 * Cron hook that revokes expired support access.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const CRON_HOOK = 'bb_support_access_expire_check';

	/**
	 * Default access duration in days when first enabled.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var int
	 */
	const DEFAULT_DAYS = 10;

	/**
	 * Login (username) of the dedicated support account.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const USER_LOGIN = 'buddyboss-support';

	/**
	 * Email of the dedicated support account.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const USER_EMAIL = 'support@buddyboss.com';

	/**
	 * Maximum number of login-log entries retained in the option.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var int
	 */
	const LOG_LIMIT = 10;

	/**
	 * AJAX nonce action. Matches BB_Admin_Settings_Ajax so the existing React
	 * nonce continues to authenticate these endpoints without any JS change.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Transient key used as an advisory lock around option read-modify-write.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const LOCK_KEY = 'bb_support_access_lock';

	/**
	 * Authenticated cipher used to encrypt the token at rest.
	 *
	 * AES-256-GCM is an AEAD cipher: it produces an authentication tag so a
	 * tampered ciphertext fails decryption (we then fall back to omitting the
	 * URL rather than emitting a forged one). Available since PHP 7.1 via the
	 * by-reference $tag parameter; the plugin floor is 7.4.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const TOKEN_CIPHER = 'aes-256-gcm';

	/**
	 * Lock time-to-live, in seconds. A crashed request can never hold the lock
	 * longer than this — it self-expires.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var int
	 */
	const LOCK_TTL = 5;

	/**
	 * Support system proxy base URL (AWS API Gateway).
	 *
	 * Hardcoded as an immutable class constant ON PURPOSE: it is intentionally
	 * NOT filterable and NOT overridable via constant/option/env. Nothing in the
	 * request can redirect where the note is sent — the only variable is the
	 * conversation ID, which is cast through intval() into the path. The gateway
	 * holds the support system API token server-side; this plugin ships no secret.
	 *
	 * Full URL: {base}/conversations/{conversation_id}/threads
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const SUPPORT_SYSTEM_API_BASE = 'https://oq8tjkh4kk.execute-api.us-east-2.amazonaws.com/v1';

	/**
	 * Outbound request timeout (seconds) for the support system proxy call. Kept
	 * short so a slow/dead gateway can never noticeably delay the admin action.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var int
	 */
	const SUPPORT_SYSTEM_TIMEOUT = 8;

	/**
	 * Rate-limit window (seconds) per context+ticket. A repeat notification for
	 * the same ticket inside this window is suppressed, so rapid clicks or a
	 * retry loop cannot hammer the gateway / helpdesk.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var int
	 */
	const SUPPORT_SYSTEM_RATE_WINDOW = 60;

	/**
	 * Hard cap on the JSON-encoded payload size (bytes) sent to the gateway.
	 * A defensive bound — the real payload is tiny; anything larger indicates
	 * tampering and is rejected before the request leaves the site.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var int
	 */
	const SUPPORT_SYSTEM_MAX_PAYLOAD = 8192;

	/**
	 * HTTP header carrying the shared gateway gate-pass key.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const SUPPORT_SYSTEM_KEY_HEADER = 'X-BB-Support-Key';

	/**
	 * Shared gate-pass key sent to the API Gateway.
	 *
	 * Hardcoded as an immutable class constant ON PURPOSE: it is intentionally
	 * NOT overridable via constant/option/env and NOT filterable, so nothing at
	 * runtime can change it. The matching value is configured on the API Gateway
	 * so it can reject requests that did not originate from a BuddyBoss site,
	 * blocking unauthenticated bots from spending support system calls.
	 *
	 * IMPORTANT: this is NOT the support system API token (that lives only in AWS).
	 * Because it ships in plugin source it is a low-value "gate pass", not a
	 * secret — real bot defense leans on AWS WAF + throttling + Lambda checks.
	 * It is sent only as an HTTP header (never in the note body, never logged).
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const SUPPORT_SYSTEM_GATEWAY_KEY = '76b2e03e99ec74898f78ae098a9e41462637e60d7a01f6d8';

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var BB_Support_Access|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return BB_Support_Access
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor — registers all hook surfaces.
	 *
	 * @since BuddyBoss 3.1.0
	 */
	private function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register front-end, cron, and AJAX hooks.
	 *
	 * The login handler runs on regular front-end `init` (support is not yet
	 * authenticated and not in wp-admin when they click the URL). Priority 5
	 * keeps it early but after WordPress core finishes its own init-time
	 * auth/cookie setup. AJAX handlers are only useful in the admin/AJAX
	 * context but registering `wp_ajax_*` outside it is harmless.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'init', array( $this, 'handle_login' ), 5 );
		add_action( self::CRON_HOOK, array( $this, 'cron_expire' ) );

		add_action( 'wp_ajax_bb_admin_support_access_get', array( $this, 'ajax_get' ) );
		add_action( 'wp_ajax_bb_admin_support_access_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_bb_admin_support_access_extend', array( $this, 'ajax_extend' ) );
		add_action( 'wp_ajax_bb_admin_support_access_set_ticket', array( $this, 'ajax_set_ticket' ) );

		// Hide the support account from the BuddyBoss member directory and the
		// `/buddyboss/v1/members` REST endpoint (both backed by BP_User_Query).
		// The account stays visible in the wp-admin Users list for auditing.
		add_filter( 'bp_user_query_uid_clauses', array( $this, 'exclude_from_user_query' ), 10, 2 );
	}

	/**
	 * Resolve the support account's user ID without provisioning it.
	 *
	 * Unlike {@see get_support_user()}, this never creates the account — it only
	 * looks up an account that already exists, identified solely by the
	 * `_bb_support_access_user` meta flag. This makes it safe to call from query
	 * filters that run on every member listing: those must never have the side
	 * effect of provisioning the support administrator, nor hide an unrelated
	 * user who happens to share the canonical email/login. The result is cached
	 * for the request.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return int Support user ID, or 0 if the account does not exist yet.
	 */
	public function get_support_user_id() {
		static $support_user_id = null;

		if ( null !== $support_user_id ) {
			return $support_user_id;
		}

		// Prefer the flagged account so a renamed login/email still matches.
		$flagged = get_users(
			array(
				'meta_key'    => '_bb_support_access_user', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'      => 'ID',
				'number'      => 1,
				'count_total' => false,
			)
		);

		// The flag is the sole authority: get_support_user() never adopts an
		// unflagged account, so there is no canonical email/login fallback here.
		// That guarantees a real user who merely shares the support email/login
		// is never silently hidden from the directory or members REST endpoint.
		$support_user_id = ! empty( $flagged ) ? (int) $flagged[0] : 0;

		return $support_user_id;
	}

	/**
	 * Exclude the support account from BP_User_Query result sets.
	 *
	 * Appends a `NOT IN` clause to the user-id query so the support
	 * administrator is never disclosed through the member directory or the
	 * `/buddyboss/v1/members` REST endpoint, even while a grant is active. The
	 * account is identified lazily via {@see get_support_user_id()} so this
	 * filter never provisions it.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param array         $sql   Array of SQL clauses for the user query.
	 * @param BP_User_Query $query Current BP_User_Query instance (by reference).
	 *
	 * @return array Filtered SQL clauses.
	 */
	public function exclude_from_user_query( $sql, $query ) {
		$support_user_id = $this->get_support_user_id();

		if ( empty( $support_user_id ) ) {
			return $sql;
		}

		// uid_name is a BP_User_Query internal (not user input), but it is
		// interpolated into raw SQL — whitelist it to the only two legitimate
		// column names so a stray third-party filter can never inject anything
		// unexpected. The id is already integer-cast.
		$uid_name = isset( $query->uid_name ) ? $query->uid_name : 'ID';
		$uid_name = in_array( $uid_name, array( 'ID', 'user_id' ), true ) ? $uid_name : 'ID';

		$sql['where'][] = "u.{$uid_name} NOT IN (" . (int) $support_user_id . ')';

		return $sql;
	}

	/**
	 * Allowed extension durations (in days) for enable / extend operations.
	 *
	 * Bounding the accepted values prevents a tampered AJAX payload from setting
	 * an absurd or negative expiry. These must stay in sync with the options the
	 * ModifyDurationModal UI offers (1–7 days); sites needing longer windows can
	 * add them via the `bb_support_access_allowed_days` filter.
	 *
	 * NOTE: this governs the increments offered when EXTENDING a grant (and the
	 * set of explicit durations accepted). The duration of a fresh "Open Access"
	 * grant is a separate concept controlled by {@see default_days()} /
	 * `bb_support_access_default_days` — so shortening this list does NOT shorten
	 * the initial window.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return int[] Allowed day counts.
	 */
	public function allowed_days() {
		$days = array( 1, 3, 5, 7 );

		/**
		 * Filter the allowed support-access EXTENSION durations (in days).
		 *
		 * Governs the durations accepted when extending a grant; the initial
		 * grant length is filtered separately via `bb_support_access_default_days`.
		 *
		 * @since BuddyBoss 3.1.0
		 *
		 * @param int[] $days Allowed day counts.
		 */
		return array_values( array_unique( array_map( 'absint', apply_filters( 'bb_support_access_allowed_days', $days ) ) ) );
	}

	/**
	 * Initial "Open Access" grant duration (in days).
	 *
	 * This is the length of a freshly enabled grant and the fallback used when
	 * an out-of-range duration is submitted. It is deliberately independent of
	 * allowed_days() (the shorter extend increments), so compliance-conscious
	 * sites that need a shorter initial window can cap it here without touching
	 * the extension options.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return int Default initial duration in days (always >= 1).
	 */
	public function default_days() {
		/**
		 * Filter the initial Support Access grant duration (in days).
		 *
		 * Governs ONLY the length of a fresh "Open Access" grant. The increments
		 * offered when EXTENDING an existing grant are filtered separately via
		 * `bb_support_access_allowed_days`.
		 *
		 * @since BuddyBoss 3.1.0
		 *
		 * @param int $days Default initial duration in days.
		 */
		$days = (int) apply_filters( 'bb_support_access_default_days', self::DEFAULT_DAYS );

		return $days > 0 ? $days : self::DEFAULT_DAYS;
	}

	/**
	 * Run a read-modify-write callback under an advisory lock.
	 *
	 * The single `bb_support_access` option is updated by several flows
	 * (login auditing, ticket appends, enable/disable). Without a lock, two
	 * concurrent requests can read the same snapshot and the second write
	 * clobbers the first — typically a lost audit-log row. WordPress options
	 * have no atomic update, so this uses `add_option()` (which is atomic: it
	 * fails when the key already exists) as a mutex, with a short bounded wait
	 * for a colliding request. The lock self-expires after LOCK_TTL so a
	 * crashed request can never deadlock it.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param callable $callback Work to run while holding the lock.
	 *
	 * @return mixed Whatever the callback returns.
	 */
	private function with_lock( $callback ) {
		// The mutex and the data it guards must live on the same blog. get_data()
		// and save() both operate on the root blog, so acquire the lock there too.
		// Otherwise, on multisite, two writers running on different blogs would
		// each take a separate current-blog lock and the read-modify-write would
		// no longer serialize — the exact lost update this lock exists to prevent.
		// Holding the root blog context for the whole critical section also means
		// the save() inside $callback is already on the root blog, so its own
		// switch is skipped. On single site current === root, so this is a no-op.
		$root_blog_id = bp_get_root_blog_id();
		$switched     = false;
		if ( get_current_blog_id() !== $root_blog_id ) {
			switch_to_blog( $root_blog_id );
			$switched = true;
		}

		$acquired = false;
		$attempts = 0;

		// Try to acquire for up to ~250ms; otherwise proceed without the lock
		// rather than failing the user's action outright. The window is tiny and
		// the worst case (a missed audit row) is non-critical.
		while ( $attempts < 25 ) {
			// add_option() returns false if the option already exists, making it
			// an atomic test-and-set. autoload 'no' keeps it out of alloptions.
			if ( add_option( self::LOCK_KEY, time() + self::LOCK_TTL, '', 'no' ) ) {
				$acquired = true;
				break;
			}

			// If a stale lock outlived its TTL (e.g. a crashed request), reclaim it.
			$held_until = (int) get_option( self::LOCK_KEY );
			if ( $held_until && $held_until < time() ) {
				delete_option( self::LOCK_KEY );
				continue;
			}

			usleep( 10000 ); // 10ms.
			++$attempts;
		}

		try {
			return call_user_func( $callback );
		} finally {
			if ( $acquired ) {
				delete_option( self::LOCK_KEY );
			}
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}

	/**
	 * Persist the support-access state.
	 *
	 * Always writes with autoload disabled: the option carries the token hash
	 * and encrypted token, and is only ever read by the front-end login handler
	 * and the admin AJAX endpoints — never on a generic page load. Keeping it
	 * out of the autoloaded option set shrinks the in-memory exposure surface
	 * (the sensitive blob is loaded on demand, not into every request).
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param array $data State to store.
	 *
	 * @return void
	 */
	private function save( $data ) {
		// This option is always read from the root blog: get_data() uses
		// bp_get_option(), which reads via get_blog_option( bp_get_root_blog_id() ).
		// On multisite the admin action can run on a non-root blog, so the write
		// must target the root blog too — otherwise the grant lands where the
		// login/read path never looks and reads back as inactive.
		//
		// The third argument to update_option() forces autoload off: the option
		// carries the token hash and encrypted token and is only ever read on
		// demand (front-end login handler / admin AJAX), never on a generic page
		// load, so keeping it out of alloptions shrinks the in-memory exposure
		// surface. bp_update_option()/update_blog_option() cannot set autoload,
		// so switch into the root blog context and call update_option() directly.
		// When already on the root blog the switch is skipped — always the case on
		// single site, and also when invoked inside with_lock().
		$root_blog_id = bp_get_root_blog_id();
		$switched     = false;
		if ( get_current_blog_id() !== $root_blog_id ) {
			switch_to_blog( $root_blog_id );
			$switched = true;
		}

		// The autoload flag also gets corrected here for a grant created before
		// this change that previously autoloaded, on its next save.
		update_option( self::OPTION, $data, false );

		if ( $switched ) {
			restore_current_blog();
		}
	}

	/**
	 * Persist the support-access state under the advisory lock.
	 *
	 * Re-reads inside the lock and lets the caller mutate that fresh snapshot,
	 * so a concurrent writer (login audit, ticket append, enable/disable) cannot
	 * clobber the result. Use this for every read-modify-write and for the
	 * full-state writes in enable()/disable() so all mutators serialize against
	 * the same mutex.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param callable $mutator Receives the fresh state by value, returns the
	 *                          state to persist, or null to skip the write.
	 *
	 * @return mixed The mutator's return value.
	 */
	private function save_locked( $mutator ) {
		return $this->with_lock(
			function () use ( $mutator ) {
				$data = $this->get_data();
				$next = call_user_func( $mutator, $data );
				if ( is_array( $next ) ) {
					$this->save( $next );
				}

				return $next;
			}
		);
	}

	/**
	 * Get the support-access state, normalized to a known shape.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return array {
	 *     @type int      $enabled         1 if a grant exists, else 0.
	 *     @type int      $support_user_id The dedicated support user ID.
	 *     @type string   $token_hash      SHA-256 hash of the active token (never the raw token).
	 *     @type int      $expires         UTC unix timestamp of expiry.
	 *     @type string[] $ticket_numbers  Associated support system ticket numbers — one URL can cover several tickets.
	 *     @type int      $created         UTC unix timestamp of the last enable.
	 *     @type array    $login_log       Bounded list of recent successful logins.
	 * }
	 */
	public function get_data() {
		$defaults = array(
			'enabled'         => 0,
			'support_user_id' => 0,
			'token_hash'      => '',
			'token_enc'       => '',
			'expires'         => 0,
			'ticket_numbers'  => array(),
			'created'         => 0,
			'login_log'       => array(),
		);

		$data = bp_get_option( self::OPTION, array() );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		// Migrate the legacy scalar `ticket_number` into the `ticket_numbers`
		// array on read, so previously-stored grants upgrade seamlessly with no
		// separate migration step. The legacy key is then dropped.
		if ( isset( $data['ticket_number'] ) ) {
			if ( ! isset( $data['ticket_numbers'] ) || ! is_array( $data['ticket_numbers'] ) ) {
				$data['ticket_numbers'] = array();
			}
			$legacy = sanitize_text_field( (string) $data['ticket_number'] );
			if ( '' !== $legacy && ! in_array( $legacy, $data['ticket_numbers'], true ) ) {
				$data['ticket_numbers'][] = $legacy;
			}
			unset( $data['ticket_number'] );
		}

		// Scrub a legacy `login_url` left by grants created before raw-token
		// storage was removed. Earlier builds persisted the raw token (embedded
		// in the login URL) here, which defeated the hash-at-rest model. Dropping
		// it on read means the stale credential is removed from the option the
		// next time it is saved — no separate migration step required.
		if ( isset( $data['login_url'] ) ) {
			unset( $data['login_url'] );
		}

		$data = wp_parse_args( $data, $defaults );

		// Coerce types defensively — the option may have been touched by other code.
		$data['enabled']         = absint( $data['enabled'] ) ? 1 : 0;
		$data['support_user_id'] = absint( $data['support_user_id'] );
		$data['token_hash']      = is_string( $data['token_hash'] ) ? $data['token_hash'] : '';
		$data['token_enc']       = is_string( $data['token_enc'] ) ? $data['token_enc'] : '';
		$data['expires']         = absint( $data['expires'] );
		$data['ticket_numbers']  = $this->normalize_ticket_numbers( $data['ticket_numbers'] );
		$data['created']         = absint( $data['created'] );
		$data['login_log']       = is_array( $data['login_log'] ) ? $data['login_log'] : array();

		return $data;
	}

	/**
	 * Normalize a raw ticket-numbers value into a clean, deduped string list.
	 *
	 * Accepts an array or a single scalar, sanitizes each entry, drops empties,
	 * and removes duplicates while preserving insertion order.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param mixed $value Raw ticket value (array or scalar).
	 *
	 * @return string[] Sanitized, deduped ticket numbers.
	 */
	private function normalize_ticket_numbers( $value ) {
		if ( ! is_array( $value ) ) {
			$value = ( '' === (string) $value ) ? array() : array( $value );
		}

		$clean = array();
		foreach ( $value as $ticket ) {
			$ticket = sanitize_text_field( (string) $ticket );
			if ( '' === $ticket || in_array( $ticket, $clean, true ) ) {
				continue;
			}
			$clean[] = $ticket;
		}

		return $clean;
	}

	/**
	 * Resolve the client IP for the audit log.
	 *
	 * Only REMOTE_ADDR is trusted. Proxy headers such as X-Forwarded-For are
	 * client-spoofable and would let a caller forge audit entries, so they are
	 * deliberately ignored. Sites behind a known reverse proxy can override the
	 * resolved value via the filter below.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return string Sanitized IP address, or empty string if unavailable/invalid.
	 */
	public function get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';

		/**
		 * Filter the client IP recorded in the support-access audit log.
		 *
		 * Trusted-proxy setups may substitute a validated forwarded address here.
		 *
		 * @since BuddyBoss 3.1.0
		 *
		 * @param string $ip The REMOTE_ADDR-derived client IP.
		 */
		$filtered = (string) apply_filters( 'bb_support_access_client_ip', $ip );

		// Re-validate after filtering. The audit log is a security record, so a
		// filter (legitimate reverse-proxy setups, or a misbehaving/malicious
		// plugin) can never write a non-IP value into it — an invalid result
		// falls back to empty rather than being trusted verbatim.
		return filter_var( $filtered, FILTER_VALIDATE_IP ) ? $filtered : '';
	}

	/**
	 * Append a successful-login entry to the bounded audit log.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function record_login() {
		$ip = $this->get_client_ip();

		$this->save_locked(
			function ( $data ) use ( $ip ) {
				$log   = $data['login_log'];
				$log[] = array(
					'time' => time(),
					'ip'   => $ip,
				);

				// Keep only the most recent entries.
				if ( count( $log ) > self::LOG_LIMIT ) {
					$log = array_slice( $log, -self::LOG_LIMIT );
				}

				$data['login_log'] = $log;

				return $data;
			}
		);
	}

	/**
	 * Record a failed token-login attempt (per-IP counter, for monitoring).
	 *
	 * Increments a short-lived per-IP counter in a transient and exposes the
	 * running count via an action so monitoring can alert on guessing. It does
	 * NOT lock anyone out: a hard lockout keyed on a spoofable-ish IP would let
	 * an attacker deny the real support team access, and the token's entropy
	 * already makes brute force infeasible. This is observability, not a gate.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	private function record_failed_login() {
		$ip = $this->get_client_ip();
		if ( '' === $ip ) {
			return;
		}

		$key   = 'bb_sa_failed_' . md5( $ip );
		$count = (int) get_transient( $key ) + 1;

		// Keep the window short; this is an alerting signal, not a durable log.
		set_transient( $key, $count, HOUR_IN_SECONDS );

		/**
		 * Fires with the running per-IP failed-attempt count for support login.
		 *
		 * @since BuddyBoss 3.1.0
		 *
		 * @param string $ip    Client IP.
		 * @param int    $count Failed attempts from this IP within the window.
		 */
		do_action( 'bb_support_access_failed_login_count', $ip, $count );
	}

	/**
	 * Whether support access is currently active (enabled AND not expired).
	 *
	 * This is the single source of truth the UI toggle reflects. Because it is
	 * computed from the stored expiry on every call, an expired grant reads as
	 * inactive the instant it lapses — no background process required.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return bool True if access is live.
	 */
	public function is_active() {
		$data = $this->get_data();

		return ( 1 === $data['enabled'] )
			&& ! empty( $data['token_hash'] )
			&& $data['expires'] > time();
	}

	/**
	 * Whether a URL is a genuine support-access login URL for THIS site.
	 *
	 * The login URL is supplied by the browser when a ticket is added (it is
	 * never persisted server-side). This guard ensures only a link to this
	 * site's own login flow can be embedded in a helpdesk note — a tampered
	 * request cannot smuggle an arbitrary/phishing link into support system. It
	 * checks the full origin (scheme + host + port) matches home_url() and that
	 * our query var is present; it deliberately does NOT validate the token
	 * value (only the hash is stored).
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param string $url Candidate login URL.
	 *
	 * @return bool True if the URL belongs to this site's login flow.
	 */
	private function is_own_login_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return false;
		}

		$parts = wp_parse_url( $url );
		$home  = wp_parse_url( home_url( '/' ) );

		if ( empty( $parts['host'] ) || empty( $home['host'] ) ) {
			return false;
		}

		// Host must match this site exactly (case-insensitive per RFC 3986).
		if ( strtolower( $parts['host'] ) !== strtolower( $home['host'] ) ) {
			return false;
		}

		// Scheme must be http or https. We intentionally do NOT require it to
		// match the home scheme exactly: a TLS-terminating proxy or load
		// balancer can legitimately surface an http URL for an https site (or
		// vice versa), and an exact match would silently drop a valid login
		// link. The host + port match above and the login-token query var
		// below are the real identity guard; this only rejects dangerous
		// schemes (javascript:, data:, etc.).
		$url_scheme = isset( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : '';
		if ( 'http' !== $url_scheme && 'https' !== $url_scheme ) {
			return false;
		}

		// Port must match. A default port is omitted by wp_parse_url(), so a
		// missing port on both sides compares equal; a non-default port on one
		// side only must match the other exactly.
		$url_port  = isset( $parts['port'] ) ? (int) $parts['port'] : 0;
		$home_port = isset( $home['port'] ) ? (int) $home['port'] : 0;
		if ( $url_port !== $home_port ) {
			return false;
		}

		// Must carry our login query var.
		if ( empty( $parts['query'] ) ) {
			return false;
		}
		parse_str( $parts['query'], $query );

		return ! empty( $query[ self::QUERY_VAR ] );
	}

	/**
	 * Get (creating once if needed) the dedicated support user.
	 *
	 * The account is created a single time and kept forever — revoking access
	 * deletes only the token, never the user. The user is a standard
	 * administrator so support can troubleshoot, but it carries a
	 * `_bb_support_access_user` meta flag so the account is identifiable and is
	 * excluded from the member directory and the members REST endpoint via
	 * {@see exclude_from_user_query()}.
	 *
	 * Resolution is flag-first: only an account this plugin previously flagged is
	 * reused. A pre-existing user that merely shares the canonical email/login is
	 * never adopted — a conflict returns a WP_Error so the admin can resolve it.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return int|WP_Error Support user ID on success, WP_Error on failure or
	 *                      when the canonical email/login is already taken.
	 */
	public function get_support_user() {
		// Reuse ONLY an account this plugin previously provisioned, identified by
		// the meta flag. We deliberately do not adopt a pre-existing user that
		// merely shares the canonical email/login but was never flagged by us:
		// silently flagging, hiding, and token-enabling a real admin account
		// would be a security problem.
		$flagged = get_users(
			array(
				'meta_key'    => '_bb_support_access_user', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'      => 'ID',
				'number'      => 1,
				'count_total' => false,
			)
		);

		if ( ! empty( $flagged ) ) {
			return (int) $flagged[0];
		}

		// No flagged account yet. Refuse to hijack an existing user that already
		// owns the canonical email or login — surface an error so the admin can
		// rename/remove it rather than have their account adopted by support.
		if ( get_user_by( 'email', self::USER_EMAIL ) || get_user_by( 'login', self::USER_LOGIN ) ) {
			return new WP_Error(
				'bb_support_user_conflict',
				__( 'A user already exists with the BuddyBoss support email or login. Please rename or remove that account before enabling Support Access.', 'buddyboss-platform' )
			);
		}

		// Create the account with a long random password the admin never needs —
		// login happens exclusively through the token flow, never via password.
		$user_id = wp_insert_user(
			array(
				'user_login'   => self::USER_LOGIN,
				'user_email'   => self::USER_EMAIL,
				'user_pass'    => wp_generate_password( 64, true, true ),
				'display_name' => __( 'BuddyBoss Support', 'buddyboss-platform' ),
				'first_name'   => __( 'BuddyBoss', 'buddyboss-platform' ),
				'last_name'    => __( 'Support', 'buddyboss-platform' ),
				'role'         => 'administrator',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, '_bb_support_access_user', 1 );

		return (int) $user_id;
	}

	/**
	 * Build the public login URL for a raw token.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param string $token Raw (unhashed) token.
	 *
	 * @return string Login URL.
	 */
	public function build_login_url( $token ) {
		return add_query_arg(
			self::QUERY_VAR,
			rawurlencode( $token ),
			home_url( '/' )
		);
	}

	/**
	 * Derive the symmetric key used to encrypt the token at rest.
	 *
	 * The key is derived from WordPress's own secret salts (which live in
	 * wp-config.php, NOT the database) plus the option name. A database-only
	 * compromise therefore cannot decrypt the token — the attacker also needs
	 * the wp-config salts. Rotating the salts invalidates stored ciphertext,
	 * which simply means notes stop including the URL until the next enable;
	 * authentication is unaffected (it uses token_hash, not this).
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return string 32-byte binary key.
	 */
	private function token_key() {
		// Raw binary output (true) gives a 32-byte key, the exact size AES-256
		// requires. Namespaced by the option so the key is specific to this use.
		return hash( 'sha256', wp_salt( 'auth' ) . '|' . self::OPTION, true );
	}

	/**
	 * Whether token-at-rest encryption is available on this host.
	 *
	 * PHP can be built without OpenSSL, and a given build may lack the GCM
	 * cipher. When unavailable we simply do not persist the token (the URL then
	 * relies on the browser round-trip), never falling back to plaintext.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return bool True if AES-256-GCM encryption can be used.
	 */
	private function can_encrypt_token() {
		return function_exists( 'openssl_encrypt' )
			&& function_exists( 'random_bytes' )
			&& in_array( self::TOKEN_CIPHER, openssl_get_cipher_methods(), true );
	}

	/**
	 * Encrypt the raw token for storage.
	 *
	 * Produces a self-describing, URL-safe string: base64( iv | tag | cipher ).
	 * A fresh random IV is generated per call. AES-256-GCM authenticates the
	 * ciphertext, so any tampering is detected on decrypt. Returns '' when
	 * encryption is unavailable or fails, so the caller can degrade gracefully.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param string $token Raw (unhashed) token.
	 *
	 * @return string Encrypted blob (base64), or '' on failure/unavailability.
	 */
	private function encrypt_token( $token ) {
		$token = (string) $token;
		if ( '' === $token || ! $this->can_encrypt_token() ) {
			return '';
		}

		$iv_len = openssl_cipher_iv_length( self::TOKEN_CIPHER );
		if ( ! $iv_len ) {
			return '';
		}

		try {
			$iv = random_bytes( $iv_len );
		} catch ( Exception $e ) {
			return '';
		}

		$tag = '';
		// phpcs:ignore PHPCompatibility.FunctionUse.NewFunctionParameters.openssl_encrypt_tagFound -- The $tag AEAD parameter is PHP 7.1+; the plugin floor is 7.4 (the phpcs.xml testVersion of 5.6 is stale). Guarded at runtime by can_encrypt_token().
		$cipher = openssl_encrypt( $token, self::TOKEN_CIPHER, $this->token_key(), OPENSSL_RAW_DATA, $iv, $tag );

		if ( false === $cipher || '' === $tag ) {
			return '';
		}

		return base64_encode( $iv . $tag . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoding binary ciphertext for storage, not obfuscating code.
	}

	/**
	 * Decrypt a stored token blob back to the raw token.
	 *
	 * Inverse of encrypt_token(). Returns '' on any failure — wrong/rotated
	 * key, truncated data, or a failed authentication tag — so a corrupted or
	 * tampered blob can never yield a usable (or forged) value.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param string $encrypted Encrypted blob produced by encrypt_token().
	 *
	 * @return string Raw token, or '' on failure.
	 */
	private function decrypt_token( $encrypted ) {
		$encrypted = (string) $encrypted;
		if ( '' === $encrypted || ! $this->can_encrypt_token() ) {
			return '';
		}

		$raw = base64_decode( $encrypted, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding stored binary ciphertext, not obfuscated code.
		if ( false === $raw ) {
			return '';
		}

		$iv_len  = openssl_cipher_iv_length( self::TOKEN_CIPHER );
		$tag_len = 16; // GCM authentication tag is 16 bytes.
		if ( ! $iv_len || strlen( $raw ) <= ( $iv_len + $tag_len ) ) {
			return '';
		}

		$iv     = substr( $raw, 0, $iv_len );
		$tag    = substr( $raw, $iv_len, $tag_len );
		$cipher = substr( $raw, $iv_len + $tag_len );

		// phpcs:ignore PHPCompatibility.FunctionUse.NewFunctionParameters.openssl_decrypt_tagFound -- The $tag AEAD parameter is PHP 7.1+; the plugin floor is 7.4 (the phpcs.xml testVersion of 5.6 is stale). Guarded at runtime by can_encrypt_token().
		$token = openssl_decrypt( $cipher, self::TOKEN_CIPHER, $this->token_key(), OPENSSL_RAW_DATA, $iv, $tag );

		return ( false === $token ) ? '' : $token;
	}

	/**
	 * Rebuild the login URL from the stored encrypted token, if possible.
	 *
	 * Decrypts the at-rest token and reconstructs the public login URL so notes
	 * can always include it — even across page reloads and fresh sessions, when
	 * the browser no longer holds the URL. Returns '' if nothing is stored or
	 * decryption is unavailable/fails (the note then omits the URL).
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param array|null $data Optional pre-fetched state to avoid a re-read.
	 *
	 * @return string Login URL, or '' when it cannot be rebuilt.
	 */
	private function get_login_url( $data = null ) {
		if ( null === $data ) {
			$data = $this->get_data();
		}

		if ( empty( $data['token_enc'] ) ) {
			return '';
		}

		$token = $this->decrypt_token( $data['token_enc'] );
		if ( '' === $token ) {
			return '';
		}

		return $this->build_login_url( $token );
	}

	/**
	 * Enable support access: ensure the support user, mint a token, set expiry.
	 *
	 * Regenerates the token every time it is called, so re-enabling invalidates
	 * any previously issued URL.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param int $days Number of days the grant is valid for.
	 *
	 * @return array|WP_Error {
	 *     @type string $login_url    The freshly minted login URL (raw token embedded).
	 *     @type int    $expires      UTC unix timestamp of expiry.
	 *     @type int    $support_user The support user ID.
	 * } or WP_Error on failure.
	 */
	public function enable( $days = self::DEFAULT_DAYS ) {
		$days = absint( $days );
		if ( ! in_array( $days, $this->allowed_days(), true ) ) {
			$days = $this->default_days();
		}

		$support_user_id = $this->get_support_user();
		if ( is_wp_error( $support_user_id ) ) {
			return $support_user_id;
		}

		// Mint a fresh token. Store only its hash for authentication.
		$token     = wp_generate_password( 64, false );
		$expires   = time() + ( $days * DAY_IN_SECONDS );
		$login_url = $this->build_login_url( $token );

		// Every enable is a clean slate: a brand-new token plus an empty ticket
		// list and audit log. Nothing from a prior session carries over, so a
		// re-enabled grant never inherits old tickets or login history (and the
		// previously-issued URL is already dead because its hash is replaced).
		//
		// Token storage is two-fold, by design. `token_hash` (SHA-256) is the
		// ONLY value used for authentication; a database read yields just this
		// irreversible hash. `token_enc` is the token encrypted with a key
		// derived from the wp-config secret salts (which are NOT in the DB). It
		// exists solely so notes can always include the login URL — even after a
		// page reload or in a fresh session — without the browser round-tripping
		// it. A DB-only leak cannot decrypt it (the salts live outside the
		// database), and it is never used to log anyone in. If encryption is
		// unavailable on the host, `token_enc` is simply empty and the URL falls
		// back to the browser round-trip. The raw token itself is never persisted.
		$data = array(
			'enabled'         => 1,
			'support_user_id' => (int) $support_user_id,
			'token_hash'      => hash( 'sha256', $token ),
			'token_enc'       => $this->encrypt_token( $token ),
			'expires'         => $expires,
			'ticket_numbers'  => array(),
			'created'         => time(),
			'login_log'       => array(),
		);

		// Write the fresh grant under the lock so a concurrent login-audit or
		// ticket append cannot read an old snapshot and clobber the new token.
		// enable() is a clean-slate replacement (not a read-modify-write), so we
		// take the lock and write directly rather than re-reading first.
		$this->with_lock(
			function () use ( $data ) {
				$this->save( $data );
			}
		);

		$this->schedule_expiry( $expires );

		/**
		 * Fires after support access is enabled.
		 *
		 * @since BuddyBoss 3.1.0
		 *
		 * @param string $login_url The login URL (contains the raw token).
		 * @param int    $expires   UTC unix timestamp of expiry.
		 */
		do_action( 'bb_support_access_enabled', $login_url, $expires );

		return array(
			'login_url'    => $login_url,
			'expires'      => $expires,
			'support_user' => (int) $support_user_id,
		);
	}

	/**
	 * Extend the current grant by adding days on top of the time remaining.
	 *
	 * Extending a grant with ~10 days left by 5 days yields ~15 days, not 5. If
	 * no active grant exists, this is equivalent to a fresh enable (which mints
	 * a new token).
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param int    $days      Days to add.
	 * @param string $login_url Optional. The existing login URL the caller
	 *                          already holds (the token is unchanged by an
	 *                          extend, so the same URL stays valid). It is
	 *                          echoed back in the result; it is NOT rebuilt
	 *                          here because only the token hash is stored.
	 *                          The caller must validate it (is_own_login_url()).
	 *
	 * @return array|WP_Error Same shape as enable(), or WP_Error.
	 */
	public function extend( $days, $login_url = '' ) {
		$days = absint( $days );
		if ( ! in_array( $days, $this->allowed_days(), true ) ) {
			return new WP_Error( 'bb_support_access_invalid_days', __( 'Invalid duration.', 'buddyboss-platform' ) );
		}

		$data = $this->get_data();

		// No live grant — start a fresh one (mints a new token + URL).
		if ( empty( $data['token_hash'] ) || $data['expires'] <= time() ) {
			return $this->enable( $days );
		}

		// Add the selected days on top of the time already remaining. The guard
		// above guarantees $data['expires'] is a valid future timestamp here.
		$expires = $data['expires'] + ( $days * DAY_IN_SECONDS );

		// Persist under the lock, re-reading the fresh snapshot so a concurrent
		// login-audit or ticket append is preserved; we only bump enabled/expires.
		$saved = $this->save_locked(
			function ( $fresh ) use ( $expires ) {
				$fresh['enabled'] = 1;
				$fresh['expires'] = $expires;

				return $fresh;
			}
		);

		// Use the locked-and-saved snapshot for the return payload so tickets
		// reflect anything a concurrent writer added.
		$data = is_array( $saved ) ? $saved : $data;

		$this->schedule_expiry( $expires );

		/**
		 * Fires after support access is extended.
		 *
		 * @since BuddyBoss 3.1.0
		 *
		 * @param int $expires UTC unix timestamp of the new expiry.
		 */
		do_action( 'bb_support_access_extended', $expires );

		// The token is unchanged by an extend, so the existing login URL still
		// works. We can't rebuild it (hash-only storage), so we echo back the
		// validated URL the caller supplied. `tickets` is returned so the caller
		// can re-notify each attached conversation with the new expiry (the note
		// dispatch happens in the caller, not here, to keep network I/O out of
		// this state mutator).
		return array(
			'login_url'    => (string) $login_url,
			'expires'      => $expires,
			'support_user' => $data['support_user_id'],
			'tickets'      => $data['ticket_numbers'],
			'extended'     => true,
		);
	}

	/**
	 * Post an "expiry updated" note to every conversation attached to the grant.
	 *
	 * Called after a duration change so support sees the new expiry on each
	 * ticket sharing this access. Bypasses the per-conversation rate limit
	 * because a duration change is a deliberate, infrequent admin action — not
	 * the rapid-repeat case the limiter guards against. Fail-soft per ticket.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param string[] $tickets   support system conversation IDs to notify.
	 * @param int      $expires   New expiry (UTC unix timestamp).
	 * @param string   $login_url Optional login URL to include (browser-supplied, validated).
	 *
	 * @return array {
	 *     @type int $sent   Count of conversations the note succeeded for.
	 *     @type int $failed Count of conversations the note failed for.
	 * }
	 */
	public function notify_expiry_update( $tickets, $expires, $login_url = '' ) {
		$sent   = 0;
		$failed = 0;

		$expiry_utc = $expires ? gmdate( 'Y-m-d H:i:s', (int) $expires ) . ' UTC' : __( 'n/a', 'buddyboss-platform' );

		foreach ( (array) $tickets as $ticket ) {
			$ticket = (int) $ticket;
			if ( $ticket <= 0 ) {
				continue;
			}

			$note = sprintf(
				'<p><strong>BuddyBoss Support Access</strong> duration updated for %1$s.</p><p>New expiry: <strong>%2$s</strong></p>',
				esc_html( home_url( '/' ) ),
				esc_html( $expiry_utc )
			);

			if ( '' !== $login_url ) {
				$note .= sprintf( '<p>Login URL: <a href="%1$s">%1$s</a></p>', esc_url( $login_url ) );
			}

			// Bypass the rate limit: this is a deliberate duration change, and we
			// want every attached ticket to reflect the new expiry now.
			$result = $this->notify_support_system( $ticket, $note, true );

			if ( is_wp_error( $result ) ) {
				++$failed;
			} else {
				++$sent;
			}
		}

		return array(
			'sent'   => $sent,
			'failed' => $failed,
		);
	}

	/**
	 * Disable support access: fully reset the stored state.
	 *
	 * Deletes the option entirely so nothing carries over to the next enable —
	 * the token (so the issued URL dies), the expiry, the attached tickets, and
	 * the audit log are all cleared. The dedicated support user account is NOT
	 * deleted; it lives in wp_users and is reused on the next enable.
	 *
	 * Crucially, this also destroys any live login sessions for the support
	 * account. Deleting the token only kills the issued URL — it does NOT
	 * invalidate an auth cookie support already obtained, which would otherwise
	 * survive until it naturally lapsed (~2 days). Revoking must be immediate,
	 * so we tear down the session tokens too. The next enable mints a fresh
	 * token and support logs in anew.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function disable() {
		// Delete the option under the lock so a concurrent login-audit or ticket
		// append cannot re-create it from a stale snapshot right after we remove
		// it (which would resurrect a "revoked" grant). Capture the support user
		// id inside the lock so the session teardown targets the right account.
		$support_user_id = $this->with_lock(
			function () {
				$data = $this->get_data();
				bp_delete_option( self::OPTION );

				return (int) $data['support_user_id'];
			}
		);

		$this->clear_scheduled_expiry();

		// Destroy any active session for the support account so a revoke/expiry
		// takes effect immediately rather than lingering until the auth cookie
		// would have expired on its own. WP_Session_Tokens has existed since
		// WordPress 4.0; the support account only ever signs in via the token
		// flow, so destroying all of its sessions has no collateral impact.
		if ( $support_user_id > 0 && class_exists( 'WP_Session_Tokens' ) ) {
			$manager = WP_Session_Tokens::get_instance( $support_user_id );
			$manager->destroy_all();
		}

		/**
		 * Fires after support access is disabled / revoked.
		 *
		 * @since BuddyBoss 3.1.0
		 */
		do_action( 'bb_support_access_disabled' );
	}

	/**
	 * Append a support system ticket number to the current grant and notify support.
	 *
	 * The same login URL can cover several support tickets, so tickets
	 * accumulate: each call adds the number to the list (deduped) rather than
	 * replacing it. An empty or duplicate value is a no-op for the list but
	 * still returns the current set.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param string $ticket_number Ticket number (support system conversation ID) to add.
	 * @param string $login_url     Optional. The active login URL to embed in the
	 *                              note. Supplied by the browser (never persisted)
	 *                              and pre-validated by the caller. Omitted when
	 *                              unavailable (e.g. after a page reload).
	 *
	 * @return array {
	 *     @type string[]      $tickets The full, deduped ticket list after the add.
	 *     @type bool          $added   Whether a new ticket was actually appended.
	 *     @type array|WP_Error $notify The support system note result (or WP_Error), null if not notified.
	 * }
	 */
	public function add_ticket( $ticket_number, $login_url = '' ) {
		$ticket_number = sanitize_text_field( $ticket_number );

		$data    = $this->get_data();
		$tickets = $data['ticket_numbers'];

		// Already attached (or empty) — nothing to do. Return the current list
		// without notifying or re-saving.
		if ( '' === $ticket_number || in_array( $ticket_number, $tickets, true ) ) {
			return array(
				'tickets' => $tickets,
				'added'   => false,
				'notify'  => null,
			);
		}

		// The login URL is supplied by the browser (validated by the caller via
		// is_own_login_url()). If the browser no longer holds it — e.g. the page
		// was reloaded after enabling, or this is a fresh session — rebuild it by
		// decrypting the at-rest token, so every note can include the same URL.
		// The raw token is never stored; only its encrypted form is decrypted
		// here, behind the wp-config salts.
		if ( '' === $login_url ) {
			$login_url = $this->get_login_url( $data );
		}

		$expiry_utc = $data['expires'] ? gmdate( 'Y-m-d H:i:s', $data['expires'] ) . ' UTC' : __( 'n/a', 'buddyboss-platform' );

		$note = sprintf(
			'<p><strong>BuddyBoss Support Access</strong> enabled for %1$s.</p><p>Expires: <strong>%2$s</strong></p>',
			esc_html( home_url( '/' ) ),
			esc_html( $expiry_utc )
		);

		// Embed the caller-supplied login URL when present. Rendered as a plain
		// anchor so support can click straight through.
		if ( '' !== $login_url ) {
			$note .= sprintf(
				'<p>Login URL: <a href="%1$s">%1$s</a></p>',
				esc_url( $login_url )
			);
		}

		// Notify FIRST. The ticket is persisted ONLY if the note posts
		// successfully — a failed note means the ticket is NOT saved, so the
		// admin can retry without an orphaned, un-notified ticket lingering.
		$notify = $this->notify_support_system( $ticket_number, $note );

		if ( is_wp_error( $notify ) ) {
			return array(
				'tickets' => $tickets,
				'added'   => false,
				'notify'  => $notify,
			);
		}

		// Note posted — now persist the ticket. The append runs under the lock
		// so a concurrent login-audit write (or another add) can't clobber it,
		// and it re-reads inside the lock to avoid acting on a stale snapshot.
		$saved = $this->save_locked(
			function ( $data ) use ( $ticket_number ) {
				if ( in_array( $ticket_number, $data['ticket_numbers'], true ) ) {
					// Already present (a racing add beat us) — skip the write.
					return null;
				}
				$data['ticket_numbers'][] = $ticket_number;

				return $data;
			}
		);

		// save_locked() returns the saved snapshot, or null when nothing changed
		// (the ticket was already present). Either way, re-read the authoritative
		// list for the response.
		$tickets = is_array( $saved ) ? $saved['ticket_numbers'] : $this->get_data()['ticket_numbers'];

		return array(
			'tickets' => $tickets,
			'added'   => true,
			'notify'  => $notify,
		);
	}

	/**
	 * Whether a notification for this conversation is allowed right now.
	 *
	 * Suppresses repeat notifications for the same conversation inside
	 * SUPPORT_SYSTEM_RATE_WINDOW seconds, so rapid clicks or a retry loop cannot
	 * hammer the gateway / helpdesk. Returns true (and arms the window) when the
	 * call is allowed; false when it should be skipped.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param int $conversation_id support system conversation ID.
	 *
	 * @return bool True if the notification may proceed.
	 */
	private function notify_rate_ok( $conversation_id ) {
		$key = 'bb_sa_notify_' . (int) $conversation_id;

		if ( false !== get_transient( $key ) ) {
			return false;
		}

		set_transient( $key, 1, self::SUPPORT_SYSTEM_RATE_WINDOW );

		return true;
	}

	/**
	 * Release the per-conversation notify rate-limit window.
	 *
	 * Called when a dispatch fails so a genuine failure (timeout, gateway
	 * error, not-found) can be retried immediately, instead of the admin seeing
	 * the misleading "a note was just sent" message for a note that never
	 * arrived. The window only exists to dedupe successfully-sent notes and
	 * rapid double-clicks while a request is in flight.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param int $conversation_id Support system conversation ID.
	 *
	 * @return void
	 */
	private function notify_rate_release( $conversation_id ) {
		delete_transient( 'bb_sa_notify_' . (int) $conversation_id );
	}

	/**
	 * Get the shared gateway gate-pass key.
	 *
	 * Returns the immutable class constant directly. There is intentionally NO
	 * constant override and NO filter — the key cannot be changed at runtime by
	 * wp-config, another plugin, or any hook. The matching value is configured
	 * on the API Gateway. NOT the support system API token.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return string The gate-pass key.
	 */
	private function gateway_key() {
		return self::SUPPORT_SYSTEM_GATEWAY_KEY;
	}

	/**
	 * Add an internal note to a support system conversation via the AWS API Gateway.
	 *
	 * The gateway endpoint is built from the immutable SUPPORT_SYSTEM_API_BASE class
	 * constant plus the integer conversation ID — it is intentionally NOT
	 * filterable or overridable, so nothing in the request can redirect where
	 * the note is sent. The gateway's Lambda holds the support system API token; this
	 * plugin transmits no secret.
	 *
	 * Guardrails (all plugin-side, defensive):
	 *  - Reject a non-positive conversation ID before any request.
	 *  - Rate-limit per conversation so repeats inside the window are dropped.
	 *  - Short timeout + fail-soft: a slow/failed gateway never blocks the admin
	 *    action. This method runs AFTER the option lock is released, so a
	 *    blocking call cannot hold the mutex either.
	 *  - Payload size cap, TLS-only (the base URL is https), no redirects.
	 *
	 * Maps the gateway response to a result the AJAX layer surfaces as a toast:
	 *  - HTTP 201            -> success, with thread_id.
	 *  - HTTP 404            -> WP_Error 'conversation_not_found'.
	 *  - anything else / err -> WP_Error 'support_system_api_error'.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param int|string $conversation_id   support system conversation ID.
	 * @param string     $text              Note text (may contain HTML).
	 * @param bool       $bypass_rate_limit Optional. Skip the per-conversation
	 *                                      rate limit for deliberate, infrequent
	 *                                      actions (e.g. a duration change).
	 *                                      Default false.
	 *
	 * @return array|WP_Error Success array on 201, WP_Error otherwise.
	 */
	public function notify_support_system( $conversation_id, $text, $bypass_rate_limit = false ) {
		$conversation_id = (int) $conversation_id;
		$text            = wp_kses_post( (string) $text );

		if ( $conversation_id <= 0 ) {
			return new WP_Error(
				'invalid_conversation_id',
				__( 'A valid ticket (conversation) ID is required.', 'buddyboss-platform' )
			);
		}

		// Rate-limit: drop repeats for the same conversation inside the window.
		// Deliberate admin actions (duration changes) bypass this so support
		// always sees the updated expiry, even right after a ticket-add note.
		if ( ! $bypass_rate_limit && ! $this->notify_rate_ok( $conversation_id ) ) {
			return new WP_Error(
				'rate_limited',
				__( 'A note for this ticket was just sent. Please wait a moment before trying again.', 'buddyboss-platform' )
			);
		}

		// Build the URL from the immutable base + integer ID only. No part of it
		// is filterable or derived from arbitrary input, so the request can never
		// be redirected elsewhere (no SSRF / no override).
		$url = self::SUPPORT_SYSTEM_API_BASE . '/conversations/' . $conversation_id . '/threads';

		// The conversation ID lives in the URL path; the gateway expects only
		// `text` in the body and rejects unexpected fields. Do NOT add
		// conversation_id here.
		$body = wp_json_encode( array( 'text' => $text ) );

		// Defensive payload-size bound. The real payload is small; anything over
		// the cap signals tampering — refuse rather than transmit it.
		if ( ! is_string( $body ) || strlen( $body ) > self::SUPPORT_SYSTEM_MAX_PAYLOAD ) {
			$this->notify_rate_release( $conversation_id );
			return new WP_Error( 'payload_error', __( 'Could not build the note request.', 'buddyboss-platform' ) );
		}

		/**
		 * Fires just before a support system note is dispatched. Read-only signal for
		 * alternative integrations; it cannot alter the destination URL.
		 *
		 * @since BuddyBoss 3.1.0
		 *
		 * @param int    $conversation_id support system conversation ID.
		 * @param string $text            Note text.
		 */
		do_action( 'bb_support_access_notify_support_system', $conversation_id, $text );

		$headers = array(
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);

		// Attach the shared gate-pass key (header only, never in the body) so the
		// gateway can reject requests that did not originate from a BuddyBoss
		// site. Skipped when not yet provisioned. This is NOT the support system token.
		$gateway_key = $this->gateway_key();
		if ( '' !== $gateway_key ) {
			$headers[ self::SUPPORT_SYSTEM_KEY_HEADER ] = $gateway_key;
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout'            => self::SUPPORT_SYSTEM_TIMEOUT,
				'redirection'        => 0,
				'sslverify'          => true,
				'reject_unsafe_urls' => true,
				'blocking'           => true,
				'headers'            => $headers,
				'body'               => $body,
			)
		);

		// Transport failure (timeout, DNS, TLS). Fail soft — the WP_Error is
		// surfaced to the admin as a toast; the ticket itself is already saved.
		if ( is_wp_error( $response ) ) {
			$this->notify_rate_release( $conversation_id );
			return new WP_Error(
				'support_system_request_failed',
				__( 'Could not reach the support system. Please try adding the ticket again.', 'buddyboss-platform' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$data = is_array( $data ) ? $data : array();

		if ( 201 === $code && ! empty( $data['success'] ) ) {
			return array(
				'success'         => true,
				'thread_id'       => isset( $data['thread_id'] ) ? sanitize_text_field( (string) $data['thread_id'] ) : '',
				'conversation_id' => $conversation_id,
			);
		}

		if ( 404 === $code ) {
			$this->notify_rate_release( $conversation_id );
			return new WP_Error(
				'conversation_not_found',
				sprintf(
					/* translators: %d: support system conversation/ticket ID. */
					__( 'Ticket %d was not found in the support system.', 'buddyboss-platform' ),
					$conversation_id
				)
			);
		}

		$message = isset( $data['error'] ) ? sanitize_text_field( (string) $data['error'] ) : '';

		$this->notify_rate_release( $conversation_id );

		return new WP_Error(
			'support_system_api_error',
			'' !== $message ? $message : __( 'The support system rejected the note. Please try again.', 'buddyboss-platform' ),
			array( 'status' => $code )
		);
	}

	/**
	 * Schedule (or reschedule) the expiry-cleanup cron at the grant's expiry time.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param int $expires UTC unix timestamp at which to run cleanup.
	 *
	 * @return void
	 */
	public function schedule_expiry( $expires ) {
		$this->clear_scheduled_expiry();

		$expires = absint( $expires );
		if ( $expires > time() ) {
			wp_schedule_single_event( $expires, self::CRON_HOOK );
		}
	}

	/**
	 * Clear any scheduled expiry-cleanup cron event.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function clear_scheduled_expiry() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		while ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
		}
	}

	/**
	 * Cron callback: revoke access if it has expired.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function cron_expire() {
		$data = $this->get_data();

		if ( ! empty( $data['token_hash'] ) && $data['expires'] <= time() ) {
			$this->disable();
		}
	}

	/**
	 * Handle an incoming support-access login URL on the front end.
	 *
	 * Runs on `init`. Validates the token in constant time, checks expiry, and —
	 * only on success — authenticates the dedicated support user and redirects to
	 * the admin dashboard. Any failure path is silent (no token oracle) and
	 * simply lets the request continue as an anonymous visitor.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function handle_login() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a capability-free public entry point validated by a secret token, not a form submission; a nonce is neither available nor applicable here.
		if ( empty( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See note above.
		$raw_token = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR ] ) );
		if ( '' === $raw_token ) {
			return;
		}

		$data = $this->get_data();

		// Fail closed if there is no active grant.
		if ( empty( $data['token_hash'] ) || empty( $data['support_user_id'] ) ) {
			return;
		}

		// Expired? Revoke and bail (lazy enforcement; cron may not have fired yet).
		if ( $data['expires'] <= time() ) {
			$this->disable();
			return;
		}

		// Timing-safe comparison of the hashed incoming token against the stored hash.
		$incoming_hash = hash( 'sha256', $raw_token );
		if ( ! hash_equals( $data['token_hash'], $incoming_hash ) ) {
			// Failed attempt. Because this path authenticates directly (it does
			// not run wp_signon / the `authenticate` filter chain), login-security
			// plugins never observe these attempts. Fire a dedicated action and
			// arm a lightweight per-IP failure counter so monitoring can alert and
			// repeated guessing is at least recorded. The token's ~380-bit entropy
			// already makes brute force infeasible; this is defence-in-depth.
			$this->record_failed_login();

			/**
			 * Fires when a support-access login is attempted with an invalid token.
			 *
			 * Lets security/monitoring code observe failed attempts that bypass the
			 * normal WordPress login pipeline.
			 *
			 * @since BuddyBoss 3.1.0
			 *
			 * @param string $ip Client IP (REMOTE_ADDR-derived), or '' if unknown.
			 */
			do_action( 'bb_support_access_login_failed', $this->get_client_ip() );

			return;
		}

		$support_user = get_user_by( 'id', $data['support_user_id'] );
		if ( ! $support_user ) {
			return;
		}

		/**
		 * Allow a site to veto a support-access token login.
		 *
		 * This token flow authenticates directly (no `wp_signon` / `authenticate`
		 * filter chain), so 2FA and login-throttling plugins do not gate it by
		 * default. The token's ~380-bit CSPRNG entropy makes this safe as a
		 * default, but sites that *mandate* a second factor for every admin can
		 * return false here to re-impose their policy on this path. Returning a
		 * non-true value aborts the login silently (no token oracle), exactly like
		 * an invalid token.
		 *
		 * @since BuddyBoss 3.1.0
		 *
		 * @param bool    $allow        Whether to allow the support login. Default true.
		 * @param WP_User $support_user The support account about to be authenticated.
		 * @param array   $data         The active grant data (token hash, expiry, etc.).
		 */
		if ( true !== apply_filters( 'bb_support_access_allow_login', true, $support_user, $data ) ) {
			return;
		}

		// Authenticate the support user for this browser.
		wp_set_current_user( $support_user->ID );
		wp_set_auth_cookie( $support_user->ID, false );

		// Audit trail: record the successful login (UTC time + client IP).
		$this->record_login();

		// Announce the login through WordPress's standard `wp_login` action.
		// This path authenticates directly (no wp_signon), so without this hook
		// security/audit/SIEM plugins that watch `wp_login` would never see a
		// support login. NOTE: this token flow deliberately bypasses the
		// `authenticate` filter chain, so 2FA and login-throttling plugins do NOT
		// gate it — the token URL is a 2FA-exempt administrator login by design.
		do_action( 'wp_login', $support_user->user_login, $support_user );

		/**
		 * Fires after a successful support-access login.
		 *
		 * @since BuddyBoss 3.1.0
		 *
		 * @param int $support_user_id The authenticated support user ID.
		 */
		do_action( 'bb_support_access_logged_in', $support_user->ID );

		// Suppress the Referer header on the redirect so the token-bearing URL is
		// never leaked to any third-party resource the destination might load.
		// Sent only on this authenticated redirect response, immediately before
		// the redirect, so it cannot have been emitted earlier in the request.
		if ( ! headers_sent() ) {
			header( 'Referrer-Policy: no-referrer' );
		}

		// Redirect to a clean admin URL so the token never lingers in history/referrer.
		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Verify the AJAX request: capability first (fail fast), then nonce.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	private function verify_request() {
		bb_admin_verify_ajax_request( self::NONCE_ACTION );
	}

	/**
	 * Build the Support Access state payload sent to React.
	 *
	 * Never exposes the stored token hash. The login URL is only present in the
	 * response of the request that just minted a fresh token (enable /
	 * fresh-grant extend); subsequent reads return an empty string because the
	 * raw token is not recoverable from storage.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param string $login_url Freshly minted login URL, if any.
	 *
	 * @return array Response payload.
	 */
	private function build_response( $login_url = '' ) {
		$data    = $this->get_data();
		$active  = $this->is_active();
		$expires = (int) $data['expires'];
		$now     = time();

		// Format the recent login log for display (UTC).
		$log = array();
		foreach ( array_reverse( $data['login_log'] ) as $entry ) {
			if ( empty( $entry['time'] ) ) {
				continue;
			}
			$log[] = array(
				'time' => gmdate( 'Y-m-d H:i:s', (int) $entry['time'] ),
				'ip'   => isset( $entry['ip'] ) ? (string) $entry['ip'] : '',
			);
		}

		return array(
			'enabled'        => $active,
			'expires'        => $expires,
			'expires_utc'    => $expires ? gmdate( 'Y-m-d H:i:s', $expires ) : '',
			'remaining'      => ( $active && $expires > $now ) ? ( $expires - $now ) : 0,
			'ticket_numbers' => array_values( $data['ticket_numbers'] ),
			'support_email'  => self::USER_EMAIL,
			'login_url'      => (string) $login_url,
			'has_login_url'  => '' !== (string) $login_url,
			'login_log'      => $log,
		);
	}

	/**
	 * AJAX: get the current Support Access state.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function ajax_get() {
		$this->verify_request();

		wp_send_json_success( $this->build_response() );
	}

	/**
	 * AJAX: toggle Support Access on or off.
	 *
	 * Enabling mints a fresh token and returns the login URL once. Disabling
	 * deletes the token so the URL is permanently dead (the support user
	 * account is kept).
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function ajax_toggle() {
		$this->verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_request().
		$enabled = isset( $_POST['enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) : '';
		$days    = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : self::DEFAULT_DAYS;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$enable = in_array( $enabled, array( '1', 'true', 'on', 'yes' ), true );

		if ( ! $enable ) {
			$this->disable();
			wp_send_json_success( $this->build_response() );
		}

		$result = $this->enable( $days );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $this->build_response( $result['login_url'] ) );
	}

	/**
	 * AJAX: extend the Support Access window by N days.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function ajax_extend() {
		$this->verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_request().
		$days = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 0;
		// The browser-held login URL (shown once after enable) so the updated
		// note can include it. Never persisted server-side; validated below.
		$login_url = isset( $_POST['login_url'] ) ? esc_url_raw( wp_unslash( $_POST['login_url'] ), array( 'https', 'http' ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Only accept a login URL that points at THIS site's login flow.
		if ( '' !== $login_url && ! $this->is_own_login_url( $login_url ) ) {
			$login_url = '';
		}

		// The token is unchanged by an extend, so the existing URL stays valid;
		// pass the validated URL through so it is echoed back in the response.
		$result = $this->extend( $days, $login_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$response           = $this->build_response( $result['login_url'] );
		$response['notice'] = array(
			'status'  => 'success',
			'message' => __( 'Support access duration updated.', 'buddyboss-platform' ),
		);

		// On a pure extension (existing grant), re-notify every attached ticket
		// with the new expiry so support sees the updated window. A fresh grant
		// (no prior live token) has no tickets yet, so nothing is posted. Prefer
		// the browser-supplied URL; if it is absent (page reloaded / fresh
		// session), rebuild it by decrypting the at-rest token so the expiry note
		// still carries the same login URL.
		if ( ! empty( $result['extended'] ) && ! empty( $result['tickets'] ) ) {
			$note_url = ( '' !== $login_url ) ? $login_url : $this->get_login_url();

			$notify = $this->notify_expiry_update( $result['tickets'], $result['expires'], $note_url );

			if ( $notify['sent'] > 0 && 0 === $notify['failed'] ) {
				$response['notice'] = array(
					'status'  => 'success',
					'message' => __( 'Duration updated and support notified of the new expiry.', 'buddyboss-platform' ),
				);
			} elseif ( $notify['failed'] > 0 ) {
				$response['notice'] = array(
					'status'  => 'error',
					'message' => __( 'Duration updated, but the support system could not be notified for some tickets.', 'buddyboss-platform' ),
				);
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * AJAX: add a support system ticket number to the current grant.
	 *
	 * Tickets accumulate — the same login URL can cover several tickets.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function ajax_set_ticket() {
		$this->verify_request();

		// Reject if there is no live grant. The UI only renders "Add Ticket"
		// while enabled, but a direct AJAX call could otherwise dispatch a note
		// for an expired-but-not-yet-cron-cleaned grant.
		if ( ! $this->is_active() ) {
			wp_send_json_error( array( 'message' => __( 'Support access is not currently active.', 'buddyboss-platform' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by verify_request().
		$ticket = isset( $_POST['ticket_number'] ) ? sanitize_text_field( wp_unslash( $_POST['ticket_number'] ) ) : '';
		// The login URL is held by the browser (shown once after enable) and
		// passed back here so the note can include it — the raw URL is never
		// persisted server-side. Validated below; ignored if it isn't ours.
		$login_url = isset( $_POST['login_url'] ) ? esc_url_raw( wp_unslash( $_POST['login_url'] ), array( 'https', 'http' ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' === $ticket ) {
			wp_send_json_error( array( 'message' => __( 'Ticket number is required.', 'buddyboss-platform' ) ) );
		}

		// The ticket number is a support system conversation ID — must be numeric.
		// Validate up front so a bad value gives an instant, clear error rather
		// than a confusing network round-trip.
		if ( ! ctype_digit( $ticket ) || (int) $ticket <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Ticket number must be a valid numeric ID.', 'buddyboss-platform' ) ) );
		}

		// Only accept a login URL that points at THIS site and carries our query
		// var, so a tampered request can't inject an arbitrary link into the note.
		if ( '' !== $login_url && ! $this->is_own_login_url( $login_url ) ) {
			$login_url = '';
		}

		$result = $this->add_ticket( $ticket, $login_url );
		$notify = $result['notify'];

		// The note must post before the ticket is saved. If it failed, the
		// ticket was NOT persisted — return an error so the admin can retry,
		// and the UI does not show the ticket as added.
		if ( is_wp_error( $notify ) ) {
			wp_send_json_error( array( 'message' => $notify->get_error_message() ) );
		}

		// Note posted and ticket saved. Reflect current state + success toast.
		$response           = $this->build_response();
		$response['notice'] = array(
			'status'  => 'success',
			'message' => __( 'Ticket added and note posted to support.', 'buddyboss-platform' ),
		);

		wp_send_json_success( $response );
	}
}

// Boot the singleton — registers the front-end login, cron, and AJAX hooks.
// Constants are class constants (BB_Support_Access::OPTION, etc.) and are
// intentionally immutable: there are no overridable define()s for these
// security-sensitive values.
BB_Support_Access::instance();
