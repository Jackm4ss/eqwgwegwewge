<?php

namespace App\Console\Commands;

use App\Services\Admin\AdminFirestoreRepository;
use Illuminate\Console\Command;

class BackfillUserPhoneIndexCommand extends Command
{
    protected $signature = 'admin:backfill-user-phone-index
        {--dry-run : Inspect the current dataset without writing phone index documents}
        {--sample=20 : Number of duplicate phone samples to print}';

    protected $description = 'Backfill the Firestore user phone index used by QR Management and Forgot QR lookups.';

    public function __construct(
        private readonly AdminFirestoreRepository $repository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->repository->backfillUserPhoneIndex(
            (bool) $this->option('dry-run'),
            max(0, (int) $this->option('sample')),
        );

        $this->table(
            ['Metric', 'Value'],
            [
                ['Dry Run', $result['dry_run'] ? 'yes' : 'no'],
                ['Total Users', (string) ($result['total_users'] ?? 0)],
                ['Users With Phone', (string) ($result['users_with_phone'] ?? 0)],
                ['Indexed', (string) ($result['indexed'] ?? 0)],
                ['Skipped Missing Phone', (string) ($result['skipped_missing_phone'] ?? 0)],
                ['Duplicate Phones', (string) ($result['duplicate_phone_count'] ?? 0)],
            ],
        );

        $duplicateSamples = $result['duplicate_phone_samples'] ?? [];

        if ($duplicateSamples !== []) {
            $this->warn('Duplicate phone samples were skipped from indexing:');

            foreach ($duplicateSamples as $sample) {
                $this->line(sprintf(
                    '- %s => %s',
                    (string) ($sample['phone_number'] ?? ''),
                    implode(', ', (array) ($sample['user_ids'] ?? [])),
                ));
            }
        }

        $this->info(
            ($result['dry_run'] ? 'Dry run' : 'Backfill').' completed.'
        );

        return self::SUCCESS;
    }
}
