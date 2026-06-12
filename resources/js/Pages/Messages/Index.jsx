import { Head } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';
import { InboxEmptyState, MessengerLayout } from './Partials/MessengerLayout';

export default function Index({ conversations = [], pagination = {}, filters = {}, tabs = [], orderStatusOptions = [], sortOptions = [] }) {
    return (
        <DashboardLayout>
            <Head title="Сообщения" />

            <MessengerLayout
                conversations={conversations}
                pagination={pagination}
                filters={filters}
                tabs={tabs}
                orderStatusOptions={orderStatusOptions}
                sortOptions={sortOptions}
            >
                <InboxEmptyState firstConversation={conversations[0] ?? null} />
            </MessengerLayout>
        </DashboardLayout>
    );
}
