import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Briefcase, Building2, Calendar, Clock, Mail, Sparkles, User, Paperclip, FileText, Image as ImageIcon, FileArchive, Download, Eye, ExternalLink } from 'lucide-react';
import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

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
    attachments?: {
        id: string;
        filename: string;
        mimeType: string;
        size: number;
    }[];
};

type PageProps = {
    application: ApplicationDetail;
};

export default function ApplicationsShow() {
    const { application } = usePage<PageProps>().props;
    const [previewAttachment, setPreviewAttachment] = useState<{id: string, filename: string, mimeType: string} | null>(null);

    const formatSize = (bytes: number) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const getAttachmentIcon = (mimeType: string) => {
        if (mimeType.startsWith('image/')) return <ImageIcon className="h-4 w-4 text-indigo-400" />;
        if (mimeType === 'application/pdf') return <FileText className="h-4 w-4 text-rose-400" />;
        if (mimeType.includes('zip') || mimeType.includes('rar')) return <FileArchive className="h-4 w-4 text-amber-400" />;
        return <Paperclip className="h-4 w-4 text-slate-400" />;
    };

    const renderNotesWithLinks = (text: string | null | undefined) => {
        if (!text) return 'No extra notes available.';
        
        const urlRegex = /(?:<)?(https?:\/\/[^\s>]+)(?:>)?/g;
        const parts = text.split(urlRegex);

        return parts.map((part, index) => {
            if (index % 2 === 1) {
                let displayUrl = part;
                if (part.startsWith('https://www.google.com/url?q=')) {
                    displayUrl = decodeURIComponent(part.split('url?q=')[1].split('&')[0] || part);
                } else if (part.startsWith('https://www.google.com/search?q=')) {
                    displayUrl = decodeURIComponent(part.split('search?q=')[1].split('&')[0] || part);
                }
                return (
                    <a key={index} href={part} target="_blank" rel="noopener noreferrer" className="text-indigo-400 hover:text-indigo-300 underline underline-offset-4 decoration-indigo-400/30">
                        {displayUrl}
                    </a>
                );
            }
            return <span key={index}>{part}</span>;
        });
    };

    const getStatusStyle = (st: string) => {
        switch (st.toLowerCase()) {
            case 'applied':
                return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
            case 'interview':
                return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
            case 'assessment':
                return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
            case 'offer':
                return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
            case 'rejection':
            case 'rejected':
                return 'bg-rose-500/10 text-rose-500 border-rose-500/20';
            default:
                return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
        }
    };

    const getSourceStyle = (source: string) => {
        switch (source.toLowerCase()) {
            case 'linkedin':
                return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
            case 'jobstreet':
                return 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20';
            case 'kalibrr':
                return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
            case 'glints':
                return 'bg-red-500/10 text-red-400 border-red-500/20';
            case 'tech in asia':
            case 'techinasia':
                return 'bg-teal-500/10 text-teal-400 border-teal-500/20';
            case 'gmail':
                return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
            case 'sent mail':
                return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
            default:
                return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
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
                                <span className={`inline-flex items-center rounded-md px-2.5 py-1 text-xs border ${getSourceStyle(application.source ?? 'Gmail')}`}>
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
                    <div className="bg-slate-950/60 p-4 rounded-xl border border-slate-800 text-sm text-slate-300 leading-relaxed font-mono whitespace-pre-wrap overflow-x-auto">
                        {renderNotesWithLinks(application.notes)}
                    </div>
                </div>

                {/* Attachments */}
                {application.attachments && application.attachments.length > 0 && (
                    <div className="rounded-2xl border border-sidebar-border/70 bg-slate-900/40 p-6 backdrop-blur-md dark:border-sidebar-border space-y-4">
                        <h2 className="text-lg font-semibold text-white flex items-center gap-2">
                            <Paperclip className="h-5 w-5 text-indigo-400" />
                            Attachments
                        </h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            {application.attachments.map((att) => (
                                <div key={att.id} className="flex items-center gap-3 bg-slate-950/40 border border-slate-800 rounded-xl p-3 hover:bg-slate-900/60 transition-colors">
                                    <div className="p-2 bg-slate-900 rounded-lg shrink-0">
                                        {getAttachmentIcon(att.mimeType)}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-medium text-slate-200 truncate" title={att.filename}>
                                            {att.filename}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            {formatSize(att.size)}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 gap-1">
                                        { (att.mimeType.startsWith('image/') || att.mimeType === 'application/pdf') ? (
                                            <button
                                                onClick={() => setPreviewAttachment(att)}
                                                className="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-indigo-400/10 rounded-md transition-colors"
                                                title="Preview"
                                            >
                                                <Eye className="h-4 w-4" />
                                            </button>
                                        ) : null}
                                        <a
                                            href={`/applications/${application.id}/attachments/${att.id}`}
                                            download={att.filename}
                                            className="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-indigo-400/10 rounded-md transition-colors"
                                            title="Download"
                                        >
                                            <Download className="h-4 w-4" />
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Attachment Preview Modal */}
            <Dialog open={!!previewAttachment} onOpenChange={(open) => !open && setPreviewAttachment(null)}>
                <DialogContent className="max-w-4xl max-h-[90vh] flex flex-col p-0 gap-0 overflow-hidden bg-slate-950 border-slate-800">
                    <DialogHeader className="p-4 border-b border-slate-800 bg-slate-900/50 flex flex-row items-center justify-between">
                        <DialogTitle className="flex items-center gap-2 text-slate-200 text-sm font-medium truncate">
                            {previewAttachment && getAttachmentIcon(previewAttachment.mimeType)}
                            {previewAttachment?.filename}
                        </DialogTitle>
                        {previewAttachment && (
                            <a
                                href={`/applications/${application.id}/attachments/${previewAttachment.id}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-slate-400 hover:text-indigo-400 flex items-center gap-1.5 text-xs mr-4"
                            >
                                <ExternalLink className="h-3.5 w-3.5" />
                                Open in new tab
                            </a>
                        )}
                    </DialogHeader>
                    <div className="flex-1 overflow-auto bg-slate-950/50 relative flex items-center justify-center p-4">
                        {previewAttachment?.mimeType.startsWith('image/') ? (
                            <img
                                src={`/applications/${application.id}/attachments/${previewAttachment.id}`}
                                alt={previewAttachment.filename}
                                className="max-w-full max-h-full object-contain rounded-md"
                            />
                        ) : previewAttachment?.mimeType === 'application/pdf' ? (
                            <iframe
                                src={`/applications/${application.id}/attachments/${previewAttachment.id}`}
                                className="w-full h-[75vh] rounded-md bg-white"
                                title={previewAttachment.filename}
                            />
                        ) : null}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
