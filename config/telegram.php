<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    */
    
    'bot_token' => env('TELEGRAM_BOT_TOKEN', '7903137926:AAFKezQgg7YowaFxc1qXJQEpo7zQRMQIaZY'),
    
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', env('APP_URL') . '/api/telegram/webhook'),
    
    'allowed_updates' => [
        'message',
        'edited_message',
        'callback_query',
        'inline_query',
        'chosen_inline_result',
        'channel_post',
        'edited_channel_post',
    ],
    
    'api_base_url' => 'https://api.telegram.org/bot',
    
    'timeout' => 30,
    
    // Настройки для хранения медиафайлов
    'media_storage' => [
        'disk' => 'public',
        'path' => 'telegram/media',
    ],
    
    // Настройки логирования
    'logging' => [
        'enabled' => env('TELEGRAM_LOGGING_ENABLED', true),
        'level' => env('TELEGRAM_LOG_LEVEL', 'info'),
        'channel' => env('TELEGRAM_LOG_CHANNEL', 'single'),
    ],
    
    // Настройки для команд бота
    'commands' => [
        'prefix' => '/',
        'case_sensitive' => false,
    ],
    
    // Ограничения
    'limits' => [
        'message_length' => 4096,
        'caption_length' => 1024,
        'entities_per_message' => 100,
    ],
]; 