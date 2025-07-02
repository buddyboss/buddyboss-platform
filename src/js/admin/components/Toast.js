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
		<div className={`bb-rl-toast bb-rl-toast--${status}`}>
			{showIcon && <div className="bb-rl-toast-icon">{getIcon()}</div>}
			<div className="bb-rl-toast-message">{message}</div>
			{status === 'error' && onDismiss && (
				<Button
					onClick={onDismiss}
					className="bb-rl-toast-dismiss"
					icon={<i className="bb-icons-rl-x" />}
				/>
			)}
		</div>
	);
}; 