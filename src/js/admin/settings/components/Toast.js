/**
 * BuddyBoss Admin Settings 2.0 - Toast Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';

export const Toast = ({ status, message, onDismiss, showIcon = true }) => {
	const getIcon = () => {
		switch (status) {
			case 'saving':
				return <Spinner />;
			case 'success':
				return <i className="bb-icons-rl-fill bb-icons-rl-check-circle" />;
			case 'error':
				return <i className="bb-icons-rl-warning-circle" />;
			default:
				return null;
		}
	};

	return (
		<div className={`bb-toast bb-toast--${status}`} role={ 'error' === status ? 'alert' : 'status' } aria-live={ 'error' === status ? 'assertive' : 'polite' }>
			{showIcon && <div className="bb-toast__icon">{getIcon()}</div>}
			<div className="bb-toast__message">{message}</div>
			{ 'error' === status && onDismiss && (
				<Button
					onClick={onDismiss}
					className="bb-toast__dismiss"
					aria-label={__('Dismiss', 'buddyboss')}
					icon={<i className="bb-icons-rl-x" />}
				/>
			)}
		</div>
	);
};
