import { __ } from '@wordpress/i18n';

export const HelpIcon = ({ onClick, contentId }) => {
	const handleClick = () => {
		if (onClick) {
			onClick(contentId);
		}
	};

	return (
		<button
			className="help-icon"
			onClick={handleClick}
			aria-label={__('Help', 'buddyboss-platform')}
		>
			<i className="bb-icons-rl-question"></i>
		</button>
	);
};
