<?php

namespace AmjadIqbal\NotificationInbox\Console;

use AmjadIqbal\NotificationInbox\NotificationInbox;
use Illuminate\Console\Command;

final class ClearCommand extends Command
{
    protected $signature = 'notification-inbox:clear';

    protected $description = 'Delete every captured notification';

    public function handle(NotificationInbox $inbox): int
    {
        $count = $inbox->clear();

        $this->components->info("Deleted {$count} captured notification(s).");

        return self::SUCCESS;
    }
}
