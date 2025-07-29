export const MembersRightSidebar = ({ formData = {} }) => {
    const { bb_rl_member_profile_sidebars = [] } = formData;
    console.log(bb_rl_member_profile_sidebars);
    
    // Helper function to check if a widget is enabled
    const isWidgetEnabled = (widgetId) => {
        // If no configuration exists, show all widgets by default
        if (!bb_rl_member_profile_sidebars || bb_rl_member_profile_sidebars.length === 0) {
            return true;
        }
        
        const widget = bb_rl_member_profile_sidebars.find(item => item.id === widgetId);
        return widget ? widget.enabled : false;
    };

    // Helper function to get widget order
    const getWidgetOrder = (widgetId) => {
        // If no configuration exists, use default order
        if (!bb_rl_member_profile_sidebars || bb_rl_member_profile_sidebars.length === 0) {
            const defaultOrders = {
                'about': 1,
                'contact': 2, 
                'work': 3,
                'network': 4
            };
            return defaultOrders[widgetId] || 999;
        }
        
        const widget = bb_rl_member_profile_sidebars.find(item => item.id === widgetId);
        return widget ? widget.order : 999; // Default high order for widgets not in config
    };

    // Define all widgets with their configurations
    const widgets = [
        {
            id: 'complete_profile',
            order: getWidgetOrder('complete_profile'),
            enabled: isWidgetEnabled('complete_profile'),
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
            order: getWidgetOrder('connections'),
            enabled: isWidgetEnabled('connections'),
            component: (
                <div className="bb-rl-preview-widget" key="network">
                    <div className="bb-rl-preview-widget-header">
                        <h3 className="bb-rl-preview-widget-title">Connections</h3>
                        <span class="bb-rl-preview-widget-link">See all</span>
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
            order: getWidgetOrder('my_network'),
            enabled: isWidgetEnabled('my_network'),
            component: (
                <div className="bb-rl-preview-widget" key="network">
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

    // Filter enabled widgets and sort by order
    const enabledWidgets = widgets
        .filter(widget => widget.enabled)
        .sort((a, b) => a.order - b.order);

    return (
        <div className="bb-rl-preview-right-sidebar">
            {enabledWidgets.map(widget => widget.component)}
        </div>
    );
};
