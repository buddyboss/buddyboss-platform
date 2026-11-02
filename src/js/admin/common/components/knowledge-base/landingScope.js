/**
 * Resolve which parent-id the Landing grid should show cards under, given the
 * requested rootCategory slug. Unset or not-found → 0 (the full-KB top level),
 * so a missing/typo/placeholder root never blanks Documentation (H4/R5).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}   terms        Flat ht-kb-category list.
 * @param {?string} rootCategory Requested root slug (may be null/empty).
 * @return {number} Parent term id whose children become the Landing cards.
 */

// Track slugs already warned about so the misconfiguration signal fires once
// per unique bad slug rather than on every KBLanding useMemo recompute.
const warnedRoots = new Set();

export function resolveRootParentId( terms, rootCategory ) {
	if ( ! rootCategory ) {
		return 0;
	}
	if ( Array.isArray( terms ) ) {
		for ( const t of terms ) {
			if ( t.slug === rootCategory ) {
				return t.id;
			}
		}
	}
	// Not found — fall back to the full KB rather than an empty grid, and warn
	// once per unique slug so a typo/placeholder root is discoverable without
	// spamming the console on every re-render.
	if ( ! warnedRoots.has( rootCategory ) ) {
		warnedRoots.add( rootCategory );
		// eslint-disable-next-line no-console
		console.warn( '[bb-kb] rootCategory not found in taxonomy, showing full KB:', rootCategory );
	}
	return 0;
}
