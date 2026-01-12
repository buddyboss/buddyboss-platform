/**
 * BuddyBoss Admin Settings 2.0 - Error Boundary Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Error Boundary Component
 *
 * Catches JavaScript errors anywhere in the child component tree,
 * logs those errors, and displays a fallback UI.
 */
export class ErrorBoundary extends Component {
	constructor(props) {
		super(props);
		this.state = { hasError: false, error: null };
	}

	static getDerivedStateFromError(error) {
		// Update state so the next render will show the fallback UI.
		return { hasError: true, error };
	}

	componentDidCatch(error, errorInfo) {
		// Log error to console in development
		if (process.env.NODE_ENV === 'development') {
			console.error('BuddyBoss Admin Settings 2.0 Error:', error, errorInfo);
		}

		// Optionally log to error tracking service
		// logErrorToService(error, errorInfo);
	}

	render() {
		if (this.state.hasError) {
			return (
				<div className="bb-admin-error-boundary">
					<div className="bb-admin-error-boundary__content">
						<h2>{__('Something went wrong', 'buddyboss')}</h2>
						<p>
							{__(
								'An error occurred while loading the admin interface. Please refresh the page or contact support if the problem persists.',
								'buddyboss'
							)}
						</p>
						<button
							className="button button-primary"
							onClick={() => {
								window.location.reload();
							}}
						>
							{__('Reload Page', 'buddyboss')}
						</button>
						{process.env.NODE_ENV === 'development' && this.state.error && (
							<details className="bb-admin-error-boundary__details">
								<summary>{__('Error Details', 'buddyboss')}</summary>
								<pre>{this.state.error.toString()}</pre>
								{this.state.error.stack && <pre>{this.state.error.stack}</pre>}
							</details>
						)}
					</div>
				</div>
			);
		}

		return this.props.children;
	}
}
