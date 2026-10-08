/**
 * BuddyBoss Admin Settings 2.0 - Side Navigation Component
 *
 * Displays side panels and navigation items in the left sidebar navigation.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { safeUrl } from '../utils/sanitize';
import { evaluateConditional } from '../utils/conditional';

/**
 * Side Navigation Component
 *
 * @param {Object} props Component props
 * @param {string} props.featureId Feature ID
 * @param {Array} props.sidePanels Side panels array
 * @param {Array} props.navItems Navigation items array (e.g., "All Activities")
 * @param {string} props.currentPanel Current panel ID
 * @param {Function} props.onNavigate Navigation callback
 * @param {Function} props.onBack Back button callback
 * @param {Object} [props.formValues] Live form values for conditional panel visibility.
 * @returns {JSX.Element} Side navigation component
 */
export function SideNavigation({ featureId, sidePanels, navItems, currentPanel, onNavigate, onBack, formValues }) {
	// Filter out panels whose `conditional` arg evaluates to false against the
	// live form state. Memoized so the returned array has a stable reference
	// when neither dep changes — prevents unnecessary child-component
	// reconciliation on every parent render.
	const visibleSidePanels = useMemo( function () {
		return ( sidePanels || [] ).filter( function ( panel ) {
			return evaluateConditional( panel.conditional, formValues || {} );
		} );
	}, [ sidePanels, formValues ] );
	const handlePanelClick = (panelId) => {
		if ( 'function' === typeof onNavigate ) {
			onNavigate(`/settings/${featureId}/${panelId}`);
		}
	};

	const handleNavItemClick = (route) => {
		if ( 'function' === typeof onNavigate ) {
			onNavigate(route);
		}
	};

	const handleBackClick = () => {
		if ( 'function' === typeof onBack ) {
			onBack();
		}
	};

	return (
		<nav className="bb-admin-side-nav" aria-label={__('Settings Navigation', 'buddyboss-platform')}>
			{/* Back button */}
			<button className="bb-admin-side-nav__back-btn" onClick={handleBackClick}>
				<span className="bb-icons-rl-arrow-left"></span>
				{__('Back to Settings', 'buddyboss-platform')}
			</button>

			{/* Menu list - Side Panels */}
			<ul className="bb-admin-side-nav__list">
				{visibleSidePanels.map((panel) => {
					// Link-out variant: a panel with `external_url` is not an
					// internal SPA route — it's a standalone link to another
					// admin page (or another feature's settings URL). Render
					// as an anchor so middle-click / ctrl-click still work,
					// and flag it visually with an up-right arrow icon on the
					// trailing edge so the user knows they're leaving this
					// feature's context. Clicking an `<a>` is inherently a
					// navigation — no JS handler needed, no active state.
					const isExternal = !! panel.external_url;

					const iconEl = panel.icon && (
						<span className="bb-admin-side-nav__icon">
							{ 'dashicon' === panel.icon.type && (
								<span className={`dashicons ${panel.icon.slug || 'dashicons-admin-generic'}`}></span>
							)}
							{ 'font' === panel.icon.type && panel.icon.class && (
								<span className={panel.icon.class}></span>
							)}
							{( 'svg' === panel.icon.type || 'image' === panel.icon.type ) && panel.icon.url && (
								<img src={safeUrl( panel.icon.url )} alt={panel.title} className="bb-admin-side-nav__icon-img" />
							)}
						</span>
					);

					return (
						<li key={panel.id} className="bb-admin-side-nav__item">
							{ panel.divider && (
								<div className="bb-admin-side-nav__divider"></div>
							) }
							{ isExternal ? (
								<a
									className="bb-admin-side-nav__link bb-admin-side-nav__link--external"
									href={ safeUrl( panel.external_url ) }
								>
									{ iconEl }
									<span className="bb-admin-side-nav__text">{panel.title}</span>
									<span className="bb-admin-side-nav__external-indicator" aria-hidden="true">
										<span className="bb-icons-rl bb-icons-rl-arrow-up-right"></span>
									</span>
								</a>
							) : (
								<button
									className={`bb-admin-side-nav__link ${
										currentPanel === panel.id ? 'bb-admin-side-nav__link--active' : ''
									}`}
									onClick={() => handlePanelClick(panel.id)}
									aria-current={currentPanel === panel.id ? 'page' : undefined}
								>
									{ iconEl }
									<span className="bb-admin-side-nav__text">{panel.title}</span>
								</button>
							) }
						</li>
					);
				})}
			</ul>

			{/* Navigation Items (e.g., "All Activities", "All Groups") */}
			{navItems && navItems.length > 0 && (
				<>
					<div className="bb-admin-side-nav__divider"></div>
					<ul className="bb-admin-side-nav__list bb-admin-side-nav__list--nav-items">
						{navItems.map((item) => (
							<li key={item.id} className="bb-admin-side-nav__item">
								<button
									className={`bb-admin-side-nav__link bb-admin-side-nav__link--nav-item ${
										currentPanel === item.id ? 'bb-admin-side-nav__link--active' : ''
									}`}
									onClick={() => handleNavItemClick(item.route)}
									aria-current={currentPanel === item.id ? 'page' : undefined}
								>
									{item.icon && (
										<span className="bb-admin-side-nav__icon">
											{ 'string' === typeof item.icon && (
												<span className={`dashicons ${item.icon || 'dashicons-list-view'}`}></span>
											)}
											{ 'object' === typeof item.icon && 'dashicon' === item.icon.type && (
												<span className={`dashicons ${item.icon.slug || 'dashicons-list-view'}`}></span>
											)}
											{ 'object' === typeof item.icon && 'font' === item.icon.type && item.icon.class && (
												<span className={item.icon.class}></span>
											)}
											{ 'object' === typeof item.icon && ( 'svg' === item.icon.type || 'image' === item.icon.type ) && item.icon.url && (
												<img src={safeUrl( item.icon.url )} alt={item.label} className="bb-admin-side-nav__icon-img" />
											)}
										</span>
									)}
									<span className="bb-admin-side-nav__text">{item.label}</span>
								</button>
							</li>
						))}
					</ul>
				</>
			)}
		</nav>
	);
}
