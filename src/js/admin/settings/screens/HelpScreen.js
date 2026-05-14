/**
 * BuddyBoss Admin Settings 2.0 - Help Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Help Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {JSX.Element} Help screen.
 */
export function HelpScreen() {
	var searchState = useState( '' );
	var searchQuery = searchState[ 0 ];
	var setSearchQuery = searchState[ 1 ];

	return (
		<div className="bb-admin-help-screen">
			<section className="bb-admin-help-hero" aria-labelledby="bb-admin-help-hero-title">
				<div className="bb-admin-help-hero__intro">
					<h1 id="bb-admin-help-hero-title" className="bb-admin-help-hero__title">
						{ __( 'Need Help?', 'buddyboss' ) }
					</h1>
					<p className="bb-admin-help-hero__subtitle">
						{ __( 'Search our help guides, chat with us, or send us a message.', 'buddyboss' ) }
					</p>
				</div>

				<div className="bb-admin-help-hero__search" role="search">
					<label htmlFor="bb-admin-help-hero-search" className="screen-reader-text">
						{ __( 'Search help guides', 'buddyboss' ) }
					</label>
					<i
						className="bb-icons-rl bb-icons-rl-magnifying-glass bb-admin-help-hero__search-icon"
						aria-hidden="true"
					></i>
					<input
						id="bb-admin-help-hero-search"
						type="search"
						className="bb-admin-help-hero__search-input"
						placeholder={ __( 'Describe your issue', 'buddyboss' ) }
						value={ searchQuery }
						onChange={ function ( e ) {
							setSearchQuery( e.target.value );
						} }
					/>
				</div>
			</section>
			<div className="bb-admin-help-wrapper">
				<div className="bb-admin-help-cards">
					<article className="bb-admin-help-card">
						<i
							className="bb-icons-rl bb-icons-rl-paper-plane-tilt bb-admin-help-card__icon"
							aria-hidden="true"
						></i>
						<div className="bb-admin-help-card__body">
							<h2 className="bb-admin-help-card__title">
								{ __( 'Support Ticket', 'buddyboss' ) }
							</h2>
							<p className="bb-admin-help-card__description">
								{ __( 'Send your request directly to our technical support team – we’re ready to help troubleshoot and guide you.', 'buddyboss' ) }
							</p>
						</div>
						<Button
							variant="secondary"
							className="bb-admin-help-card__action"
						>
							{ __( 'Submit a Ticket', 'buddyboss' ) }
						</Button>
					</article>

					<article className="bb-admin-help-card">
						<i
							className="bb-icons-rl bb-icons-rl-key bb-admin-help-card__icon"
							aria-hidden="true"
						></i>
						<div className="bb-admin-help-card__body">
							<div className="bb-admin-help-card__title-row">
								<h2 className="bb-admin-help-card__title">
									{ __( 'Support Access', 'buddyboss' ) }
								</h2>
								<span className="bb-admin-help-card__status bb-admin-help-card__status--positive">
									<i
										className="bb-icons-rl bb-icons-rl-check-circle"
										aria-hidden="true"
									></i>
									{ __( 'Enabled', 'buddyboss' ) }
								</span>
							</div>
							<p className="bb-admin-help-card__description">
								{ __( 'Allow our support team to securely access your site using temporary credentials to troubleshoot issues.', 'buddyboss' ) }
							</p>
						</div>
						<Button
							variant="secondary"
							className="bb-admin-help-card__action"
						>
							{ __( 'Open Access', 'buddyboss' ) }
						</Button>
					</article>
				</div>

				<section
					className="bb-admin-help-resources"
					aria-labelledby="bb-admin-help-resources-title"
				>
					<h2
						id="bb-admin-help-resources-title"
						className="bb-admin-help-resources__title"
					>
						{ __( 'More Resources', 'buddyboss' ) }
					</h2>
					<div className="bb-admin-help-resources__grid">
						{ [
							{ key: 'platform',        icon: 'gear',          label: __( 'BuddyBoss Platform', 'buddyboss' ) },
							{ key: 'theme',           icon: 'palette',       label: __( 'BuddyBoss Theme', 'buddyboss' ) },
							{ key: 'integrations',    icon: 'plug',          label: __( 'Integrations', 'buddyboss' ) },
							{ key: 'advanced-setup',  icon: 'wrench',        label: __( 'Advanced Setup', 'buddyboss' ) },
							{ key: 'troubleshooting', icon: 'seal-question', label: __( 'Troubleshooting', 'buddyboss' ) },
							{ key: 'customizations',  icon: 'sliders',       label: __( 'Customizations', 'buddyboss' ) },
						].map( function ( item ) {
							var count = 132;
							return (
								<a
									key={ item.key }
									href="#"
									className="bb-admin-help-resource-card"
								>
									<div className="bb-admin-help-resource-card__head">
										<i
											className={ 'bb-icons-rl bb-icons-rl-' + item.icon + ' bb-admin-help-resource-card__icon' }
											aria-hidden="true"
										></i>
										<span className="bb-admin-help-resource-card__title">
											{ item.label }
										</span>
									</div>
									<span className="bb-admin-help-resource-card__count">
										{
											/* translators: %d is the number of articles in this resource category. */
											sprintf( _n( '%d article', '%d articles', count, 'buddyboss' ), count )
										}
									</span>
								</a>
							);
						} ) }
					</div>
				</section>
			</div>
		</div>
	);
}

export default HelpScreen;
