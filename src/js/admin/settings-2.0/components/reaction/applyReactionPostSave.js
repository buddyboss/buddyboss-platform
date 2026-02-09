/**
 * BuddyBoss Admin Settings 2.0 - Reaction post-save handler
 *
 * Handles reactions-specific behavior after bb_admin_save_feature_settings:
 * refetch when reaction_items were saved (to get real DB IDs), or inject
 * migration data when only mode/migration changed.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
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
	const savedReactionItems = fieldsToSave.reaction_items !== undefined;
	const saveMigrationData = response.data?.migration_data;
	const saveMigrationStatus = response.data?.migration_status || '';
	const hasMigrationData = !!saveMigrationData || !!response.data?.migration_status;

	if (savedReactionItems) {
		// Refetch to get real DB IDs replacing react_key_ IDs
		context.ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId }).then((featureResponse) => {
			if (!featureResponse.success || !featureResponse.data) {
				return;
			}
			let updatedData = featureResponse.data;
			if (saveMigrationData) {
				updatedData = {
					...updatedData,
					side_panels: injectMigrationDataIntoPanels(
						updatedData.side_panels,
						saveMigrationData,
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

	if (hasMigrationData) {
		// Mode-only change with migration data - inject directly, no refetch
		const inject = (panels) => injectMigrationDataIntoPanels(panels, saveMigrationData, saveMigrationStatus);
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
