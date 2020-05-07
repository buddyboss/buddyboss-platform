import { __, _n, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, ToggleControl, PanelBody } from '@wordpress/components';

registerBlockType( 'bp-zoom-meeting/meeting', {
	title: __( 'Zoom Meeting', 'buddyboss' ),
	description: __( 'Meeting in Zoom Conference', 'buddyboss' ),
	icon: 'info',
	category: 'buddyboss',

	attributes : {
		meetingId : {
			type: 'string',
			default: ''
		},
		onlyZoomEmbed : {
			type: 'boolean',
			default: false
		}
	},

	edit: ( props ) => {
		const { setAttributes } = props;
		const { meetingId, onlyZoomEmbed } = props.attributes;
		const updateMeetingId = ( val ) => {
			setAttributes( { meetingId: val } );
		}
		const setOnlyZoomEmbed = ( val ) => {
			setAttributes( { onlyZoomEmbed: val } );
		}
		return (
			<>
				<TextControl
					label={__('Meeting ID','buddyboss')}
					value={ meetingId }
					onChange={ updateMeetingId }
					/>
				<InspectorControls>
					<PanelBody
					title={ __( 'Mode', 'buddyboss' ) }
					initialOpen={ true }>
						<ToggleControl
							label={ __( 'Display the zoom embed mode', 'buddyboss' ) }
							checked={ onlyZoomEmbed }
							onChange={ setOnlyZoomEmbed } />
					</PanelBody>
				</InspectorControls>
			</>
		);
	},

	// save: ( { attributes } ) => {
	// 	return <>{ attributes.meetingId }</>;
	// }
} );
