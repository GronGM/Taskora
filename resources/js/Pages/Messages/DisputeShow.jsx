import { Head } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import { ConversationDetails, ConversationView, MessengerLayout } from './Partials/MessengerLayout';

export default function DisputeShow({ conversation, conversations = [], pagination = {}, filters = {}, tabs = [], orderStatusOptions = [], sortOptions = [] }) {
    return (
        <DashboardLayout>
            <Head title={`Спор #${conversation.id}: сообщения`} />

            <MessengerLayout
                conversations={conversations}
                pagination={pagination}
                filters={filters}
                tabs={tabs}
                orderStatusOptions={orderStatusOptions}
                sortOptions={sortOptions}
                activeKey={conversation.key}
                details={<ConversationDetails conversation={conversation} />}
            >
                <ConversationView conversation={conversation} />
            </MessengerLayout>
        </DashboardLayout>
    );
}
