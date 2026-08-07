import { useState } from 'react';
import { router } from '@inertiajs/react';
import { ChevronDown, Database, RefreshCw } from 'lucide-react';
import { toast } from 'sonner';
import { sync } from '@/actions/App/Http/Controllers/GmailSyncController';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

type SyncResponse = {
    message: string;
    synced: number;
    last_synced_at?: string;
};

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export function GmailSyncButton() {
    const [isSyncing, setIsSyncing] = useState(false);

    const handleSync = async (full = false) => {
        if (isSyncing) {
            return;
        }

        setIsSyncing(true);

        try {
            const url = full ? `${sync().url}?full=1` : sync().url;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'text/event-stream',
                    'X-XSRF-TOKEN': getXsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                toast.error('Gmail sync failed. Please try again.');
                setIsSyncing(false);
                return;
            }

            const reader = response.body?.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            if (reader) {
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n\n');
                    buffer = lines.pop() || '';

                    for (const block of lines) {
                        const eventMatch = block.match(/^event: (.*)$/m);
                        const dataMatch = block.match(/^data: (.*)$/m);

                        const eventType = eventMatch ? eventMatch[1] : 'message';
                        const dataStr = dataMatch ? dataMatch[1] : null;

                        if (dataStr) {
                            try {
                                const data = JSON.parse(dataStr);

                                if (eventType === 'complete') {
                                    toast.success(data.message ?? 'Gmail sync complete!');
                                    router.reload();
                                    setIsSyncing(false);
                                    return;
                                }

                                if (eventType === 'error') {
                                    toast.error(data.message ?? 'Gmail sync failed.');
                                    setIsSyncing(false);
                                    return;
                                }

                                if (eventType === 'progress') {
                                    router.reload();
                                }
                            } catch {
                                // Ignore JSON parse errors for incomplete chunks
                            }
                        }
                    }
                }
            }
        } catch {
            toast.error('Gmail sync failed. Check your connection and try again.');
            setIsSyncing(false);
        }
    };

    return (
        <div className="relative flex flex-col items-center">
            <div className="flex items-center">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            id="gmail-sync-button"
                            variant="ghost"
                            size="sm"
                            onClick={() => handleSync(false)}
                            disabled={isSyncing}
                            className="group relative h-9 gap-2 overflow-hidden rounded-l-lg rounded-r-none border border-transparent px-3 text-xs font-semibold transition-all duration-200 hover:border-indigo-500/30 hover:bg-indigo-500/10 hover:text-indigo-400 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <RefreshCw
                                className={`h-3.5 w-3.5 shrink-0 transition-all duration-300 ${isSyncing ? 'animate-spin text-indigo-400' : 'text-muted-foreground group-hover:text-indigo-400'}`}
                            />
                            <span className={`hidden sm:inline ${isSyncing ? 'text-indigo-400' : ''}`}>
                                {isSyncing ? 'Syncing…' : 'Sync Gmail'}
                            </span>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">
                        <p>{isSyncing ? 'Syncing your Gmail inbox…' : 'Sync latest applications'}</p>
                    </TooltipContent>
                </Tooltip>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="sm"
                            disabled={isSyncing}
                            className="h-9 px-2 rounded-l-none rounded-r-lg border-l border-slate-700/50 hover:bg-indigo-500/10 hover:text-indigo-400 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <ChevronDown className="h-3.5 w-3.5 text-muted-foreground" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56 bg-slate-900 border-slate-800">
                        <DropdownMenuItem onClick={() => handleSync(true)} className="cursor-pointer text-slate-300 hover:text-white">
                            <Database className="mr-2 h-4 w-4 text-indigo-400" />
                            <span>Full Resync (All Time)</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            {/* Indeterminate sweeping progress bar shown while syncing */}
            {isSyncing && (
                <div className="absolute -bottom-px left-0 h-[2px] w-full overflow-hidden">
                    <div className="gmail-sync-progress h-full w-full" />
                </div>
            )}

            <style>{`
                .gmail-sync-progress {
                    background: linear-gradient(
                        90deg,
                        transparent 0%,
                        #6366f1 20%,
                        #a855f7 50%,
                        #6366f1 80%,
                        transparent 100%
                    );
                    background-size: 200% 100%;
                    animation: gmail-sync-sweep 1.4s ease-in-out infinite;
                }
                @keyframes gmail-sync-sweep {
                    0%   { background-position: 200% 0; }
                    100% { background-position: -200% 0; }
                }
            `}</style>
        </div>
    );
}
