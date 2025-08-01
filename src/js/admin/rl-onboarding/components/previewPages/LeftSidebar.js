export const LeftSidebar = ({ sideMenuItems = [], customLinks = [] }) => {
    console.log(sideMenuItems);

    return (
        <div className="bb-rl-preview-sidebar">
            <ul className="bb-rl-preview-nav-list">
                {
                    sideMenuItems.length > 0 ? (
                        sideMenuItems.map((item) => (
                            <li className="bb-rl-preview-nav-item" key={item.id} style={{ display: item.enabled ? 'flex' : 'none' }}>
                                <i className={`bb-icons-rl-${item.icon}`}></i>
                                <span>{item.label}</span>
                            </li>
                        ))
                    ) : (
                        <>
                            <li className="bb-rl-preview-nav-item active">
                                <i className="bb-icons-rl-pulse"></i>
                                <span>News Feed</span>
                            </li>
                            <li className="bb-rl-preview-nav-item">
                                <i className="bb-icons-rl-users"></i>
                                <span>Members</span>
                            </li>
                            <li className="bb-rl-preview-nav-item">
                                <i className="bb-icons-rl-users-three"></i>
                                <span>Groups</span>
                            </li>
                            <li className="bb-rl-preview-nav-item">
                                <i className="bb-icons-rl-chat-text"></i>
                                <span>Forums</span>
                            </li>
                            <li className="bb-rl-preview-nav-item">
                                <i className="bb-icons-rl-chat-teardrop-text"></i>
                                <span>Messages</span>
                            </li>
                            <li className="bb-rl-preview-nav-item">
                                <i className="bb-icons-rl-bell"></i>
                                <span>Notifications</span>
                            </li>
                        </>
                    )
                }
            </ul>

            <div className="bb-rl-preview-nav-section">
                <h4 className="bb-rl-preview-nav-section-title">My Groups</h4>
                <ul className="bb-rl-preview-group-list">
                    <li className="bb-rl-preview-group-item">
                        <div className="bb-rl-preview-group-avatar"></div>
                        <span className="bb-rl-preview-group-name">Sports Freak</span>
                    </li>
                    <li className="bb-rl-preview-group-item">
                        <div className="bb-rl-preview-group-avatar"></div>
                        <span className="bb-rl-preview-group-name">Neon Nights</span>
                    </li>
                    <li className="bb-rl-preview-group-item">
                        <div className="bb-rl-preview-group-avatar"></div>
                        <span className="bb-rl-preview-group-name">Paw Crew</span>
                    </li>
                    <li className="bb-rl-preview-group-item">
                        <div className="bb-rl-preview-group-avatar"></div>
                        <span className="bb-rl-preview-group-name">Flourish Friends</span>
                    </li>
                    <li className="bb-rl-preview-group-item">
                        <div className="bb-rl-preview-group-avatar"></div>
                        <span className="bb-rl-preview-group-name">Machine Minds</span>
                    </li>
                    <li className="bb-rl-preview-group-item">
                        <div className="bb-rl-preview-group-avatar"></div>
                        <span className="bb-rl-preview-group-name">Reflective Alliance</span>
                    </li>
                </ul>
                <button className="bb-rl-preview-show-more">
                    <i className="bb-icons-rl-caret-down"></i>
                    <span>Show More</span>
                </button>
            </div>

            <div className={`bb-rl-preview-nav-section ${bbRlOnboarding.stepOptions.pages.bb_rl_enabled_pages.options?.courses == undefined ? 'bb-rl-nav-hidden' : ''}`}>
                <h4 className="bb-rl-preview-nav-section-title">My Courses</h4>
                <ul className="bb-rl-preview-course-list">
                    <li className="bb-rl-preview-course-item">
                        <div className="bb-rl-preview-course-icon"></div>
                        <span className="bb-rl-preview-course-name">UX/UI Design: Crafting...</span>
                    </li>
                    <li className="bb-rl-preview-course-item">
                        <div className="bb-rl-preview-course-icon"></div>
                        <span className="bb-rl-preview-course-name">Video Editing Masterclass...</span>
                    </li>
                    <li className="bb-rl-preview-course-item">
                        <div className="bb-rl-preview-course-icon"></div>
                        <span className="bb-rl-preview-course-name">Full Stack Developer Bo...</span>
                    </li>
                    <li className="bb-rl-preview-course-item">
                        <div className="bb-rl-preview-course-icon"></div>
                        <span className="bb-rl-preview-course-name">AI & Machine Learning...</span>
                    </li>
                    <li className="bb-rl-preview-course-item">
                        <div className="bb-rl-preview-course-icon"></div>
                        <span className="bb-rl-preview-course-name">Time Management: Achi...</span>
                    </li>
                    <li className="bb-rl-preview-course-item">
                        <div className="bb-rl-preview-course-icon"></div>
                        <span className="bb-rl-preview-course-name">Data Science for Begin...</span>
                    </li>
                </ul>
            </div>

            <div className="bb-rl-preview-nav-section">
                <h4 className="bb-rl-preview-nav-section-title">Links</h4>
                <ul className="bb-rl-preview-link-list">
                    {
                        customLinks.map((link) => (
                            <li className="bb-rl-preview-link-item" key={link.id}>
                                <i className="bb-icons-rl-link"></i>
                                <span>{link.title}</span>
                            </li>
                        ))
                    }                    
                </ul>
            </div>
        </div>
    );
}; 