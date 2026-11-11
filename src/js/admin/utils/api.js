const CACHE_DURATION_DAYS = 3;
const CACHE_DURATION_IN_MILLIS = CACHE_DURATION_DAYS * 24 * 60 * 60 * 1000;

/**
 * Retrieves an item from localStorage cache if it's not expired.
 * @param {string} cacheKey - The key for the cache item.
 * @returns {any|null} The cached data or null if not found or expired.
 */
const getFromCache = (cacheKey) => {
	const cachedData = localStorage.getItem(cacheKey);
	if (cachedData) {
		const { timestamp, data } = JSON.parse(cachedData);
		const now = new Date().getTime();
		if (now - timestamp < CACHE_DURATION_IN_MILLIS) {
			return data;
		}
	}
	return null;
};

/**
 * Saves an item to the localStorage cache with a timestamp.
 * @param {string} cacheKey - The key for the cache item.
 * @param {any} data - The data to cache.
 */
const saveToCache = (cacheKey, data) => {
	const cacheValue = {
		timestamp: new Date().getTime(),
		data: data,
	};
	localStorage.setItem(cacheKey, JSON.stringify(cacheValue));
};

/**
 * Clears the help content cache for a specific content ID or all help content.
 * @param {string} [contentId] - Optional content ID or URL (with article=). If not provided, clears all help content cache.
 */
export const clearHelpContentCache = (contentId = null) => {
	if (contentId) {
		const kbId = resolveHelpContentId(contentId);
		if (kbId) {
			localStorage.removeItem(`bb_help_content_${kbId}`);
		}
	} else {
		// Collect-then-delete in two passes. `localStorage.removeItem()`
		// reindexes the storage on the spot, so removing during a
		// `localStorage.key(i)` walk silently skips every key that lands
		// in the freshly-vacated slot — leaving stale entries behind on
		// any flush that touches more than one matching key in a row.
		const keysToRemove = [];
		for (let i = 0; i < localStorage.length; i++) {
			const key = localStorage.key(i);
			if (key && key.startsWith('bb_help_content_')) {
				keysToRemove.push(key);
			}
		}
		keysToRemove.forEach((key) => localStorage.removeItem(key));
	}
};

/**
 * Creates a debounced function that delays invoking func until after wait milliseconds have elapsed
 * since the last time the debounced function was invoked.
 *
 * @param {Function} func - The function to debounce.
 * @param {number} wait - The number of milliseconds to delay.
 * @returns {Function} The debounced function.
 */
export const debounce = (func, wait) => {
	let timeout;

	return function executedFunction(...args) {
		const later = () => {
			clearTimeout(timeout);
			func(...args);
		};

		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
	};
};

/**
 * Resolve help content ID from a URL or raw ID.
 * Backend may pass help_url as full URL (e.g. admin.php?page=bp-help&article=127197).
 *
 * @param {string} contentId - URL with article= param or numeric/slug ID.
 * @returns {string} Resolved article ID for the ht-kb API.
 */
const resolveHelpContentId = (contentId) => {
	if (!contentId || typeof contentId !== 'string') {
		return '';
	}
	const trimmed = contentId.trim();

	// Defense in depth: PHP's esc_url_raw() prepends 'http://' to bare numeric
	// strings, so a help_url registered as the bare KB article ID '636101' can
	// arrive here as 'http://636101'. The PHP layer guards against this in
	// bb_sanitize_help_url(), but we strip the synthetic scheme here too so a
	// regression in any caller surfaces as a working article load instead of a
	// 404 to wp/v2/ht-kb/http://<id>.
	const bareIdFromBadScheme = trimmed.match(/^https?:\/\/(\d+)\/?$/i);
	if (bareIdFromBadScheme) {
		return bareIdFromBadScheme[1];
	}

	// Full URL: extract article query param.
	if (trimmed.startsWith('http') || trimmed.includes('?')) {
		try {
			const url = trimmed.startsWith('http') ? trimmed : `https://example.com?${trimmed.split('?')[1] || ''}`;
			const params = new URL(url).searchParams;
			const article = params.get('article');
			if (article) {
				return String(article);
			}
		} catch (e) {
			// Fall through to use trimmed as-is.
		}
	}
	return trimmed;
};

/**
 * Resolve the same-origin help-content proxy URL for an article ID.
 *
 * The proxy lives in this plugin (BB_REST_Help_Content_Endpoint) and
 * fetches `https://buddyboss.com/wp-json/wp/v2/ht-kb/<id>` server-side
 * via `wp_remote_get()`. This avoids the CORS-blocked path the previous
 * direct cross-origin fetch hit when buddyboss.com's LiteSpeed cache
 * served cached responses without an `Access-Control-Allow-Origin`
 * header (the header is added at the PHP layer but stripped on cache
 * replay; see commit message for full diagnosis).
 *
 * Reads `window.bbAdminData.apiUrl` which is set by
 * `bb-admin-settings-page.php` via `wp_localize_script` to
 * `rest_url( 'buddyboss/v1/' )`. Falls back to the WP-Admin REST root
 * if for some reason the localized data is missing — the controller
 * registers the route under both `buddyboss/v1` (with platform-api)
 * and `bb/v1` (without), so the fallback URL still resolves.
 *
 * @param {string} kbId Validated KB article ID (digits only).
 * @returns {string} Same-origin proxy URL.
 */
const buildHelpProxyUrl = () => {
	const root = (typeof window !== 'undefined' && window.bbAdminData && window.bbAdminData.apiUrl)
		? window.bbAdminData.apiUrl
		: '/wp-json/buddyboss/v1/';
	const base = root.endsWith('/') ? root : root + '/';
	return base + 'help-content/proxy';
};

/**
 * Build the upstream KB article path that the PHP proxy will fetch.
 *
 * The proxy validates and prepends `https://buddyboss.com` server-side
 * (see BB_REST_Help_Content_Endpoint::build_proxy_target). Clients send
 * a path-only fragment so they can never influence the egress host.
 *
 * @param {string} kbId Validated KB article ID (digits only).
 * @returns {string} Path fragment under buddyboss.com.
 */
const buildHelpArticlePath = (kbId) => '/wp-json/wp/v2/ht-kb/' + encodeURIComponent(kbId);

/**
 * Read the WP REST nonce from the localized admin data.
 *
 * Required for authenticated REST calls — without it, WP treats the
 * request as unauthenticated and the controller's `manage_options` check
 * fails with HTTP 401 even when the admin is logged in.
 *
 * @returns {string} REST nonce, or empty string if not localized.
 */
const getRestNonce = () => {
	if (typeof window === 'undefined' || !window.bbAdminData) {
		return '';
	}
	return window.bbAdminData.nonce || '';
};

/**
 * Fetch help content via the same-origin REST proxy.
 *
 * Two-tier cache: localStorage for instant repeat reads in the same
 * browser session, plus the server-side transient cache in the proxy
 * controller (12h, shared across all admins on the site). The
 * localStorage cache is keyed by article ID so a render-loop calling
 * `fetchHelpContent` repeatedly for the same article only hits the
 * network once per `CACHE_DURATION_DAYS`.
 *
 * Error handling preserves the previous contract: on any failure the
 * promise rejects with an `Error` whose `message` includes the upstream
 * status / payload context. The `FeatureSettingsScreen` consumer reads
 * `error.message` and shows a generic "couldn't load" notice — no
 * change required there.
 *
 * @param {string} contentId The ID of the help content to fetch, or a URL containing article=.
 * @returns {Promise} Promise that resolves to help content object.
 */
export const fetchHelpContent = async (contentId) => {
	if (!contentId) {
		throw new Error('Content ID is required');
	}

	const kbId = resolveHelpContentId(contentId);
	if (!kbId || !/^\d+$/.test(kbId)) {
		throw new Error('Could not determine help article ID');
	}

	const cacheKey = `bb_help_content_${kbId}`;
	const cached = getFromCache(cacheKey);
	if (cached) {
		return cached;
	}

	try {
		// POST to the universal proxy with a path-only URL body. The
		// server prepends `https://buddyboss.com` so this client can
		// never influence the egress host. The response envelope is
		// `{ body: <upstream JSON>, headers: {...}, status: <int> }` —
		// `body` is the raw `wp/v2/ht-kb/<id>` payload (title.rendered,
		// content.rendered, acf.video_id, acf.featured_image), so we
		// flatten it here on the client.
		const response = await fetch(buildHelpProxyUrl(), {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				Accept: 'application/json',
				'Content-Type': 'application/json',
				'X-WP-Nonce': getRestNonce(),
			},
			body: JSON.stringify({ url: buildHelpArticlePath(kbId) }),
		});

		if (!response.ok) {
			// Try to surface the upstream error code from the WP REST
			// envelope (`{ code, message, data: { status } }`) so the
			// caller's toast can show something meaningful, then fall
			// back to the HTTP status if the body isn't parseable.
			let detail = `HTTP ${response.status}`;
			try {
				const errBody = await response.json();
				if (errBody && typeof errBody.message === 'string' && errBody.message) {
					detail = errBody.message;
				}
			} catch (e) {
				// Ignore — body wasn't JSON.
			}
			throw new Error(`Failed to fetch help content (${detail})`);
		}

		const envelope = await response.json();
		const upstream = envelope && typeof envelope === 'object' && envelope.body && typeof envelope.body === 'object'
			? envelope.body
			: {};
		const titleStr = upstream.title && typeof upstream.title.rendered === 'string' ? upstream.title.rendered : '';
		const contentStr = upstream.content && typeof upstream.content.rendered === 'string' ? upstream.content.rendered : '';
		const videoIdStr = upstream.acf && typeof upstream.acf.video_id === 'string' ? upstream.acf.video_id : '';
		const imageUrlStr = upstream.acf && typeof upstream.acf.featured_image === 'string' ? upstream.acf.featured_image : '';

		const contentObject = {
			title: titleStr,
			content: contentStr,
			videoId: videoIdStr || null,
			imageUrl: imageUrlStr || null,
		};

		saveToCache(cacheKey, contentObject);
		return contentObject;
	} catch (error) {
		// Log with context; rethrow so caller can show user message.
		// eslint-disable-next-line no-console
		console.error('Error fetching help content:', error.message || error);
		throw error;
	}
};

