/**
 * BuddyBoss Admin Settings 2.0 - Side Navigation Component
 *
 * Displays side panels and navigation items in the left sidebar navigation.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { __ } from '@wordpress/i18n';

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
 * @returns {JSX.Element} Side navigation component
 */
export function SideNavigation({ featureId, sidePanels, navItems, currentPanel, onNavigate, onBack }) {
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
		<nav className="bb-admin-side-nav" aria-label={__('Settings Navigation', 'buddyboss')}>
			{/* Back button */}
			<button className="bb-admin-side-nav__back-btn" onClick={handleBackClick}>
				<span className="bb-icons-rl-arrow-left"></span>
				{__('Back to Settings', 'buddyboss')}
			</button>

			{/* Menu list - Side Panels */}
			<ul className="bb-admin-side-nav__list">
				{(sidePanels || []).map((panel) => (
					<li key={panel.id} className="bb-admin-side-nav__item">
						<button
							className={`bb-admin-side-nav__link ${
								currentPanel === panel.id ? 'bb-admin-side-nav__link--active' : ''
							}`}
							onClick={() => handlePanelClick(panel.id)}
							aria-current={currentPanel === panel.id ? 'page' : undefined}
						>
							{panel.icon && (
								<span className="bb-admin-side-nav__icon">
									{ 'dashicon' === panel.icon.type && (
										<span className={`dashicons ${panel.icon.slug || 'dashicons-admin-generic'}`}></span>
									)}
									{ 'font' === panel.icon.type && panel.icon.class && (
										<span className={panel.icon.class}></span>
									)}
									{( 'svg' === panel.icon.type || 'image' === panel.icon.type ) && panel.icon.url && (
										<img src={panel.icon.url} alt={panel.title} className="bb-admin-side-nav__icon-img" />
									)}
								</span>
							)}
							<span className="bb-admin-side-nav__text">{panel.title}</span>
						</button>
					</li>
				))}
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
												<img src={item.icon.url} alt={item.label} className="bb-admin-side-nav__icon-img" />
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
