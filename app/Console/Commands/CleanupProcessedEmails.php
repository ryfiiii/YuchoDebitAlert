<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProcessedEmail;
use Carbon\Carbon;

class CleanupProcessedEmails extends Command
{
    protected $signature = 'emails:cleanup-processed';
    protected $description = '古い処理済みメールレコードをクリーンアップする';

    public function handle()
    {
        $cutoffDate = Carbon::now()->subDays(30);

        ProcessedEmail::where('processed_at', '<', $cutoffDate)->delete();

        $this->info('古い処理済みメールレコードがクリーンアップされました。');
    }
}
