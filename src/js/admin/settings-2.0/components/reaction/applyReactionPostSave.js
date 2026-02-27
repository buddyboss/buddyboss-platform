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
 * @param {Object} response Save API response (response.data may have migration_data, migration_status)
 * @param {Object} fieldsToSave The payload that was saved (e.g. { reaction_items, reaction_checks, bb_reaction_mode })
 * @param {string} featureId Feature ID (used for refetch and cache keys)
 * @param {Object} context Helpers: ajaxFetch, getCachedFeatureData, setCachedFeatureData, setFeature, setSidePanels, setSettings, setOriginalSettings
 */
export function applyReactionPostSave(response, fieldsToSave, featureId, context) {
	if ( process.env.NODE_ENV !== 'production' ) {
		const requiredContextKeys = [ 'ajaxFetch', 'getCachedFeatureData', 'setCachedFeatureData', 'setFeature', 'setSidePanels', 'setSettings', 'setOriginalSettings' ];
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
			context.setCachedFeatureData(featureId, updatedData);
			context.setFeature(updatedData);
			context.setSidePanels(updatedData.side_panels || []);
			const freshSettings = updatedData.settings || {};
			context.setSettings(freshSettings);
			context.setOriginalSettings(freshSettings);
		});
		return;
	}

	if (hasMigrationResponse) {
		// Mode-only change - inject migration data (or clear if empty), no refetch.
		const inject = (panels) => injectMigrationDataIntoPanels(panels, saveMigrationData || {}, saveMigrationStatus);
		context.setSidePanels((prev) => inject(prev));
		context.setFeature((prev) => {
			if (!prev) return prev;
			const updatedPanels = inject(prev.side_panels || []);
			const cachedData = context.getCachedFeatureData(featureId);
			if (cachedData) {
				context.setCachedFeatureData(featureId, {
					...cachedData,
					side_panels: updatedPanels,
					settings: { ...cachedData.settings, ...fieldsToSave },
				});
			}
			return { ...prev, side_panels: updatedPanels };
		});
		// Keep local state in sync with saved values. Refetch for reactions no longer
		// replaces settings (it only updates panels), so this merge is still correct.
		context.setSettings((prev) => ({ ...prev, ...fieldsToSave }));
		context.setOriginalSettings((prev) => ({ ...prev, ...fieldsToSave }));
		return;
	}

	// Fallback: no reaction_items save and no migration_data (e.g. user saved bb_reaction_mode with same value).
	// Keep originalSettings and cache in sync with what was sent so UI and cache stay consistent.
	context.setOriginalSettings((prev) => ({ ...prev, ...fieldsToSave }));
	const cachedData = context.getCachedFeatureData(featureId);
	if (cachedData) {
		context.setCachedFeatureData(featureId, {
			...cachedData,
			settings: { ...cachedData.settings, ...fieldsToSave },
		});
	}
}
