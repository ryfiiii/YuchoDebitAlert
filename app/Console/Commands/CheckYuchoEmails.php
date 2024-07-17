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

    /**
     * 新着のゆうちょデビットメールを取得し、各メールから利用情報を抽出して
     * LINE Notifyを通じて通知を送信します。処理の各段階で情報をコンソールに出力します。
     *
     * @param EmailService $emailService メール処理サービス
     * @param LineNotifyService $lineNotifyService LINE通知サービス
     * @return void
     * @throws \Exception メールの取得、データの抽出、または通知の送信中にエラーが発生した場合
     */
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
