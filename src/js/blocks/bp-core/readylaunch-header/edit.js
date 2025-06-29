/**
 * WordPress dependencies.
 */
import {
    InspectorControls,
    useBlockProps,
} from '@wordpress/block-editor';
import {
    Disabled,
    PanelBody,
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const EditReadyLaunchHeader = ({ attributes, setAttributes }) => {
    const {
        showSearch,
        showMessages,
        showNotifications,
        showProfileMenu
    } = attributes;

    const blockProps = useBlockProps();

    // Get component status from window.BP_ADMIN.components
    const messagesEnabled = window.BP_ADMIN?.components?.messages === "1";
    const notificationsEnabled = window.BP_ADMIN?.components?.notifications === "1";
    const searchEnabled = window.BP_ADMIN?.components?.search === "1";

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Header Settings', 'buddyboss')} initialOpen={true}>
                    {searchEnabled && (
                        <ToggleControl
                            label={__('Show Search', 'buddyboss')}
                            checked={showSearch}
                            onChange={() => setAttributes({ showSearch: !showSearch })}
                        />
                    )}
                    {messagesEnabled && (
                        <ToggleControl
                            label={__('Show Messages', 'buddyboss')}
                            checked={showMessages}
                            onChange={() => setAttributes({ showMessages: !showMessages })}
                        />
                    )}
                    {notificationsEnabled && (
                        <ToggleControl
                            label={__('Show Notifications', 'buddyboss')}
                            checked={showNotifications}
                            onChange={() => setAttributes({ showNotifications: !showNotifications })}
                        />
                    )}
                    <ToggleControl
                        label={__('Show Profile Menu', 'buddyboss')}
                        checked={showProfileMenu}
                        onChange={() => setAttributes({ showProfileMenu: !showProfileMenu })}
                    />
                </PanelBody>
            </InspectorControls>
            <Disabled>
                <ServerSideRender
                    block="buddyboss/readylaunch-header"
                    attributes={attributes}
                />
            </Disabled>
        </div>
    );
};

export default EditReadyLaunchHeader;
