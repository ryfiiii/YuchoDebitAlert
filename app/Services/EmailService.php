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
            Log::info('Successfully connected to IMAP server');

            $folder = $client->getFolder('INBOX');
            Log::info('Successfully opened INBOX folder');

            $since = Carbon::now()->subDays(7)->format('d-M-Y');
            Log::info('Searching for emails since: ' . $since);

            $messages = $folder->query()
                ->subject('【ゆうちょデビット】ご利用のお知らせ')
                ->since($since)
                ->limit(30)
                ->get();

            Log::info('Total messages found: ' . count($messages));

            $newEmails = [];
            foreach ($messages as $message) {
                $messageId = $message->getMessageId();
                Log::info('Processing message ID: ' . $messageId);

                if (!ProcessedEmail::where('message_id', $messageId)->exists()) {
                    $newEmails[] = $message;
                    ProcessedEmail::create([
                        'message_id' => $messageId,
                        'processed_at' => Carbon::now(),
                    ]);
                    Log::info('New email found and processed: ' . $messageId);
                } else {
                    Log::info('Email already processed: ' . $messageId);
                }
            }

            Log::info('New emails found: ' . count($newEmails));

            return $newEmails;
        } catch (\Exception $e) {
            Log::error('Error in getNewYuchoEmails: ' . $e->getMessage());
            throw $e;
        }
    }

    public function extractYuchoEmailData($email)
    {
        // HTMLとプレーンテキストの両方を試みる
        $body = $email->getHTMLBody() ?: $email->getTextBody();

        // デバッグ用にメール本文をログに記録
        Log::debug('Original email body:', ['body' => $body]);

        // 文字エンコーディングの問題を解決するためにUTF-8に変換
        $body = mb_convert_encoding($body, 'UTF-8', 'AUTO');

        // HTMLタグを削除
        $body = strip_tags($body);

        // 改行を空白に置換して、一行のテキストにする
        $body = preg_replace('/\s+/', ' ', $body);

        Log::debug('Processed email body:', ['body' => $body]);

        // 正規表現パターンを調整して、より柔軟に情報を抽出
        preg_match('/利用日時\s*(\d{4}\/\d{2}\/\d{2}\s*\d{2}:\d{2}:\d{2})/', $body, $dateMatches);
        preg_match('/利用店舗\s*([^\d]+)(?=\s*利用金額)/', $body, $storeMatches);
        preg_match('/利用金額\s*(\d+(?:,\d+)?)\s*円/', $body, $amountMatches);

        $data = [
            'date' => trim($dateMatches[1] ?? ''),
            'store' => trim($storeMatches[1] ?? ''),
            'amount' => trim($amountMatches[1] ?? '')
        ];

        // 抽出したデータをログに記録
        Log::info('Extracted email data:', $data);

        return $data;
    }
}
