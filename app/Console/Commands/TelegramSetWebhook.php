<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramSetWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook {url? : The webhook URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Telegram bot webhook';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $webhookUrl = $this->argument('url') ?: config('app.url') . '/api/telegram/webhook';
        $botToken = config('telegram.bot_token');
        
        if (!$botToken) {
            $this->error('Bot token not found in config/telegram.php');
            return 1;
        }
        
        $this->info('Setting webhook to: ' . $webhookUrl);
        
        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'edited_message', 'callback_query'],
            ]);
            
            $result = $response->json();
            
            if ($result['ok']) {
                $this->info('✅ Webhook установлен успешно!');
                $this->line('URL: ' . $webhookUrl);
                
                if (isset($result['description'])) {
                    $this->line('Описание: ' . $result['description']);
                }
                
                return 0;
            } else {
                $this->error('❌ Ошибка установки webhook: ' . $result['description']);
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Ошибка соединения с Telegram API: ' . $e->getMessage());
            Log::error('Telegram webhook error:', ['error' => $e->getMessage()]);
            return 1;
        }
    }
} 