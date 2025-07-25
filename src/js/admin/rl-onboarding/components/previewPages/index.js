import { Header } from './Header';
import { LeftSidebar } from './LeftSidebar';
import { Feed } from './Feed';
import { FeedRightSidebar } from './FeedRightSidebar';
import { Members } from './Members';
import { MembersRightSidebar } from './MembersRightSidebar';

export const PreviewPages = ({ page = 'activity' }) => {
    return (
        <div className="bb-rl-preview-activity">
            <Header />

            <div className="bb-rl-preview-content">
                <LeftSidebar />

                <div className="bb-rl-preview-main">
                    {page === 'activity' && <Feed />}
                    {page === 'members' && <Members />}
                </div>

                {page === 'activity' && <FeedRightSidebar />}
                {page === 'members' && <MembersRightSidebar />}
            </div>
        </div>
    );
};
