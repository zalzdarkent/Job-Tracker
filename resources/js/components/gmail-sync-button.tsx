import { useState } from 'react';
import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { toast } from 'sonner';
import { sync } from '@/actions/App/Http/Controllers/GmailSyncController';
import { Button } from '@/components/ui/button';
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

    const handleSync = async () => {
        if (isSyncing) {
            return;
        }

        setIsSyncing(true);

        try {
            const response = await fetch(sync().url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json() as SyncResponse;

            if (!response.ok) {
                toast.error(data?.message ?? 'Gmail sync failed. Please try again.');
                return;
            }

            toast.success(data?.message ?? 'Gmail sync complete!');
            router.reload();
        } catch {
            toast.error('Gmail sync failed. Check your connection and try again.');
        } finally {
            setIsSyncing(false);
        }
    };

    return (
        <div className="relative flex flex-col items-center">
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        id="gmail-sync-button"
                        variant="ghost"
                        size="sm"
                        onClick={handleSync}
                        disabled={isSyncing}
                        className="group relative h-9 gap-2 overflow-hidden rounded-lg border border-transparent px-3 text-xs font-semibold transition-all duration-200 hover:border-indigo-500/30 hover:bg-indigo-500/10 hover:text-indigo-400 disabled:cursor-not-allowed disabled:opacity-70"
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
                    <p>{isSyncing ? 'Syncing your Gmail inbox…' : 'Sync Gmail applications'}</p>
                </TooltipContent>
            </Tooltip>

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
