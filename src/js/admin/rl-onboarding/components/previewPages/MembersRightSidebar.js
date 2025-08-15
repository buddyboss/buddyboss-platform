export const MembersRightSidebar = ({ formData = {} }) => {
    const { bb_rl_member_profile_sidebars } = formData;
    
    // Helper function to check if a widget should be shown
    const shouldShowWidget = (widgetId) => {
        
        // If no configuration exists, show all widgets by default
        if ( typeof bb_rl_member_profile_sidebars === 'undefined' ) {
            return true;
        }
        
        // Show widget if it's in the array
        const result = bb_rl_member_profile_sidebars.includes(widgetId);
        return result;
    };

    // Define all widgets
    const widgets = [
        {
            id: 'complete_profile',
            component: (
                <div className="bb-rl-preview-widget" key="complete_profile">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">Complete your profile</h3>
                    </div>
                    <div className="bb-rl-preview-widget-content">
                        <div className="bb-rl-preview-profile-completion">
                            <div className="bb-rl-preview-profile-completion-header">
                                <div className="bb-rl-preview-profile-completion-percent">64% <span>Complete</span></div>
                                <div className="bb-rl-preview-profile-progress-bar">
                                    <div className="bb-rl-preview-profile-progress-fill" style={{ width: '64%' }}></div>
                                </div>
                            </div>
                            <div className="bb-rl-preview-profile-completion-list">
                                <div className="bb-rl-preview-profile-completion-item">
                                    <div className="bb-rl-preview-profile-completion-checkbox">
                                        <i className="bb-icons-rl-circle"></i>
                                    </div>
                                    <span className="bb-rl-preview-profile-completion-label">General Information</span>
                                    <span className="bb-rl-preview-profile-completion-count">5/9</span>
                                </div>
                                <div className="bb-rl-preview-profile-completion-item bb-rl-preview-profile-completion-item-completed">
                                    <div className="bb-rl-preview-profile-completion-checkbox">
                                        <i className="bb-icons-rl-check-circle"></i>
                                    </div>
                                    <span className="bb-rl-preview-profile-completion-label">Profile Photo</span>
                                    <span className="bb-rl-preview-profile-completion-count">1/1</span>
                                </div>
                                <div className="bb-rl-preview-profile-completion-item bb-rl-preview-profile-completion-item-completed">
                                    <div className="bb-rl-preview-profile-completion-checkbox">
                                        <i className="bb-icons-rl-check-circle"></i>
                                    </div>
                                    <span className="bb-rl-preview-profile-completion-label">Cover Photo</span>
                                    <span className="bb-rl-preview-profile-completion-count">1/1</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )
        },
        {
            id: 'connections',
            component: (
                <div className="bb-rl-preview-widget" key="connections-widget">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">Connections</h3>
                        <span className="bb-rl-preview-widget-link">See all</span>
                    </div>
                    <div className="bb-rl-preview-widget-content">
                        <div className="bb-rl-preview-network-grid">
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira-144.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john-144.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}ros.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira-144.png`} alt="Network Member" />
                            </div>
                        </div>
                    </div>
                </div>
            )
        },
        {
            id: 'my_network',
            component: (
                <div className="bb-rl-preview-widget" key="my-network">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">Network</h3>
                    </div>
                    <div className="bb-rl-preview-widget-content">
                        <div className="bb-rl-preview-network-tabs">
                            <div className="bb-rl-preview-network-tab bb-rl-preview-network-tab-active">
                                <span>Followers (31)</span>
                            </div>
                            <div className="bb-rl-preview-network-tab">
                                <span>Following (12)</span>
                            </div>
                        </div>
                        <div className="bb-rl-preview-network-grid">
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira-144.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john-144.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}ros.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira-144.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john-144.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}ros.png`} alt="Network Member" />
                            </div>
                            <div className="bb-rl-preview-network-avatar">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Network Member" />
                            </div>
                        </div>
                    </div>
                </div>
            )
        }
    ];

    // Filter widgets based on configuration
    const visibleWidgets = widgets.filter(widget => shouldShowWidget(widget.id));

    return (
        <div className="bb-rl-preview-right-sidebar">
            {visibleWidgets.map(widget => widget.component)}
        </div>
    );
};
