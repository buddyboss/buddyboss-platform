export const GroupHeadBar = () => {
    return (
        <>
            <div className="bb-rl-preview-group-head-bar">

                {/* Group Info Section */}
                <div className="bb-rl-preview-group-info-bar">
                    <div className="bb-rl-preview-group-info-left">
                        <div className="bb-rl-preview-group-avatar">
                            <div className="bb-rl-preview-group-avatar-icon">
                                <i className="bb-icons-rl-leaf"></i>
                            </div>
                        </div>
                        
                        <div className="bb-rl-preview-group-details">
                            <h1 className="bb-rl-preview-group-title">Nature's Symphony</h1>
                            <div className="bb-rl-preview-group-meta">
                                <div className="bb-rl-preview-group-members">
                                    <div className="bb-rl-preview-group-member-avatars">
                                        <div className="bb-rl-preview-member-avatar">
                                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}elena.png`} alt="Member" />
                                        </div>
                                        <div className="bb-rl-preview-member-avatar">
                                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john.png`} alt="Member" />
                                        </div>
                                        <div className="bb-rl-preview-member-avatar">
                                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira.png`} alt="Member" />
                                        </div>
                                    </div>
                                    <span className="bb-rl-preview-member-count">+480</span>
                                    <div className="bb-rl-preview-group-privacy">
                                        <i className="bb-icons-rl-user-plus"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div className="bb-rl-preview-group-info-right">
                        <button className="bb-rl-preview-join-button">
                            <i className="bb-icons-rl-plus"></i>
                            Join Group
                        </button>
                        <button className="bb-rl-preview-more-button">
                            <i className="bb-icons-rl-dots-three"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div className="bb-rl-preview-group-header">
                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}group-cover.jpg`} alt="Group Header" />
            </div>
        </>
    );
};