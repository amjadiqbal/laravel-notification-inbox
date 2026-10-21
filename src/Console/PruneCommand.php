<?php

namespace AmjadIqbal\NotificationInbox\Console;

use AmjadIqbal\NotificationInbox\NotificationInbox;
use Illuminate\Console\Command;

final class PruneCommand extends Command
{
    protected $signature = 'notification-inbox:prune
        {--hours= : Delete notifications captured more than this many hours ago (default: retention_hours)}';

    protected $description = 'Delete old captured notifications (they can hold personal data)';

    public function handle(NotificationInbox $inbox): int
    {
        $hours = $this->option('hours');

        if ($hours !== null && (! is_numeric($hours) || (int) $hours < 0)) {
            $this->components->error('--hours must be a whole number of hours, 0 or more.');

            return self::FAILURE;
        }

        $count = $inbox->prune($hours === null ? null : (int) $hours);

        $this->components->info("Pruned {$count} captured notification(s).");

        return self::SUCCESS;
    }
}
