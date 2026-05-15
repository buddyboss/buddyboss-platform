/**
 * BuddyBoss Admin Settings 2.0 - Help Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import doneForYouImage from '../images/help-done-for-you.png';

/**
 * Help Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {JSX.Element} Help screen.
 */
export function HelpScreen( { onNavigate } ) {
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
							onClick={ function () {
								if ( 'function' === typeof onNavigate ) {
									onNavigate( '/settings/help/support-access' );
								}
							} }
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

				<section
					className="bb-admin-help-cta"
					aria-labelledby="bb-admin-help-cta-title"
				>
					<h2
						id="bb-admin-help-cta-title"
						className="bb-admin-help-cta__title"
					>
						{ __( 'Can’t find the Answer?', 'buddyboss' ) }
					</h2>
					<Button variant="primary" className="bb-admin-help-cta__action">
						<i
							className="bb-icons-rl bb-icons-rl-robot bb-admin-help-cta__action-icon"
							aria-hidden="true"
						></i>
						<span className="bb-admin-help-cta__action-label">
							{ __( 'Ask Buddy', 'buddyboss' ) }
						</span>
						<i
							className="bb-icons-rl bb-icons-rl-arrow-right bb-admin-help-cta__action-icon"
							aria-hidden="true"
						></i>
					</Button>
				</section>

				<div className="bb-admin-help-row">
					<section
						className="bb-admin-help-getting-started"
						aria-labelledby="bb-admin-help-getting-started-title"
					>
						<h2
							id="bb-admin-help-getting-started-title"
							className="bb-admin-help-getting-started__title"
						>
							{ __( 'Get Started', 'buddyboss' ) }
						</h2>
						<ul className="bb-admin-help-getting-started__list">
							{ [
								{ key: 'install-theme',     label: __( 'How to install the BuddyBoss Theme', 'buddyboss' ) },
								{ key: 'default-data',      label: __( 'How to Setup Default Data in BuddyBoss', 'buddyboss' ) },
								{ key: 'login-register',    label: __( 'How to Customize the Login & Registration Page in BuddyBoss', 'buddyboss' ) },
								{ key: 'install-theme-2',   label: __( 'How to install the BuddyBoss Theme', 'buddyboss' ) },
								{ key: 'default-data-2',    label: __( 'How to Setup Default Data in BuddyBoss', 'buddyboss' ) },
								{ key: 'login-register-2',  label: __( 'How to Customize the Login & Registration Page in BuddyBoss', 'buddyboss' ) },
							].map( function ( item ) {
								return (
									<li key={ item.key } className="bb-admin-help-getting-started__item">
										<a href="#" className="bb-admin-help-getting-started__link">
											<i
												className="bb-icons-rl bb-icons-rl-file-text bb-admin-help-getting-started__icon"
												aria-hidden="true"
											></i>
											<span className="bb-admin-help-getting-started__label">
												{ item.label }
											</span>
										</a>
									</li>
								);
							} ) }
						</ul>
					</section>

					<section
						className="bb-admin-help-promo"
						aria-labelledby="bb-admin-help-promo-title"
					>
						<div className="bb-admin-help-promo__media">
							<img
								src={ doneForYouImage }
								alt={ __( 'Done For You Service preview', 'buddyboss' ) }
								className="bb-admin-help-promo__image"
							/>
						</div>
						<div className="bb-admin-help-promo__body">
							<div className="bb-admin-help-promo__text">
								<p className="bb-admin-help-promo__eyebrow">
									{ __( 'Done For You Service', 'buddyboss' ) }
								</p>
								<h2
									id="bb-admin-help-promo-title"
									className="bb-admin-help-promo__title"
								>
									{ __( 'Get help launching your site', 'buddyboss' ) }
								</h2>
								<p className="bb-admin-help-promo__description">
									{ __( 'Get your own team that will help you launch your site. Let them know what your business is about, then provide them with your logo and brand colors, then sit back while we do all the heavy lifting.', 'buddyboss' ) }
								</p>
							</div>
							<Button variant="primary" className="bb-admin-help-promo__action">
								<span className="bb-admin-help-promo__action-label">
									{ __( 'Get Done For You Web Service', 'buddyboss' ) }
								</span>
							</Button>
						</div>
					</section>
				</div>
			</div>

			<footer
				className="bb-admin-help-footer"
				aria-label={ __( 'Chat with Buddy', 'buddyboss' ) }
			>
				<Button variant="primary" className="bb-admin-help-footer__action">
					<i
						className="bb-icons-rl bb-icons-rl-robot bb-admin-help-footer__action-icon"
						aria-hidden="true"
					></i>
					<span className="bb-admin-help-footer__action-label">
						{ __( 'Chat with Buddy', 'buddyboss' ) }
					</span>
				</Button>
			</footer>
		</div>
	);
}

export default HelpScreen;
