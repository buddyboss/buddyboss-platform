/**
 * BuddyBoss Admin Settings 2.0 - Tools Screen
 *
 * Top-level "Tools" page reached via the BuddyBoss → Tools menu entry.
 * Renders inside the standard Settings 2.0 feature-settings shell
 * (`.bb-admin-feature-settings` chrome + `SideNavigation`) so the visual
 * frame matches every other panel-based screen — Reactions, Profile
 * Fields, Notifications, etc. Tools is a sibling of Settings (reached
 * via its own menu entry), not a feature card on the grid.
 *
 * Three panels: Default Data, Repair Platform, Import Content. Each
 * panel wraps a legacy Tools area (default-data importer,
 * `bp_admin_repair_list` callbacks, `BBP_Converter`) via a thin AJAX
 * adapter (`bb_admin_tools_*`) — backend logic is unchanged.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect, useMemo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { SideNavigation } from './SideNavigation';
import { DefaultDataPanel } from '../components/tools/DefaultDataPanel';
import { RepairPlatformPanel } from '../components/tools/RepairPlatformPanel';
import { ImportContentPanel } from '../components/tools/ImportContentPanel';

/**
 * Tools side-panel definitions. Shape matches the `sidePanels` array
 * `SideNavigation` expects (`id`, `title`, `icon: { type, class }`) so
 * the existing nav component renders them without any wiring changes.
 */
var TOOLS_PANELS = [
	{
		id:    'default_data',
		title: __( 'Default Data', 'buddyboss' ),
		icon:  { type: 'font', class: 'bb-icons-rl bb-icons-rl-database' },
	},
	{
		id:    'repair_platform',
		title: __( 'Repair Platform', 'buddyboss' ),
		icon:  { type: 'font', class: 'bb-icons-rl bb-icons-rl-wrench' },
	},
	{
		id:    'import_content',
		title: __( 'Import Content', 'buddyboss' ),
		icon:  { type: 'font', class: 'bb-icons-rl bb-icons-rl-upload-simple' },
	},
];

/**
 * Tools Screen
 *
 * @param {Object}   props
 * @param {string}   props.panelId    Active panel id from the URL.
 * @param {Function} props.onNavigate Router navigation callback.
 * @returns {JSX.Element}
 */
function ToolsScreen( { panelId, onNavigate } ) {
	// Default to the first panel when the URL omits `panel=...`. Router
	// already redirects bare `?page=bb-settings&tab=tools` via the legacy
	// bp-tools redirect, but a hand-typed URL may still arrive without one.
	var activePanelId = useMemo( function () {
		var requested = ( panelId || '' ).toLowerCase();
		var match = TOOLS_PANELS.filter( function ( p ) { return p.id === requested; } );
		return match.length > 0 ? match[ 0 ].id : TOOLS_PANELS[ 0 ].id;
	}, [ panelId ] );

	useEffect( function () {
		var match = TOOLS_PANELS.filter( function ( p ) { return p.id === activePanelId; } );
		var label = match.length > 0 ? match[ 0 ].title : __( 'Tools', 'buddyboss' );
		document.title = label + ' — ' + __( 'BuddyBoss Tools', 'buddyboss' );
	}, [ activePanelId ] );

	var handleBack = useCallback( function () {
		onNavigate( '/settings' );
	}, [ onNavigate ] );

	return (
		<div className="bb-admin-feature-settings bb-admin-tools">
			<div className="bb-admin-feature-settings__container">
				<aside className="bb-admin-feature-settings__sidebar">
					<SideNavigation
						featureId="tools"
						sidePanels={ TOOLS_PANELS }
						currentPanel={ activePanelId }
						onNavigate={ onNavigate }
						onBack={ handleBack }
					/>
				</aside>

				<main className="bb-admin-feature-settings__main">
					<div className="bb-admin-feature-settings__content-wrap">
						<div className="bb-admin-feature-settings__content">
							{ 'default_data' === activePanelId && (
								<DefaultDataPanel />
							) }
							{ 'repair_platform' === activePanelId && (
								<RepairPlatformPanel />
							) }
							{ 'import_content' === activePanelId && (
								<ImportContentPanel />
							) }
						</div>
					</div>
				</main>
			</div>
		</div>
	);
}

/**
 * Temporary placeholder for panels not yet implemented in Phase 1.
 * Will be replaced by the real panel components in Phase 2 / Phase 3.
 *
 * @param {Object} props
 * @param {string} props.title    Panel name.
 * @param {string} props.message  Body copy.
 * @returns {JSX.Element}
 */
function ToolsPlaceholderPanel( { title, message } ) {
	return (
		<section className="bb-admin-tools__panel bb-admin-tools__panel--placeholder">
			<header className="bb-admin-tools__panel-header">
				<h1 className="bb-admin-tools__panel-title">{ title }</h1>
			</header>
			<div className="bb-admin-tools__panel-body">
				<p>{ message }</p>
			</div>
		</section>
	);
}

export default ToolsScreen;
