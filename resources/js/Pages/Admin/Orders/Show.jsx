import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

const formatMoney = (amount) => `${currency.format(amount ?? 0)} ₽`;

const statusTone = {
    awaiting_payment: 'bg-amber-50 text-amber-700 ring-amber-200',
    in_progress: 'bg-blue-50 text-blue-700 ring-blue-200',
    submitted_for_review: 'bg-purple-50 text-purple-700 ring-purple-200',
    revision_requested: 'bg-orange-50 text-orange-700 ring-orange-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    disputed: 'bg-red-50 text-red-700 ring-red-200',
    canceled: 'bg-slate-100 text-slate-700 ring-slate-200',
};

const paymentTone = {
    unpaid: 'bg-amber-50 text-amber-700 ring-amber-200',
    held: 'bg-blue-50 text-blue-700 ring-blue-200',
    released: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    refunded: 'bg-red-50 text-red-700 ring-red-200',
    canceled: 'bg-slate-100 text-slate-700 ring-slate-200',
};

export default function Show({
    order,
    workspace = {},
    events = [],
    finance = {},
    disputes = {},
    betaFeedback = [],
    links = {},
}) {
    return (
        <DashboardLayout>
            <Head title={`Заказ #${order.id}`} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div className="min-w-0">
                        <div className="flex flex-wrap gap-2">
                            <Badge tone={statusTone[order.status]}>{order.status_label}</Badge>
                            <Badge tone={paymentTone[order.payment_status]}>{order.payment_status_label}</Badge>
                            <Badge>{order.source_type_label}</Badge>
                            {disputes.active && <Badge tone="bg-red-50 text-red-700 ring-red-200">Активный спор #{disputes.active.id}</Badge>}
                        </div>
                        <p className="mt-4 text-sm font-semibold uppercase text-blue-700">Администрирование заказов</p>
                        <h1 className="mt-3 break-words text-4xl font-semibold tracking-normal text-slate-950">
                            #{order.id} {order.title}
                        </h1>
                        <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                            Карточка заказа только для чтения. Здесь нет действий для смены статусов,
                            возвратов, разблокировки средств или изменения финансовой логики.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href={links.index} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            К списку
                        </Link>
                        <Link href={links.events} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            Все события
                        </Link>
                        <Link href={links.ledger} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Финансовый журнал
                        </Link>
                    </div>
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Metric title="Сумма заказа" value={formatMoney(order.price)} />
                    <Metric title="Комиссия платформы" value={`${formatMoney(order.platform_fee_amount)} (${order.platform_fee_percent}%)`} />
                    <Metric title="Исполнителю" value={formatMoney(order.performer_amount)} />
                    <Metric title="Срок" value={`${order.delivery_days} дн.`} />
                </div>

                <div className="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-6">
                        <Panel title="Основное">
                            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <Info label="Источник" value={order.source_type_label} />
                                <Info label="Создан" value={order.created_at ?? '—'} />
                                <Info label="Обновлен" value={order.updated_at ?? '—'} />
                                <Info label="Старт" value={order.started_at ?? '—'} />
                                <Info label="Сдан" value={order.submitted_at ?? '—'} />
                                <Info label="Завершен" value={order.completed_at ?? '—'} />
                                <Info label="Отменен" value={order.canceled_at ?? '—'} />
                                <Info label="Проверка до" value={order.review_hold_until ?? '—'} />
                                <Info label="Автовыплата" value={order.auto_release_at ?? '—'} />
                                <Info label="Дата разблокировки" value={order.released_at ?? '—'} />
                                <Info label="Причина разблокировки" value={order.release_reason_label ?? order.release_reason ?? '—'} />
                            </div>
                            {order.description && (
                                <div className="mt-5 border-t border-slate-100 pt-5">
                                    <p className="text-xs font-semibold uppercase text-slate-500">Описание</p>
                                    <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">{order.description}</p>
                                </div>
                            )}
                        </Panel>

                        <Panel title="Участники">
                            <div className="grid gap-5 lg:grid-cols-2">
                                <Participant title="Заказчик" user={order.customer} />
                                <Participant title="Исполнитель" user={order.performer} />
                            </div>
                        </Panel>

                        <Panel title="Источник заказа">
                            <Source source={order.source} />
                        </Panel>

                        <Panel title="Рабочая область">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Metric title="Сообщения" value={workspace.messages_count ?? 0} compact />
                                <Metric title="Файлы" value={workspace.files_count ?? 0} compact />
                            </div>
                            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                                <List title="Последние сообщения" empty="Сообщений пока нет.">
                                    {(workspace.messages ?? []).map((message) => (
                                        <li key={message.id} className="border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                                            <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                <span className="font-semibold text-slate-700">{message.author}</span>
                                                <span>{message.author_role}</span>
                                                <span>{message.created_at}</span>
                                            </div>
                                            <p className="mt-2 break-words text-sm leading-6 text-slate-700">{message.body}</p>
                                        </li>
                                    ))}
                                </List>

                                <List title="Последние файлы" empty="Файлов пока нет.">
                                    {(workspace.files ?? []).map((file) => (
                                        <li key={file.id} className="border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                                            <p className="break-words text-sm font-semibold text-slate-900">{file.original_name}</p>
                                            <p className="mt-1 text-xs text-slate-500">
                                                {file.mime_type ?? 'тип не указан'} · {formatBytes(file.size)} · {file.user_role ?? 'роль не указана'} · {file.created_at}
                                            </p>
                                            <p className="mt-1 text-xs text-slate-500">
                                                Статус: {file.status}, модерация: {file.moderation_status}
                                            </p>
                                        </li>
                                    ))}
                                </List>
                            </div>
                        </Panel>

                        <Panel title="Последние события">
                            <List empty="Событий пока нет.">
                                {events.map((event) => (
                                    <EventItem key={event.id} event={event} />
                                ))}
                            </List>
                            <div className="mt-4">
                                <Link href={links.events} className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                                    Открыть полный журнал
                                </Link>
                            </div>
                        </Panel>

                        <Panel title="Финансы и журнал">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Metric title="Операции" value={finance.operations_count ?? 0} compact />
                                <Metric title="Записи журнала" value={finance.ledger_entries_count ?? 0} compact />
                            </div>
                            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                                <List title="Операции" empty="Операций пока нет.">
                                    {(finance.operations ?? []).map((operation) => (
                                        <li key={operation.id} className="border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                                            <p className="text-sm font-semibold text-slate-900">{operation.type_label} · {operation.status_label}</p>
                                            <p className="mt-1 text-xs text-slate-500">{formatMoney(operation.amount)} · {providerLabel(operation.provider)} · {operation.created_at}</p>
                                        </li>
                                    ))}
                                </List>
                                <List title="Сводка счетов" empty="Движений по счетам пока нет.">
                                    {(finance.account_summary ?? []).map((entry) => (
                                        <li key={`${entry.account}-${entry.direction}`} className="border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                                            <p className="text-sm font-semibold text-slate-900">{entry.account_label}</p>
                                            <p className="mt-1 text-xs text-slate-500">{entry.direction_label}: {formatMoney(entry.amount)}</p>
                                        </li>
                                    ))}
                                </List>
                            </div>
                        </Panel>
                    </div>

                    <aside className="space-y-6">
                        <Panel title="Быстрые ссылки">
                            <nav className="grid gap-2">
                                {order.customer?.admin_url && <QuickLink href={order.customer.admin_url}>Заказчик в админке</QuickLink>}
                                {order.performer?.admin_url && <QuickLink href={order.performer.admin_url}>Исполнитель в админке</QuickLink>}
                                {sourcePublicUrl(order.source) && <QuickLink href={sourcePublicUrl(order.source)}>Публичный источник</QuickLink>}
                                <QuickLink href={links.finance}>Финансовая сводка</QuickLink>
                                <QuickLink href={links.events}>События заказа</QuickLink>
                                <QuickLink href={links.ledger}>Финансовый журнал заказа</QuickLink>
                                <QuickLink href={links.beta_feedback}>Beta-обращения</QuickLink>
                            </nav>
                        </Panel>

                        <Panel title="Споры">
                            {disputes.active && (
                                <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-100">
                                    Активный спор #{disputes.active.id}: {disputes.active.reason_label}
                                </div>
                            )}
                            <List empty="Споров по заказу нет.">
                                {(disputes.items ?? []).map((dispute) => (
                                    <li key={dispute.id} className="border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                                        <Link href={dispute.show_url} className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                                            Спор #{dispute.id}
                                        </Link>
                                        <p className="mt-1 text-xs text-slate-500">{dispute.status_label} · {dispute.reason_label} · {dispute.created_at}</p>
                                        {dispute.resolution_label && <p className="mt-1 text-xs text-slate-500">Решение: {dispute.resolution_label}</p>}
                                    </li>
                                ))}
                            </List>
                        </Panel>

                        <Panel title="Связанный beta feedback">
                            <List empty="Связанных обращений пока нет.">
                                {betaFeedback.map((feedback) => (
                                    <li key={feedback.id} className="border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
                                        <Link href={feedback.show_url} className="break-words text-sm font-semibold text-blue-700 hover:text-blue-800">
                                            #{feedback.id} {feedback.title}
                                        </Link>
                                        <p className="mt-1 text-xs text-slate-500">{feedback.type} · {feedback.severity} · {feedback.status}</p>
                                    </li>
                                ))}
                            </List>
                        </Panel>
                    </aside>
                </div>
            </section>
        </DashboardLayout>
    );
}

function Source({ source }) {
    if (!source) {
        return <p className="text-sm text-slate-500">Источник не указан.</p>;
    }

    if (source.service) {
        return (
            <div className="grid gap-4 sm:grid-cols-2">
                <Info label="Тип" value={source.label} />
                <Info label="ID услуги" value={source.service.id} />
                <Info label="Название" value={source.service.title} />
                <Info label="Категория" value={source.service.category ?? '—'} />
                {source.service.public_url && <LinkLine href={source.service.public_url}>Открыть услугу</LinkLine>}
            </div>
        );
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <Info label="Тип" value={source.label} />
            <Info label="ID задания" value={source.task?.id ?? '—'} />
            <Info label="Задание" value={source.task?.title ?? '—'} />
            <Info label="Статус задания" value={source.task?.status ?? '—'} />
            <Info label="ID отклика" value={source.task_offer?.id ?? '—'} />
            <Info label="Цена отклика" value={source.task_offer ? formatMoney(source.task_offer.price) : '—'} />
            {source.task?.public_url && <LinkLine href={source.task.public_url}>Открыть задание</LinkLine>}
        </div>
    );
}

function Participant({ title, user }) {
    if (!user) {
        return (
            <div>
                <h3 className="text-sm font-semibold text-slate-900">{title}</h3>
                <p className="mt-2 text-sm text-slate-500">Пользователь не указан.</p>
            </div>
        );
    }

    return (
        <div>
            <h3 className="text-sm font-semibold text-slate-900">{title}</h3>
            <div className="mt-3 grid gap-3">
                <Info label="ID" value={user.id} />
                <Info label="Имя" value={user.name} />
                <Info label="Email" value={user.email} />
                <Info label="Роль" value={user.role_label} />
                <Info label="Статус" value={user.status_label} />
                <Info label="Рейтинг исполнителя" value={user.performer_rating ?? '—'} />
                <Link href={user.admin_url} className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                    Открыть пользователя
                </Link>
            </div>
        </div>
    );
}

function EventItem({ event }) {
    return (
        <li className="border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
            <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span className="font-semibold text-slate-800">{event.type_label}</span>
                <span>{event.actor}</span>
                <span>{event.created_at}</span>
            </div>
            {event.summary && <p className="mt-2 break-words text-sm leading-6 text-slate-700">{event.summary}</p>}
        </li>
    );
}

function Metric({ title, value, compact = false }) {
    return (
        <div className={compact ? '' : 'rounded-lg border border-slate-200 bg-white p-5 shadow-sm'}>
            <p className="text-sm text-slate-500">{title}</p>
            <p className="mt-2 break-words text-2xl font-semibold text-slate-950">{value}</p>
        </div>
    );
}

function Panel({ title, children }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            {title && <h2 className="text-xl font-semibold text-slate-950">{title}</h2>}
            <div className={title ? 'mt-5' : ''}>{children}</div>
        </section>
    );
}

function List({ title, empty, children }) {
    const items = Array.isArray(children) ? children.filter(Boolean) : children ? [children] : [];

    return (
        <div>
            {title && <h3 className="text-sm font-semibold text-slate-900">{title}</h3>}
            {items.length > 0 ? (
                <ul className={title ? 'mt-3' : ''}>{items}</ul>
            ) : (
                <p className={`${title ? 'mt-3' : ''} text-sm text-slate-500`}>{empty}</p>
            )}
        </div>
    );
}

function Info({ label, value }) {
    return (
        <div className="min-w-0">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-1 break-words text-sm text-slate-800">{value}</p>
        </div>
    );
}

function Badge({ children, tone = 'bg-slate-100 text-slate-700 ring-slate-200' }) {
    return <span className={`rounded-md px-3 py-1 text-xs font-semibold ring-1 ${tone}`}>{children}</span>;
}

function QuickLink({ href, children }) {
    return (
        <Link href={href} className="rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
            {children}
        </Link>
    );
}

function LinkLine({ href, children }) {
    return (
        <div className="sm:col-span-2">
            <Link href={href} className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                {children}
            </Link>
        </div>
    );
}

function sourcePublicUrl(source) {
    return source?.service?.public_url ?? source?.task?.public_url ?? null;
}

function formatBytes(bytes) {
    if (!bytes) {
        return '0 Б';
    }

    if (bytes < 1024) {
        return `${bytes} Б`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} КБ`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} МБ`;
}

function providerLabel(provider) {
    return provider === 'stub' ? 'Заглушка' : provider;
}
