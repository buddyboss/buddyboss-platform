export const Members = () => {
    return (
        <div className="bb-rl-preview-member-profile">
            {/* Member Profile Header */}
            <div className="bb-rl-preview-member-profile-header">
                <div className="bb-rl-preview-member-profile-header-top">
                    <div className="bb-rl-preview-member-profile-avatar">
                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john-144.png`} alt="John Muller" />
                    </div>
                    
                    <div className="bb-rl-preview-member-profile-info">
                        <div className="bb-rl-preview-member-profile-top">
                            <div className="bb-rl-preview-member-badge">Admin</div>
                            <div className="bb-rl-preview-member-menu">
                                <i className="bb-icons-rl-dots-three"></i>
                            </div>
                        </div>
                        
                        <h1 className="bb-rl-preview-member-profile-name">John Muller</h1>
                        
                        <div className="bb-rl-preview-member-profile-meta">
                            <span className="bb-rl-preview-member-profile-role">UX Writer</span>
                            <span className="bb-rl-preview-member-joined">Joined 23 NOV 2024</span>
                            <span className="bb-rl-preview-member-activity">Active 14 minutes ago</span>
                        </div>
                        
                        <div className="bb-rl-preview-member-profile-actions">
                            <button className="bb-rl-preview-member-action-btn bb-rl-preview-member-action-primary">
                                <i className="bb-icons-rl-plus"></i>
                                <span>Connect</span>
                            </button>
                            <button className="bb-rl-preview-member-action-btn bb-rl-preview-member-action-secondary">
                                <i className="bb-icons-rl-user-check"></i>
                                <span>Follow</span>
                            </button>
                            <button className="bb-rl-preview-member-action-btn bb-rl-preview-member-action-secondary">
                                <i className="bb-icons-rl-chat-circle"></i>
                                <span>Message</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div className="bb-rl-preview-member-profile-nav">
                    <div className="bb-rl-preview-member-nav-item bb-rl-preview-member-nav-active">
                        <span>Timeline</span>
                    </div>
                    <div className="bb-rl-preview-member-nav-item">
                        <span>Connections</span>
                    </div>
                    <div className="bb-rl-preview-member-nav-item">
                        <span>Groups</span>
                    </div>
                    <div className="bb-rl-preview-member-nav-item">
                        <span>Media</span>
                    </div>
                    <div className="bb-rl-preview-member-nav-item">
                        <span>Courses</span>
                    </div>
                </div>
            </div>
            
            {/* Filter Area */}
            <div className="bb-rl-preview-member-filter-area">
                <div className="bb-rl-preview-member-filter-left">
                    <div className="bb-rl-preview-filter-category bb-rl-preview-filter-category-active">
                        <span>My connections (3)</span>
                    </div>
                    <div className="bb-rl-preview-filter-category">
                        <span>Request (12)</span>
                    </div>
                </div>
                
                <div className="bb-rl-preview-member-filter-right">
                    <span className="bb-rl-preview-icon-button bb-rl-preview-icon-button-active"><i className="bb-icons-rl-squares-four"></i></span>
                    <span className="bb-rl-preview-icon-button"><i className="bb-icons-rl-rows"></i></span>
                    <div className="bb-rl-preview-filter-dropdown">
                        <i className="bb-icons-rl-funnel-simple"></i>
                        <span>Newest</span>
                        <i className="bb-icons-rl-caret-down"></i>
                    </div>
                </div>
            </div>
            
            {/* Member Listing */}
            <div className="bb-rl-preview-member-listing">
                <div className="bb-rl-preview-member-card">
                    
                    <div className="bb-rl-preview-member-card-avatar">
                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}mira-144.png`} alt="Mira Zai" />
                        <div className="bb-rl-preview-member-online-status"></div>
                    </div>

                    <div className="bb-rl-preview-member-badge">Admin</div>
                    
                    <div className="bb-rl-preview-member-card-info">
                        <h3 className="bb-rl-preview-member-card-name">Mira Zai</h3>
                        <div className="bb-rl-preview-member-card-role">UX Writer</div>
                        
                        <div className="bb-rl-preview-member-card-meta">
                            <span className="bb-rl-preview-member-card-joined">Joined 15 Dec 2024</span>
                            <span className="bb-rl-preview-member-card-followers">48 followers</span>
                            <span className="bb-rl-preview-member-card-status">Active now</span>
                        </div>
                    </div>
                    
                    <div className="bb-rl-preview-member-card-actions">
                        <div className="bb-rl-preview-member-card-icons">
                            <span className="bb-rl-preview-member-action-icon">
                                <i className="bb-icons-rl-megaphone"></i>
                            </span>
                            <span className="bb-rl-preview-member-action-icon">
                                <i className="bb-icons-rl-chat-circle-text"></i>
                            </span>
                        </div>
                        <button className="bb-rl-preview-member-card-btn">
                            <i className="bb-icons-rl-plus"></i>
                            <span>Connect</span>
                        </button>
                    </div>
                </div>

                <div className="bb-rl-preview-member-card">
                    
                    <div className="bb-rl-preview-member-card-avatar">
                        <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john-144.png`} alt="John Muller" />
                        <div className="bb-rl-preview-member-online-status"></div>
                    </div>

                    <div className="bb-rl-preview-member-badge">Admin</div>
                    
                    <div className="bb-rl-preview-member-card-info">
                        <h3 className="bb-rl-preview-member-card-name">John Muller</h3>
                        <div className="bb-rl-preview-member-card-role">UX Writer</div>
                        
                        <div className="bb-rl-preview-member-card-meta">
                            <span className="bb-rl-preview-member-card-joined">Joined 23 Nov 2024</span>
                            <span className="bb-rl-preview-member-card-followers">34 followers</span>
                            <span className="bb-rl-preview-member-card-status">Active now</span>
                        </div>
                    </div>
                    
                    <div className="bb-rl-preview-member-card-actions">
                        <div className="bb-rl-preview-member-card-icons">
                            <span className="bb-rl-preview-member-action-icon">
                                <i className="bb-icons-rl-megaphone"></i>
                            </span>
                            <span className="bb-rl-preview-member-action-icon">
                                <i className="bb-icons-rl-chat-circle-text"></i>
                            </span>
                        </div>
                        <button className="bb-rl-preview-member-card-btn">
                            <i className="bb-icons-rl-plus"></i>
                            <span>Connect</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};
