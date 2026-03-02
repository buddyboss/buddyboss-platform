import { Feed } from "./Feed";

export const Group = () => {
    return (
        <div className="bb-rl-preview-group">
            <div className="bb-rl-preview-group-nav">
                <ul>
                    <li className="bb-rl-preview-group-nav-active">Feed</li>
                    <li>Members</li>
                    <li>Documents</li>
                    <li>Messages</li>
                    <li>Courses</li>
                </ul>
            </div>
            <Feed showForm={false} />
        </div>
    );
};