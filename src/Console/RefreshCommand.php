<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Console;

use Flagmint\FlagmintClient;
use Illuminate\Console\Command;

/**
 * Artisan command: refresh config-sync rules into the configured cache adapter.
 *
 * ```bash
 * php artisan flagmint:refresh
 * ```
 *
 * Useful from cron/Scheduler so FPM workers keep a warm shared snapshot without
 * each request paying for a handshake.
 */
final class RefreshCommand extends Command
{
    protected $signature = 'flagmint:refresh';

    protected $description = 'Refresh Flagmint config-sync rules (REST)';

    /**
     * @param FlagmintClient $client
     * @return int Command::SUCCESS or FAILURE
     */
    public function handle(FlagmintClient $client): int
    {
        $client->refresh();
        if ($client->getRulesStore()->isReady()) {
            $this->info('Flagmint rules refreshed (version ' . $client->getRulesStore()->getState()->version . ').');

            return self::SUCCESS;
        }

        $this->warn('Flagmint refresh completed but rules store is not ready (check API key / canary).');

        return self::FAILURE;
    }
}
