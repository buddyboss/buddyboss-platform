/**
 * BuddyBoss Admin Settings 2.0 - Error Boundary Component
 *
 * Catches React rendering errors and displays a fallback UI
 * instead of a white screen.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Error Boundary Component
 *
 * Wraps the Settings 2.0 app to catch rendering errors gracefully.
 */
export class ErrorBoundary extends Component {
	constructor(props) {
		super(props);
		this.state = {
			hasError: false,
			error: null,
		};
	}

	static getDerivedStateFromError(error) {
		return {
			hasError: true,
			error: error,
		};
	}

	componentDidCatch(error, errorInfo) {
		// Log to console for debugging
		if (typeof console !== 'undefined') {
			console.error('[BuddyBoss Settings 2.0] Rendering error:', error, errorInfo);
		}
	}

	handleRetry = () => {
		this.setState({ hasError: false, error: null });
	};

	render() {
		if (this.state.hasError) {
			return (
				<div className="bb-admin-error-boundary">
					<div className="bb-admin-error-boundary__content">
						<h2>{__('Something went wrong', 'buddyboss')}</h2>
						<p>{__('An error occurred while loading the settings. Please try refreshing the page.', 'buddyboss')}</p>
						{this.state.error && window.bbAdminData?.debug && (
							<pre className="bb-admin-error-boundary__details">
								{this.state.error.toString()}
							</pre>
						)}
						<div className="bb-admin-error-boundary__actions">
							<button
								type="button"
								className="button button-primary"
								onClick={this.handleRetry}
							>
								{__('Try Again', 'buddyboss')}
							</button>
							<button
								type="button"
								className="button"
								onClick={() => window.location.reload()}
							>
								{__('Reload Page', 'buddyboss')}
							</button>
						</div>
					</div>
				</div>
			);
		}

		return this.props.children;
	}
}
