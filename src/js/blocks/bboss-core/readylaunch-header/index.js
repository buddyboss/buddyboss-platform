/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './style.scss';
import metadata from './block.json';
import editReadyLaunchHeader from './edit';

const block_icon = (
	<svg width="24" height="24" viewBox="0 0 24 24" fill="none"
	     xmlns="http://www.w3.org/2000/svg">
		<path d="M9 6.5C8.44771 6.5 8 6.94772 8 7.5V10.5C8 11.0523 8.44771 11.5 9 11.5H15C15.5523 11.5 16 11.0523 16 10.5V7.5C16 6.94772 15.5523 6.5 15 6.5H9Z" fill="black"/>
		<path d="M8 13.75C8 14.1642 8.33579 14.5 8.75 14.5H15.25C15.6642 14.5 16 14.1642 16 13.75C16 13.3358 15.6642 13 15.25 13H8.75C8.33579 13 8 13.3358 8 13.75Z" fill="black"/>
		<path d="M8.75 16C8.33579 16 8 16.3358 8 16.75C8 17.1642 8.33579 17.5 8.75 17.5H15.25C15.6642 17.5 16 17.1642 16 16.75C16 16.3358 15.6642 16 15.25 16H8.75Z" fill="black"/>
		<path d="M7.5 2.00024C5.567 2.00024 4 3.56725 4 5.50024V18.5002C4 20.4332 5.567 22.0002 7.5 22.0002H16.5C18.433 22.0002 20 20.4332 20 18.5002V7.64084C20 6.9061 19.7304 6.1969 19.2422 5.64775L16.895 3.00715C16.3257 2.36669 15.5097 2.00024 14.6528 2.00024H7.5ZM18.5 7.64084V18.5002C18.5 19.6048 17.6046 20.5002 16.5 20.5002H7.5C6.39543 20.5002 5.5 19.6048 5.5 18.5002V5.50024C5.5 4.39567 6.39543 3.50024 7.5 3.50024H14.6528C15.0813 3.50024 15.4893 3.68347 15.7739 4.0037L18.1211 6.6443C18.3652 6.91887 18.5 7.27347 18.5 7.64084Z" fill="black"/>
	</svg>
);

/**
 * Register block
 */
registerBlockType(
	metadata,
	{
		icon: {
			src: block_icon,
		},
		edit: editReadyLaunchHeader,
	}
);

