<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineNotifyService
{
    protected $accessToken;

    public function __construct()
    {
        $this->accessToken = config('services.line_notify.access_token');
    }

    public function sendNotification($data)
    {
        $message = "\n\n";
        $message .= !empty($data['date']) ? "利用日時: {$data['date']}\n" : "";
        $message .= !empty($data['store']) ? "利用店舗: {$data['store']}\n" : "";
        $message .= !empty($data['amount']) ? "利用金額: {$data['amount']}円" : "";

        // メッセージの末尾の空白行を削除
        $message = rtrim($message);

        // メッセージが空の場合は送信しない
        if (empty($message)) {
            Log::warning('LINE通知をスキップ: メッセージが空です', $data);
            return false;
        }

        Log::info('LINE通知メッセージを送信中:', ['メッセージ' => $message]);

        try {
            $response = Http::withToken($this->accessToken)
                ->asForm()  // フォームデータとして送信
                ->post('https://notify-api.line.me/api/notify', [
                    'message' => $message
                ]);

            Log::info('LINE通知APIレスポンス:', [
                'ステータス' => $response->status(),
                '本文' => $response->body()
            ]);

            if (!$response->successful()) {
                Log::error('LINE通知APIエラー:', [
                    'ステータス' => $response->status(),
                    '本文' => $response->body()
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('LINE通知送信中の例外:', [
                'メッセージ' => $e->getMessage()
            ]);
            return false;
        }
    }
}
