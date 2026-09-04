<?php

namespace App\Console\Commands;

use App\Models\Label;
use App\Models\Language;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedBulkLocalizationsCommand extends Command
{
    protected $signature = 'localizations:seed-bulk
                            {--count=100000 : Number of localization keys to create}
                            {--chunk=500 : Insert chunk size}';

    protected $description = 'Populate the database with a large volume of localizations for scalability testing';

    public function handle(): int
    {
        $count = max(1, (int) $this->option('count'));
        $chunkSize = max(50, (int) $this->option('chunk'));

        $languages = Language::query()->where('is_active', true)->get(['id', 'code']);
        $labelIds = Label::query()->pluck('id')->all();

        if ($languages->isEmpty()) {
            $this->error('No languages found. Run db:seed first.');

            return self::FAILURE;
        }

        if ($labelIds === []) {
            $this->error('No labels found. Run db:seed first.');

            return self::FAILURE;
        }

        $this->info("Seeding {$count} localization keys across {$languages->count()} languages...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $now = now()->toDateTimeString();
        $prefix = 'bulk.'.uniqid('', true).'.';

        for ($offset = 0; $offset < $count; $offset += $chunkSize) {
            $batch = min($chunkSize, $count - $offset);
            $keys = [];

            for ($i = 0; $i < $batch; $i++) {
                $keys[] = [
                    'key' => $prefix.($offset + $i + 1),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('localization_keys')->insert($keys);

            $keyNames = array_column($keys, 'key');
            $keyIds = DB::table('localization_keys')
                ->whereIn('key', $keyNames)
                ->pluck('id');

            $localizations = [];
            $pivots = [];

            foreach ($keyIds as $keyId) {
                foreach ($languages as $language) {
                    $localizations[] = [
                        'localization_key_id' => $keyId,
                        'language_id' => $language->id,
                        'content' => "[{$language->code}] Content for key {$keyId}",
                        'status' => 'approved',
                        'version' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $pivots[] = [
                    'localization_key_id' => $keyId,
                    'label_id' => $labelIds[array_rand($labelIds)],
                ];
            }

            foreach (array_chunk($localizations, 1500) as $chunk) {
                DB::table('localizations')->insert($chunk);
            }

            DB::table('label_localization_key')->insert($pivots);

            $bar->advance($batch);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Bulk seeding completed.');
        $this->line('Localization keys: '.DB::table('localization_keys')->count());
        $this->line('Localizations: '.DB::table('localizations')->count());

        return self::SUCCESS;
    }
}
