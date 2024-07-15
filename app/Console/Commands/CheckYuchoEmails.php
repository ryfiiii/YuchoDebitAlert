<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;
use App\Services\LineNotifyService;
use Illuminate\Support\Facades\Log;

class CheckYuchoEmails extends Command
{
    protected $signature = 'emails:check-yucho';
    protected $description = 'ゆうちょデビットの新着メールをチェックし、通知を送信する';

    public function handle(EmailService $emailService, LineNotifyService $lineNotifyService)
    {
        $emails = $emailService->getNewYuchoEmails();

        $this->info('新着メール数: ' . count($emails));

        foreach ($emails as $email) {
            $this->info('Processing email: ' . $email->getSubject());

            $data = $emailService->extractYuchoEmailData($email);
            $this->info('抽出されたデータ: ' . json_encode($data));

            $result = $lineNotifyService->sendNotification($data);
            if ($result) {
                $this->info('LINE Notify送信成功');
            } else {
                $this->error('LINE Notify送信失敗');
            }
        }

        $this->info('ゆうちょデビットのメールチェックが完了しました。');
    }
}
