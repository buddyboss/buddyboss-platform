/**
 * BuddyBoss Admin Settings 2.0 - Reaction post-save handler
 *
 * Handles reactions-specific behavior after bb_admin_save_feature_settings:
 * refetch when reaction_items were saved (to get real DB IDs), or inject
 * migration data when only mode/migration changed.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Injects migration_data and migration_status into reaction_migration / reaction_notice fields.
 *
 * @param {Array} panels Side panels array
 * @param {Object} migrationData Migration data from save response
 * @param {string} migrationStatus Migration status from save response
 * @returns {Array} New panels with migration data injected
 */
function injectMigrationDataIntoPanels(panels, migrationData, migrationStatus) {
	return (panels || []).map((panel) => ({
		...panel,
		sections: (panel.sections || []).map((section) => ({
			...section,
			fields: (section.fields || []).map((f) => {
				if ('reaction_migration' === f.type || 'reaction_notice' === f.type) {
					return { ...f, migration_data: migrationData, migration_status: migrationStatus };
				}
				return f;
			}),
		})),
	}));
}

/**
 * Apply reactions-specific post-save behavior.
 * Call only when featureId === 'reactions'; caller (FeatureSettingsScreen) performs that check.
 *
 * Cache reconciliation runs regardless of supersession or navigation — like
 * every other feature's cache write, it must happen even when the response was
 * superseded or the admin navigated away, or the reactions cache would serve
 * pre-edit values on the next cache-first render. The one exception is the
 * items branch when claimItemsRefetchWrite() returns false: a NEWER save's
 * refetch already wrote fresher data, so this older refetch is discarded whole
 * (writing it would be the bug). Screen state (feature/panels/settings) is
 * applied only when shouldApplyScreen() passes; it is re-evaluated after the
 * async refetch resolves so a navigation during the refetch round-trip is also
 * respected.
 *
 * @since BuddyBoss [BBVERSION] Added the `shouldApplyScreen`, `isLatestSave` and `claimItemsRefetchWrite` parameters.
 *
 * @param {Object}   response          Save API response (response.data may have migration_data, migration_status)
 * @param {Object}   fieldsToSave      The payload that was saved (e.g. { reaction_items, reaction_checks, bb_reaction_mode })
 * @param {string}   featureId         Feature ID (used for refetch and cache keys)
 * @param {Object}   context           Helpers: ajaxFetch, getCachedFeatureData, setCachedFeatureData, invalidateFeatureCache, setFeature, setSidePanels, setSettings, setOriginalSettings
 * @param {Function} shouldApplyScreen Returns true when screen state may be applied (response not superseded, feature still displayed). Defaults to always-true.
 * @param {Function} isLatestSave      Returns true when this save is still the feature's newest dispatch. Gates how the ASYNC refetch writes the cache: the refetch runs outside the single-flight channel, so an earlier save's slower refetch must not overwrite a newer save's cache state. Defaults to always-true.
 * @param {Function} claimItemsRefetchWrite Atomically claims the right to write this items-refetch's result: returns false when a NEWER save's refetch already wrote. The caller backs this with channel-scoped state so its lifetime matches the sequence counter it orders (a module-scoped ledger would outlive the channel across remounts and wrongly reject fresh sessions). Defaults to always-true.
 */
export function applyReactionPostSave(response, fieldsToSave, featureId, context, shouldApplyScreen = () => true, isLatestSave = () => true, claimItemsRefetchWrite = () => true) {
	if ( process.env.NODE_ENV !== 'production' ) {
		const requiredContextKeys = [ 'ajaxFetch', 'getCachedFeatureData', 'setCachedFeatureData', 'invalidateFeatureCache', 'setFeature', 'setSidePanels', 'setSettings', 'setOriginalSettings' ];
		requiredContextKeys.forEach( ( key ) => {
			if ( typeof context[ key ] !== 'function' ) {
				// eslint-disable-next-line no-console
				console.error( `applyReactionPostSave: context.${ key } is missing or not a function` );
			}
		} );
	}

	const savedReactionItems = fieldsToSave.reaction_items !== undefined;
	let saveMigrationData = response.data?.migration_data;
	let saveMigrationStatus = response.data?.migration_status || '';
	// Check if response includes migration fields (even if empty - we need to clear old data).
	const hasMigrationResponse = 'migration_data' in (response.data || {});

	// Normalise "dismissed" status so React behaves like the Pro field callbacks:
	// when migration_data.status is 'dismissed', we should treat it as "no migration"
	// and clear both migration_data and migration_status. This prevents the success
	// notice from reappearing after the user dismisses it and then triggers another save.
	if (saveMigrationData && saveMigrationData.status === 'dismissed') {
		saveMigrationData = {};
		saveMigrationStatus = '';
	}

	if (savedReactionItems) {
		// Refetch to get real DB IDs replacing react_key_ IDs
		context.ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId }).then((featureResponse) => {
			if (!featureResponse.success || !featureResponse.data) {
				return;
			}
			let updatedData = featureResponse.data;
			// Always inject migration data from save response (even if empty to clear old notice).
			if (hasMigrationResponse) {
				updatedData = {
					...updatedData,
					side_panels: injectMigrationDataIntoPanels(
						updatedData.side_panels,
						saveMigrationData || {},
						saveMigrationStatus
					),
				};
			}
			// This refetch runs outside the single-flight channel, so writes
			// must be ordered manually. The claim (backed by channel-scoped
			// state, so it resets with the channel on a full remount) rejects
			// an older items-save's refetch resolving after a newer one wrote.
			if (!claimItemsRefetchWrite()) {
				return;
			}

			// Superseded by a NEWER save (e.g. a mode change, whose branch does
			// no refetch of its own): a full-replace here would clobber that
			// save's cache effects with pre-save server state — but silently
			// discarding the refetch would strand the temporary react_key_
			// item IDs in the cache forever. Land ONLY reaction_items (the
			// data this refetch exists to deliver) on top of the current cache.
			if (!isLatestSave()) {
				const current = context.getCachedFeatureData(featureId);
				if (current && updatedData.settings && undefined !== updatedData.settings.reaction_items) {
					context.setCachedFeatureData(featureId, {
						...current,
						settings: { ...current.settings, reaction_items: updatedData.settings.reaction_items },
					});
				}
				return;
			}
			context.setCachedFeatureData(featureId, updatedData);
			if (!shouldApplyScreen()) {
				return;
			}
			context.setFeature(updatedData);
			context.setSidePanels(updatedData.side_panels || []);
			const freshSettings = updatedData.settings || {};
			context.setSettings(freshSettings);
			context.setOriginalSettings(freshSettings);
		}).catch(() => {
			// The save itself succeeded but this refetch (the ONLY cache write
			// of the items branch) failed — the cache still holds the pre-save
			// reaction items, and the next panel load is cache-first, so it
			// would serve them indefinitely. Drop the feature's cache entry so
			// the next entry fetches fresh from the server instead.
			if (typeof context.invalidateFeatureCache === 'function') {
				context.invalidateFeatureCache(featureId);
			}
		});
		return;
	}

	if (hasMigrationResponse) {
		// Mode-only change - inject migration data (or clear if empty), no refetch.
		const inject = (panels) => injectMigrationDataIntoPanels(panels, saveMigrationData || {}, saveMigrationStatus);

		// Cache: always, and derived from the CACHED panels — not the screen's
		// current feature state, which may belong to another feature when this
		// response arrives after navigation.
		const cachedData = context.getCachedFeatureData(featureId);
		if (cachedData) {
			context.setCachedFeatureData(featureId, {
				...cachedData,
				side_panels: inject(cachedData.side_panels || []),
				settings: { ...cachedData.settings, ...fieldsToSave },
			});
		}

		if (!shouldApplyScreen()) {
			return;
		}
		context.setSidePanels((prev) => inject(prev));
		context.setFeature((prev) => (prev ? { ...prev, side_panels: inject(prev.side_panels || []) } : prev));
		// Keep local state in sync with saved values. Refetch for reactions no longer
		// replaces settings (it only updates panels), so this merge is still correct.
		context.setSettings((prev) => ({ ...prev, ...fieldsToSave }));
		context.setOriginalSettings((prev) => ({ ...prev, ...fieldsToSave }));
		return;
	}

	// Fallback: no reaction_items save and no migration_data (e.g. user saved bb_reaction_mode with same value).
	// Keep originalSettings and cache in sync with what was sent so UI and cache stay consistent.
	const cachedData = context.getCachedFeatureData(featureId);
	if (cachedData) {
		context.setCachedFeatureData(featureId, {
			...cachedData,
			settings: { ...cachedData.settings, ...fieldsToSave },
		});
	}
	if (!shouldApplyScreen()) {
		return;
	}
	context.setOriginalSettings((prev) => ({ ...prev, ...fieldsToSave }));
}
