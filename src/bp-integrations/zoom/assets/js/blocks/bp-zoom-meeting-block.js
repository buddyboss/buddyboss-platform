import { __, _n, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { TextControl } from '@wordpress/components';

registerBlockType( 'bp-zoom-meeting/meeting', {
	title: __( 'Zoom Meeting', 'buddyboss' ),
	description: __( 'Meeting in Zoom Conference', 'buddyboss' ),
	icon: 'info',
	category: 'buddyboss',

	attributes : {
		meetingId : {
			type: 'string',
			default: ''
		}
	},

	edit: ( props ) => {
		const { setAttributes } = props;
		const { meetingId } = props.attributes;
		const updateMeetingId = ( val ) => {
			setAttributes( { meetingId: val } );
		}
		return <TextControl
		label={__('Meeting ID','buddyboss')}
		value={ meetingId }
		onChange={ updateMeetingId }
		/>;
	},

	// save: ( { attributes } ) => {
	// 	return <>{ attributes.meetingId }</>;
	// }
} );
