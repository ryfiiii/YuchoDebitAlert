<?php

namespace App\Services;

use Webklex\IMAP\Facades\Client;
use App\Models\ProcessedEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function getNewYuchoEmails()
    {
        try {
            $client = Client::account('default');
            $client->connect();
            Log::info('IMAPサーバーに正常に接続しました');

            $folder = $client->getFolder('INBOX');
            Log::info('INBOXフォルダを正常に開きました');

            $since = Carbon::now()->subDays(7)->format('d-M-Y');
            Log::info('検索対象日時: ' . $since);

            $messages = $folder->query()
                ->subject('【ゆうちょデビット】ご利用のお知らせ') // 固定の件名でフィルタリング
                ->since($since)
                ->limit(30)
                ->get();

            Log::info('見つかったメッセージの総数: ' . count($messages));

            $newEmails = [];
            foreach ($messages as $message) {
                $messageId = $message->getMessageId();
                Log::info('処理中のメッセージID: ' . $messageId);

                if (!ProcessedEmail::where('message_id', $messageId)->exists()) {
                    $newEmails[] = $message;
                    ProcessedEmail::create([
                        'message_id' => $messageId,
                        'processed_at' => Carbon::now(),
                    ]);
                    Log::info('新しいメールが見つかり、処理されました: ' . $messageId);
                } else {
                    Log::info('既に処理済みのメール: ' . $messageId);
                }
            }

            Log::info('新しく見つかったメールの数: ' . count($newEmails));

            return $newEmails;
        } catch (\Exception $e) {
            Log::error('getNewYuchoEmailsでエラーが発生しました: ' . $e->getMessage());
            throw $e;
        }
    }

    public function extractYuchoEmailData($email)
    {
        // HTMLとプレーンテキストの両方を試みる
        $body = $email->getHTMLBody() ?: $email->getTextBody();

        // 文字エンコーディングの問題を解決するためにUTF-8に変換
        $body = mb_convert_encoding($body, 'UTF-8', 'AUTO');

        // HTMLタグを削除
        $body = strip_tags($body);

        // 改行を空白に置換して、一行のテキストにする
        $body = preg_replace('/\s+/', ' ', $body);

        // 固定のパターンに基づいて情報を抽出
        $patterns = [
            'date' => '/利用日時\s*(\d{4}\/\d{2}\/\d{2}\s*\d{2}:\d{2}:\d{2})/',
            'store' => '/利用店舗\s*([^\s]+)/',
            'amount' => '/利用金額\s*(\d+(?:,\d+)?)\s*円/'
        ];

        $data = [];
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                $data[$key] = trim($matches[1]);
            } else {
                $data[$key] = '';
                Log::warning("メール本文から{$key}の抽出に失敗しました");
            }
        }

        // 抽出したデータをログに記録
        Log::info('抽出されたメールデータ:', $data);

        return $data;
    }
}
