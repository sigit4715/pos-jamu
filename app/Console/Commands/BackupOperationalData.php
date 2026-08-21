<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BackupOperationalData extends Command
{
    protected $signature = 'pos:backup {--label=manual : Label tambahan pada nama backup}';
    protected $description = 'Menyimpan snapshot seluruh tabel aplikasi ke storage private';

    public function handle(): int
    {
        $label = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $this->option('label')) ?: 'manual';
        $tables = Schema::getTableListing();
        $snapshot = [];
        $this->output->progressStart(count($tables));
        foreach ($tables as $table) {
            $snapshot[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $path = 'backups/pos-jamu-'.$label.'-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode([
            'captured_at' => now()->toIso8601String(),
            'database' => config('database.connections.'.config('database.default').'.database'),
            'tables' => $snapshot,
        ], JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
        $this->info('Backup tersimpan: storage/app/private/'.$path);
        return self::SUCCESS;
    }
}
