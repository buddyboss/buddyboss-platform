/**
 * BuddyBoss Integrations marketplace API client.
 *
 * Mirrors the Knowledge Base client (./api.js): every request goes through the
 * same-origin REST proxy (BB_REST_Integrations_Endpoint at
 * `buddyboss/v1/integrations/proxy`) which fetches buddyboss.com server-side —
 * the direct cross-origin path is CORS-blocked when buddyboss.com's LiteSpeed
 * cache replays a cached response without the ACAO header.
 *
 * Two-tier cache: a localStorage cache for instant repeat reads in the same
 * browser, on top of the proxy's 12h server transient shared across all admins.
 * The list/categories use a short TTL because the directory gains items over
 * time; per-integration detail content rarely changes, so it caches longer. The
 * cache key is versioned with the plugin version so a plugin update busts it.
 *
 * @since BuddyBoss [BBVERSION]
 */

const HOUR_IN_MILLIS = 60 * 60 * 1000;
// List + categories: short TTL so newly published integrations surface promptly
// (bounded further by the 12h server transient). Detail content: longer.
const LIST_TTL_MS = HOUR_IN_MILLIS;
const DETAIL_TTL_MS = 3 * 24 * HOUR_IN_MILLIS;

/**
 * Read the localized admin data for this page.
 *
 * Set by bb-admin-integrations-page.php as `bbIntegrationsData` (apiUrl, nonce,
 * version) — a name distinct from the Settings app's `bbAdminData` so the two
 * bundles never read each other's data even if both ever load together.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {Object} Localized data, or an empty object.
 */
const getAdminData = () =>
	( typeof window !== 'undefined' && window.bbIntegrationsData ) || {};

// Versioned with the plugin version so a plugin upgrade fully invalidates the
// client cache (the per-call TTLs above handle routine upstream changes).
const CACHE_PREFIX = `bb_integrations_${ getAdminData().version || '0' }_`;

/**
 * Debounce a function — inlined (rather than imported from the Settings api.js)
 * so the standalone integrations bundle has no dependency on that module.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Function} func Function to debounce.
 * @param {number}   wait Delay in ms.
 * @returns {Function} Debounced wrapper.
 */
export const debounce = (func, wait) => {
	let timeout;
	return function debounced(...args) {
		clearTimeout(timeout);
		timeout = setTimeout(() => func.apply(this, args), wait);
	};
};

/**
 * Read a non-expired item from the localStorage cache.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} cacheKey Cache key.
 * @param {number} maxAgeMs Max age in ms before the entry is treated as stale.
 * @returns {any|null} Cached data, or null when missing/expired/corrupt.
 */
const getFromCache = (cacheKey, maxAgeMs) => {
	try {
		const cachedData = localStorage.getItem(cacheKey);
		if (cachedData) {
			const { timestamp, data } = JSON.parse(cachedData);
			if (Date.now() - timestamp < maxAgeMs) {
				return data;
			}
		}
	} catch (e) {
		// Corrupt entry or localStorage unavailable — treat as a miss.
	}
	return null;
};

/**
 * Write an item to the localStorage cache with a timestamp.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} cacheKey Cache key.
 * @param {any}    data     Data to cache.
 */
const saveToCache = (cacheKey, data) => {
	try {
		localStorage.setItem(cacheKey, JSON.stringify({ timestamp: Date.now(), data }));
	} catch (e) {
		// Quota exceeded or unavailable — caching is best-effort, ignore.
	}
};

/**
 * Clear all integrations marketplace localStorage cache entries.
 *
 * Collect-then-delete in two passes: removing during a `localStorage.key(i)`
 * walk re-indexes the store and silently skips keys.
 *
 * @since BuddyBoss [BBVERSION]
 */
export const clearIntegrationsCache = () => {
	try {
		const keysToRemove = [];
		for (let i = 0; i < localStorage.length; i++) {
			const key = localStorage.key(i);
			if (key && key.startsWith(CACHE_PREFIX)) {
				keysToRemove.push(key);
			}
		}
		keysToRemove.forEach((key) => localStorage.removeItem(key));
	} catch (e) {
		// Nothing to do if localStorage is unavailable.
	}
};

/**
 * Resolve the same-origin proxy URL.
 *
 * Reads `bbIntegrationsData.apiUrl` (set via wp_localize_script to
 * `rest_url( 'buddyboss/v1/' )`). Falls back to the WP-Admin REST root; the
 * controller registers under both `buddyboss/v1` and `bb/v1`, so the fallback
 * still resolves on platform-only installs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {string} Same-origin proxy URL.
 */
const buildProxyUrl = () => {
	const root = getAdminData().apiUrl || '/wp-json/buddyboss/v1/';
	const base = root.endsWith('/') ? root : `${root}/`;
	return `${base}integrations/proxy`;
};

/**
 * Read the WP REST nonce from the localized admin data.
 *
 * Required for authenticated REST calls — without it WP treats the request as
 * unauthenticated and the controller's `manage_options` gate returns 401.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {string} REST nonce, or empty string.
 */
const getRestNonce = () => getAdminData().nonce || '';

/**
 * POST a path-only upstream fragment to the proxy and return the envelope.
 *
 * The server prepends `https://buddyboss.com`, so the client can never
 * influence the egress host. Returns the raw `{ body, headers, status }`
 * envelope. Throws an Error (message = upstream message or HTTP status) on
 * failure — same contract as fetchHelpContent so callers show a generic notice.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string}      path      Path-only fragment (must start with one `/`).
 * @param {AbortSignal} [signal]  Optional AbortController signal.
 * @returns {Promise<Object>} Proxy envelope `{ body, headers, status }`.
 */
const proxyFetch = async (path, signal) => {
	const response = await fetch(buildProxyUrl(), {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			Accept: 'application/json',
			'Content-Type': 'application/json',
			'X-WP-Nonce': getRestNonce(),
		},
		body: JSON.stringify({ url: path }),
		signal,
	});

	if (!response.ok) {
		let detail = `HTTP ${response.status}`;
		try {
			const errBody = await response.json();
			if (errBody && typeof errBody.message === 'string' && errBody.message) {
				detail = errBody.message;
			}
		} catch (e) {
			// Body wasn't JSON — keep the HTTP status detail.
		}
		throw new Error(`Failed to fetch integrations (${detail})`);
	}

	return response.json();
};

/**
 * Build a `/wp-json/wp/v2/integrations` query path from listing params.
 *
 * Filtering, search and pagination are all done server-side via REST params so
 * the listing never over-fetches (the directory has 100+ items).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} params Listing params.
 * @returns {string} Path-only fragment with encoded query string.
 */
const buildListPath = ({ page = 1, perPage = 20, search = '', category = 0 } = {}) => {
	const query = [
		`page=${encodeURIComponent(page)}`,
		`per_page=${encodeURIComponent(perPage)}`,
		// Light field set for the grid; the detail drawer fetches content.rendered separately.
		'_fields=id,slug,title,short_description,logo_image_url,collection_name,template,integrations_category,integrations_collection,integrations_require,link,link_url',
	];
	if (search) {
		query.push(`search=${encodeURIComponent(search)}`);
	}
	if (category) {
		query.push(`integrations_category=${encodeURIComponent(category)}`);
	}
	return `/wp-json/wp/v2/integrations?${query.join('&')}`;
};

/**
 * Fetch a page of integrations.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}      [params]        Listing params (page, perPage, search, category).
 * @param {AbortSignal} [params.signal] Optional AbortController signal.
 * @returns {Promise<{items: Array, total: number, totalPages: number}>}
 */
export const fetchIntegrations = async (params = {}) => {
	const { signal, ...listParams } = params;
	const path = buildListPath(listParams);
	const cacheKey = `${CACHE_PREFIX}list_${path}`;

	const cached = getFromCache(cacheKey, LIST_TTL_MS);
	if (cached) {
		return cached;
	}

	try {
		const envelope = await proxyFetch(path, signal);
		const items = Array.isArray(envelope?.body) ? envelope.body : [];
		const headers = envelope?.headers || {};
		const result = {
			items,
			total: parseInt(headers['x-wp-total'], 10) || items.length,
			totalPages: parseInt(headers['x-wp-totalpages'], 10) || 1,
		};
		saveToCache(cacheKey, result);
		return result;
	} catch (error) {
		// Abort is an expected cancellation, not an error to surface.
		if (error && error.name === 'AbortError') {
			throw error;
		}
		// eslint-disable-next-line no-console
		console.error('Error fetching integrations:', error.message || error);
		throw error;
	}
};

/**
 * Fetch all integration categories for the filter dropdown.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {AbortSignal} [signal] Optional AbortController signal.
 * @returns {Promise<Array<{id: number, name: string, slug: string, count: number}>>}
 */
export const fetchIntegrationCategories = async (signal) => {
	const path = '/wp-json/wp/v2/integrations_category?per_page=100&_fields=id,name,slug,count&orderby=name&order=asc';
	const cacheKey = `${CACHE_PREFIX}categories`;

	const cached = getFromCache(cacheKey, LIST_TTL_MS);
	if (cached) {
		return cached;
	}

	try {
		const envelope = await proxyFetch(path, signal);
		const categories = Array.isArray(envelope?.body) ? envelope.body : [];
		saveToCache(cacheKey, categories);
		return categories;
	} catch (error) {
		if (error && error.name === 'AbortError') {
			throw error;
		}
		// eslint-disable-next-line no-console
		console.error('Error fetching integration categories:', error.message || error);
		throw error;
	}
};

/**
 * Fetch a single integration's full record (incl. content.rendered) for the
 * detail drawer. Looked up by slug so the grid only needs the slug to open it.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string}      slug     Integration post slug.
 * @param {AbortSignal} [signal] Optional AbortController signal.
 * @returns {Promise<Object|null>} The integration record, or null when not found.
 */
export const fetchIntegrationBySlug = async (slug, signal) => {
	if (!slug || typeof slug !== 'string') {
		throw new Error('Integration slug is required');
	}
	const path =
		`/wp-json/wp/v2/integrations?slug=${encodeURIComponent(slug)}` +
		'&_fields=id,slug,title,short_description,content,logo_image_url,collection_name,template,integrations_category,integrations_collection,integrations_require,link,link_url';
	const cacheKey = `${CACHE_PREFIX}detail_${slug}`;

	const cached = getFromCache(cacheKey, DETAIL_TTL_MS);
	if (cached) {
		return cached;
	}

	try {
		const envelope = await proxyFetch(path, signal);
		const item = Array.isArray(envelope?.body) && envelope.body.length ? envelope.body[0] : null;
		if (item) {
			saveToCache(cacheKey, item);
		}
		return item;
	} catch (error) {
		if (error && error.name === 'AbortError') {
			throw error;
		}
		// eslint-disable-next-line no-console
		console.error('Error fetching integration detail:', error.message || error);
		throw error;
	}
};
