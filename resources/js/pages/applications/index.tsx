import { Head, Link, router, usePage } from '@inertiajs/react';
import { Briefcase, Building2, Calendar, ChevronLeft, ChevronRight, Filter, Search, X } from 'lucide-react';
import { FormEvent, useState } from 'react';

type ApplicationItem = {
    id: number;
    position_title: string;
    status: string;
    company_name: string;
    recruiter_name?: string | null;
    application_date?: string | null;
    source?: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedApplications = {
    data: ApplicationItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

type PageProps = {
    applications: PaginatedApplications;
    filters: {
        status?: string;
        company?: string;
        search?: string;
    };
};

export default function ApplicationsIndex() {
    const { applications, filters } = usePage<PageProps>().props;

    const [search, setSearch] = useState(filters.search ?? '');
    const [company, setCompany] = useState(filters.company ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const handleFilter = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            '/applications',
            { search, company, status },
            { preserveState: true, replace: true }
        );
    };

    const handleReset = () => {
        setSearch('');
        setCompany('');
        setStatus('');
        router.get('/applications', {}, { preserveState: true, replace: true });
    };

    const getStatusStyle = (st: string) => {
        switch (st.toLowerCase()) {
            case 'interview':
                return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
            case 'assessment':
                return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
            case 'offer':
                return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
            case 'rejection':
            case 'rejected':
                return 'bg-rose-500/10 text-rose-500 border-rose-500/20';
            default:
                return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
        }
    };

    return (
        <>
            <Head title="Applications" />
            <div className="space-y-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-2xl border border-sidebar-border/70 bg-slate-900/60 p-6 backdrop-blur-xl dark:border-sidebar-border">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                            <Briefcase className="h-6 w-6 text-indigo-400" />
                            Applications
                        </h1>
                        <p className="mt-1 text-sm text-slate-400">
                            Track every application, status, recruiter, and response easily.
                        </p>
                    </div>
                    <div className="flex items-center gap-2 rounded-xl bg-indigo-500/10 px-4 py-2 text-sm font-semibold text-indigo-400 border border-indigo-500/20 w-fit">
                        Total: {applications.total} applications
                    </div>
                </div>

                {/* Filters */}
                <div className="rounded-2xl border border-sidebar-border/70 bg-slate-900/40 p-4 backdrop-blur-md dark:border-sidebar-border">
                    <form onSubmit={handleFilter} className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search position or note..."
                                className="w-full rounded-xl border border-slate-700/80 bg-slate-950/60 pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>
                        <div className="relative">
                            <Building2 className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                value={company}
                                onChange={(e) => setCompany(e.target.value)}
                                placeholder="Company name..."
                                className="w-full rounded-xl border border-slate-700/80 bg-slate-950/60 pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="w-full rounded-xl border border-slate-700/80 bg-slate-950/60 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="" className="bg-slate-900">All statuses</option>
                                <option value="applied" className="bg-slate-900">Applied</option>
                                <option value="interview" className="bg-slate-900">Interview</option>
                                <option value="assessment" className="bg-slate-900">Assessment</option>
                                <option value="offer" className="bg-slate-900">Offer</option>
                                <option value="rejection" className="bg-slate-900">Rejection</option>
                            </select>
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="submit"
                                className="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition cursor-pointer"
                            >
                                <Filter className="h-4 w-4" /> Filter
                            </button>
                            {(search || company || status) && (
                                <button
                                    type="button"
                                    onClick={handleReset}
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800/80 px-3 py-2 text-slate-300 hover:bg-slate-700 transition cursor-pointer"
                                    title="Reset Filters"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                    </form>
                </div>

                {/* List Items */}
                <div className="space-y-3">
                    {applications.data.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-slate-800 p-12 text-center text-slate-500 bg-slate-900/20">
                            No applications found matching your criteria.
                        </div>
                    ) : (
                        applications.data.map((app) => (
                            <Link
                                key={app.id}
                                href={`/applications/${app.id}`}
                                className="group block rounded-2xl border border-sidebar-border/60 bg-slate-900/40 p-4 transition-all duration-200 hover:border-indigo-500/40 hover:bg-slate-900/80 hover:shadow-lg hover:shadow-indigo-500/5 dark:border-sidebar-border"
                            >
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="space-y-1">
                                        <div className="font-semibold text-base text-white group-hover:text-indigo-400 transition-colors">
                                            {app.position_title}
                                        </div>
                                        <div className="flex items-center gap-2 text-sm text-slate-400 flex-wrap">
                                            <span className="font-medium text-slate-300">{app.company_name}</span>
                                            {app.recruiter_name && (
                                                <>
                                                    <span>•</span>
                                                    <span>{app.recruiter_name}</span>
                                                </>
                                            )}
                                            {app.source && (
                                                <>
                                                    <span>•</span>
                                                    <span className="rounded-md bg-slate-800 px-2 py-0.5 text-xs text-slate-400 border border-slate-700/50">
                                                        {app.source}
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center justify-between sm:justify-end gap-4 text-xs sm:text-right">
                                        <div className="flex items-center gap-1.5 text-slate-400">
                                            <Calendar className="h-3.5 w-3.5" />
                                            {app.application_date ?? '—'}
                                        </div>
                                        <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold capitalize ${getStatusStyle(app.status)}`}>
                                            {app.status}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        ))
                    )}
                </div>

                {/* Pagination Controls */}
                {applications.total > 0 && (
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-2xl border border-sidebar-border/70 bg-slate-900/40 px-6 py-4 dark:border-sidebar-border">
                        <div className="text-sm text-slate-400">
                            Showing <span className="font-semibold text-white">{applications.from ?? 0}</span> to{' '}
                            <span className="font-semibold text-white">{applications.to ?? 0}</span> of{' '}
                            <span className="font-semibold text-white">{applications.total}</span> applications
                        </div>

                        {applications.links && applications.links.length > 3 && (
                            <div className="flex items-center gap-1 flex-wrap">
                                {applications.links.map((link, idx) => {
                                    if (!link.url) {
                                        return (
                                            <span
                                                key={idx}
                                                className="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-slate-800 bg-slate-950/40 px-3 text-xs text-slate-600 cursor-not-allowed select-none"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        );
                                    }

                                    return (
                                        <Link
                                            key={idx}
                                            href={link.url}
                                            preserveState
                                            className={`inline-flex h-9 min-w-9 items-center justify-center rounded-xl border px-3 text-xs font-medium transition cursor-pointer ${
                                                link.active
                                                    ? 'border-indigo-500/50 bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                                    : 'border-slate-800 bg-slate-950/60 text-slate-300 hover:border-slate-700 hover:bg-slate-800'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                })}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}
