import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Briefcase, Building2, Calendar, CheckCircle2, Clock, Mail, Sparkles, User } from 'lucide-react';

type ApplicationDetail = {
    id: number;
    position_title: string;
    status: string;
    company_name: string;
    recruiter_name?: string | null;
    application_date?: string | null;
    source?: string | null;
    notes?: string | null;
    summary?: string | null;
};

type PageProps = {
    application: ApplicationDetail;
};

export default function ApplicationsShow() {
    const { application } = usePage<PageProps>().props;

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
            <Head title={`${application.position_title} - ${application.company_name}`} />
            <div className="space-y-6 p-4 md:p-6 max-w-5xl mx-auto">
                {/* Back Link */}
                <div>
                    <Link
                        href="/applications"
                        className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" /> Back to Applications
                    </Link>
                </div>

                {/* Header Card */}
                <div className="rounded-2xl border border-sidebar-border/70 bg-slate-900/60 p-6 backdrop-blur-xl dark:border-sidebar-border shadow-xl">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div className="space-y-2">
                            <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-white flex items-center gap-3">
                                <Briefcase className="h-7 w-7 text-indigo-400 shrink-0" />
                                {application.position_title}
                            </h1>
                            <div className="flex items-center gap-3 text-sm text-slate-300 flex-wrap">
                                <span className="flex items-center gap-1.5 font-medium text-slate-200">
                                    <Building2 className="h-4 w-4 text-indigo-400" />
                                    {application.company_name}
                                </span>
                                {application.recruiter_name && (
                                    <>
                                        <span>•</span>
                                        <span className="flex items-center gap-1.5 text-slate-400">
                                            <User className="h-4 w-4 text-slate-400" />
                                            {application.recruiter_name}
                                        </span>
                                    </>
                                )}
                            </div>
                        </div>
                        <div className="flex items-center gap-3 w-fit">
                            <span className={`inline-flex items-center rounded-full border px-4 py-1.5 text-sm font-semibold capitalize ${getStatusStyle(application.status)}`}>
                                {application.status}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Meta details & AI summary */}
                <div className="grid gap-6 md:grid-cols-2">
                    <div className="rounded-2xl border border-sidebar-border/70 bg-slate-900/40 p-6 backdrop-blur-md dark:border-sidebar-border space-y-4">
                        <h2 className="text-lg font-semibold text-white flex items-center gap-2">
                            <Clock className="h-5 w-5 text-indigo-400" />
                            Application Info
                        </h2>
                        <div className="divide-y divide-slate-800 text-sm">
                            <div className="flex items-center justify-between py-3">
                                <span className="text-slate-400 flex items-center gap-2">
                                    <Calendar className="h-4 w-4" /> Applied Date
                                </span>
                                <span className="font-medium text-white">{application.application_date ?? '—'}</span>
                            </div>
                            <div className="flex items-center justify-between py-3">
                                <span className="text-slate-400 flex items-center gap-2">
                                    <Mail className="h-4 w-4" /> Source
                                </span>
                                <span className="inline-flex items-center rounded-md bg-slate-800 px-2.5 py-1 text-xs text-slate-300 border border-slate-700">
                                    {application.source ?? 'Gmail'}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-sidebar-border/70 bg-slate-900/40 p-6 backdrop-blur-md dark:border-sidebar-border space-y-4">
                        <h2 className="text-lg font-semibold text-white flex items-center gap-2">
                            <Sparkles className="h-5 w-5 text-indigo-400" />
                            AI Summary
                        </h2>
                        <p className="text-sm text-slate-300 leading-relaxed bg-slate-950/40 p-4 rounded-xl border border-slate-800/80">
                            {application.summary || 'No AI summary generated for this application yet.'}
                        </p>
                    </div>
                </div>

                {/* Notes */}
                <div className="rounded-2xl border border-sidebar-border/70 bg-slate-900/40 p-6 backdrop-blur-md dark:border-sidebar-border space-y-4">
                    <h2 className="text-lg font-semibold text-white">Full Notes & Snippet</h2>
                    <div className="bg-slate-950/60 p-4 rounded-xl border border-slate-800 text-sm text-slate-300 leading-relaxed font-mono whitespace-pre-wrap">
                        {application.notes || 'No extra notes available.'}
                    </div>
                </div>
            </div>
        </>
    );
}
