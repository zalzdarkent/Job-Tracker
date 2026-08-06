import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard } from '@/routes';
import {
    Activity,
    Award,
    BarChart3,
    Briefcase,
    Building2,
    Calendar,
    CheckCircle2,
    Clock,
    FileText,
    PieChart,
    Sparkles,
    TrendingUp,
    UserCheck,
    XCircle,
} from 'lucide-react';

type DashboardStats = {
    totalApplications: number;
    activeApplications: number;
    interviews: number;
    assessments: number;
    offers: number;
    rejections: number;
    companiesCount: number;
    responseRate: number;
};

type RecentApplication = {
    id: number;
    position_title: string;
    status: string;
    company_name: string;
    recruiter_name?: string | null;
    application_date?: string | null;
    source?: string | null;
};

type StatusItem = {
    status: string;
    count: number;
    color: string;
};

type PageProps = {
    stats: DashboardStats;
    recentApplications: RecentApplication[];
    monthlyApplicationTrend: Array<{ month: string; count: number }>;
    statusBreakdown: StatusItem[];
};

export default function Dashboard() {
    const { stats, recentApplications, monthlyApplicationTrend, statusBreakdown } = usePage<PageProps>().props;

    const statCards = [
        {
            label: 'Total Applications',
            value: stats.totalApplications,
            icon: Briefcase,
            color: 'from-indigo-500/20 to-purple-500/10 text-indigo-400 border-indigo-500/20',
        },
        {
            label: 'Active Applications',
            value: stats.activeApplications,
            icon: Activity,
            color: 'from-sky-500/20 to-blue-500/10 text-sky-400 border-sky-500/20',
        },
        {
            label: 'Interviews',
            value: stats.interviews,
            icon: Clock,
            color: 'from-amber-500/20 to-orange-500/10 text-amber-400 border-amber-500/20',
        },
        {
            label: 'Assessments',
            value: stats.assessments,
            icon: FileText,
            color: 'from-blue-500/20 to-cyan-500/10 text-blue-400 border-blue-500/20',
        },
        {
            label: 'Offers Received',
            value: stats.offers,
            icon: Award,
            color: 'from-emerald-500/20 to-teal-500/10 text-emerald-400 border-emerald-500/20',
        },
        {
            label: 'Rejections',
            value: stats.rejections,
            icon: XCircle,
            color: 'from-rose-500/20 to-red-500/10 text-rose-400 border-rose-500/20',
        },
        {
            label: 'Companies',
            value: stats.companiesCount,
            icon: Building2,
            color: 'from-purple-500/20 to-pink-500/10 text-purple-400 border-purple-500/20',
        },
        {
            label: 'Response Rate',
            value: `${stats.responseRate}%`,
            icon: TrendingUp,
            color: 'from-teal-500/20 to-emerald-500/10 text-teal-400 border-teal-500/20',
        },
    ];

    const getStatusStyle = (st: string) => {
        switch (st.toLowerCase()) {
            case 'interview':
                return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
            case 'assessment':
                return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
            case 'offer':
                return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
            case 'rejection':
            case 'rejected':
                return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
            default:
                return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
        }
    };

    const maxTrendCount = Math.max(...monthlyApplicationTrend.map((m) => m.count), 1);

    return (
        <>
            <Head title="Dashboard" />
            <div className="space-y-6 p-4 md:p-6">
                {/* Hero Banner */}
                <div className="relative overflow-hidden rounded-2xl border border-indigo-500/20 bg-gradient-to-r from-indigo-950/80 via-slate-900/90 to-slate-950 p-6 md:p-8 backdrop-blur-xl shadow-2xl">
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-semibold text-indigo-400 border border-indigo-500/20 mb-3">
                                <Sparkles className="h-3.5 w-3.5" />
                                Smart Job Tracking Dashboard
                            </div>
                            <h1 className="text-2xl md:text-3xl font-extrabold tracking-tight text-white">
                                Welcome back to your Applications Hub
                            </h1>
                            <p className="mt-2 text-sm md:text-base text-slate-400 max-w-xl">
                                Real-time metrics and synchronization from your Gmail inbox and sent applications.
                            </p>
                        </div>
                    </div>
                    {/* Glow Accents */}
                    <div className="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-indigo-600/10 blur-3xl pointer-events-none" />
                </div>

                {/* Key Stat Cards Grid */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {statCards.map((card) => {
                        const Icon = card.icon;
                        return (
                            <div
                                key={card.label}
                                className={`group relative overflow-hidden rounded-2xl border bg-gradient-to-br p-5 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg dark:bg-slate-900/50 ${card.color}`}
                            >
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                        {card.label}
                                    </span>
                                    <div className="rounded-xl bg-slate-900/60 p-2.5 backdrop-blur-md">
                                        <Icon className="h-5 w-5" />
                                    </div>
                                </div>
                                <div className="mt-3 text-2xl md:text-3xl font-extrabold tracking-tight text-white">
                                    {card.value}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Main Visual Charts Section */}
                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Monthly Trend Visual Bar Chart (2 Cols) */}
                    <div className="lg:col-span-2 rounded-2xl border border-sidebar-border/70 bg-slate-900/40 p-6 backdrop-blur-xl dark:border-sidebar-border flex flex-col justify-between space-y-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-bold text-white flex items-center gap-2">
                                    <BarChart3 className="h-5 w-5 text-indigo-400" />
                                    Monthly Application Activity
                                </h2>
                                <p className="text-xs text-slate-400 mt-1">
                                    Application submissions grouped by month
                                </p>
                            </div>
                            <span className="text-xs font-medium text-indigo-400 bg-indigo-500/10 px-2.5 py-1 rounded-lg border border-indigo-500/20">
                                {monthlyApplicationTrend.length} Months Tracked
                            </span>
                        </div>

                        {/* Custom SVG/CSS Modern Bar Graph */}
                        {monthlyApplicationTrend.length === 0 ? (
                            <div className="flex h-48 items-center justify-center rounded-xl border border-dashed border-slate-800 text-sm text-slate-500">
                                No monthly trend data available.
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="flex items-end gap-3 md:gap-6 h-52 pt-6 px-2 border-b border-slate-800">
                                    {monthlyApplicationTrend.map((item) => {
                                        const heightPercent = Math.max((item.count / maxTrendCount) * 100, 8);
                                        return (
                                            <div
                                                key={item.month}
                                                className="group relative flex flex-1 flex-col items-center justify-end h-full"
                                            >
                                                {/* Tooltip */}
                                                <div className="absolute -top-10 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-950 text-white text-xs font-bold px-2.5 py-1 rounded-lg border border-slate-700 shadow-xl pointer-events-none whitespace-nowrap z-20">
                                                    {item.count} applications ({item.month})
                                                </div>
                                                {/* Bar */}
                                                <div
                                                    style={{ height: `${heightPercent}%` }}
                                                    className="w-full max-w-[48px] rounded-t-xl bg-gradient-to-t from-indigo-600 via-indigo-500 to-purple-400 transition-all duration-500 group-hover:from-indigo-500 group-hover:to-purple-300 group-hover:shadow-lg group-hover:shadow-indigo-500/25 relative overflow-hidden"
                                                >
                                                    <div className="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity" />
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                                {/* X-Axis Labels */}
                                <div className="flex justify-between gap-3 md:gap-6 px-2 text-xs font-medium text-slate-400">
                                    {monthlyApplicationTrend.map((item) => (
                                        <div key={item.month} className="flex-1 text-center truncate">
                                            {item.month}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Application Status Distribution (1 Col) */}
                    <div className="rounded-2xl border border-sidebar-border/70 bg-slate-900/40 p-6 backdrop-blur-xl dark:border-sidebar-border space-y-6">
                        <div>
                            <h2 className="text-lg font-bold text-white flex items-center gap-2">
                                <PieChart className="h-5 w-5 text-indigo-400" />
                                Status Breakdown
                            </h2>
                            <p className="text-xs text-slate-400 mt-1">
                                Distribution across application stages
                            </p>
                        </div>

                        <div className="space-y-4">
                            {statusBreakdown.map((item) => {
                                const percentage =
                                    stats.totalApplications > 0
                                        ? Math.round((item.count / stats.totalApplications) * 100)
                                        : 0;

                                return (
                                    <div key={item.status} className="space-y-1.5">
                                        <div className="flex items-center justify-between text-xs font-medium">
                                            <span className="text-slate-300 flex items-center gap-2">
                                                <span
                                                    className="h-2.5 w-2.5 rounded-full"
                                                    style={{ backgroundColor: item.color }}
                                                />
                                                {item.status}
                                            </span>
                                            <span className="text-slate-400">
                                                <strong className="text-white font-bold">{item.count}</strong> ({percentage}%)
                                            </span>
                                        </div>
                                        <div className="h-2 w-full rounded-full bg-slate-950 overflow-hidden p-0.5 border border-slate-800">
                                            <div
                                                className="h-full rounded-full transition-all duration-700"
                                                style={{
                                                    width: `${percentage}%`,
                                                    backgroundColor: item.color,
                                                }}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Recent Applications Section */}
                <div className="rounded-2xl border border-sidebar-border/70 bg-slate-900/40 p-6 backdrop-blur-xl dark:border-sidebar-border space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-bold text-white flex items-center gap-2">
                                <Briefcase className="h-5 w-5 text-indigo-400" />
                                Recent Synchronized Applications
                            </h2>
                            <p className="text-xs text-slate-400 mt-1">
                                Latest applications tracked from your accounts
                            </p>
                        </div>
                        <Link
                            href="/applications"
                            className="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors"
                        >
                            View All Applications &rarr;
                        </Link>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {recentApplications.length === 0 ? (
                            <p className="col-span-full text-sm text-slate-500 py-6 text-center">
                                No recent applications found.
                            </p>
                        ) : (
                            recentApplications.map((app) => (
                                <Link
                                    key={app.id}
                                    href={`/applications/${app.id}`}
                                    className="group flex flex-col justify-between rounded-xl border border-slate-800 bg-slate-950/60 p-4 transition-all duration-200 hover:border-indigo-500/40 hover:bg-slate-900/80 hover:shadow-lg"
                                >
                                    <div className="space-y-1.5">
                                        <div className="font-semibold text-sm text-white group-hover:text-indigo-400 transition-colors truncate">
                                            {app.position_title}
                                        </div>
                                        <div className="text-xs text-slate-400 flex items-center gap-1.5 truncate">
                                            <Building2 className="h-3.5 w-3.5 text-slate-500 shrink-0" />
                                            <span className="truncate">{app.company_name}</span>
                                        </div>
                                    </div>

                                    <div className="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                                        <span className="text-slate-500 flex items-center gap-1">
                                            <Calendar className="h-3 w-3" />
                                            {app.application_date ?? '—'}
                                        </span>
                                        <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider ${getStatusStyle(app.status)}`}>
                                            {app.status}
                                        </span>
                                    </div>
                                </Link>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
