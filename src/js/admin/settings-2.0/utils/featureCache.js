/**
 * BuddyBoss Admin Settings 2.0 - Feature Data Cache
 *
 * Simple in-memory cache for feature settings data to prevent
 * re-fetching on navigation between screens within the same feature.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// In-memory cache storage
const cache = {};

/**
 * Get cached feature data.
 *
 * @param {string} featureId Feature ID
 * @returns {Object|null} Cached data or null
 */
export function getCachedFeatureData(featureId) {
	if (cache[featureId] && cache[featureId].data) {
		return cache[featureId].data;
	}
	return null;
}

/**
 * Set cached feature data.
 *
 * @param {string} featureId Feature ID
 * @param {Object} data Feature data (side_panels, navigation, settings)
 */
export function setCachedFeatureData(featureId, data) {
	cache[featureId] = {
		data,
		timestamp: Date.now(),
	};
}

/**
 * Check if cache exists for a feature.
 *
 * @param {string} featureId Feature ID
 * @returns {boolean} True if cache exists
 */
export function hasCachedFeatureData(featureId) {
	return !!(cache[featureId] && cache[featureId].data);
}

/**
 * Invalidate cache for a feature.
 *
 * @param {string} featureId Feature ID (optional, clears all if not provided)
 */
export function invalidateFeatureCache(featureId = null) {
	if (featureId) {
		delete cache[featureId];
	} else {
		Object.keys(cache).forEach((key) => delete cache[key]);
	}
}

/**
 * Get the sidebar data (side panels + nav items) from cache.
 *
 * @param {string} featureId Feature ID
 * @returns {Object|null} Object with sidePanels and navItems or null
 */
export function getCachedSidebarData(featureId) {
	const data = getCachedFeatureData(featureId);
	if (data) {
		return {
			sidePanels: data.side_panels || [],
			navItems: data.navigation || [],
		};
	}
	return null;
}
