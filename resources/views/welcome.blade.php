<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */
            html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}a{background-color:transparent}[hidden]{display:none}html{font-family:system-ui,sans-serif;line-height:1.4}*,*:before,*:after{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}*:before,*:after{--tw-content:''}body{margin:0;line-height:inherit}a{color:inherit;text-decoration:inherit}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-size:100%;font-weight:inherit;line-height:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button,[type='button'],[type='reset'],[type='submit']{-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type='search']{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dl,dd,h1,h2,h3,h4,h5,h6,hr,figure,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}ol,ul,menu{list-style:none;margin:0;padding:0}textarea{resize:vertical}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}button,[role="button"]{cursor:pointer}:disabled{cursor:default}img,svg,video,canvas,audio,iframe,embed,object{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[type='text'],[type='email'],[type='url'],[type='password'],[type='number'],[type='date'],[type='datetime-local'],[type='month'],[type='search'],[type='tel'],[type='time'],[type='week'],[multiple],textarea,select{appearance:none;background-color:#fff;border-color:#6b7280;border-width:1px;border-radius:0;padding-top:0.5rem;padding-right:0.75rem;padding-bottom:0.5rem;padding-left:0.75rem;font-size:1rem;line-height:1.5rem;--tw-shadow:0 0 #0000}[type='text']:focus,[type='email']:focus,[type='url']:focus,[type='password']:focus,[type='number']:focus,[type='date']:focus,[type='datetime-local']:focus,[type='month']:focus,[type='search']:focus,[type='tel']:focus,[type='time']:focus,[type='week']:focus,[multiple]:focus,textarea:focus,select:focus{outline:2px solid transparent;outline-offset:2px;--tw-ring-inset:var(--tw-empty,/*!*/ /*!*/);--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:#2563eb;--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow);border-color:#2563eb}[type='checkbox'],[type='radio']{appearance:none;padding:0;-webkit-print-color-adjust:exact;print-color-adjust:exact;display:inline-block;vertical-align:middle;background-origin:border-box;-webkit-user-select:none;-moz-user-select:none;user-select:none;flex-shrink:0;height:1rem;width:1rem;color:#2563eb;background-color:#fff;border-color:#6b7280;border-width:1px;--tw-shadow:0 0 #0000}

            body {
                font-family: 'Figtree', sans-serif;
            }

            .relative {
                position: relative;
            }

            .min-h-screen {
                min-height: 100vh;
            }

            .bg-dots-darker {
                background-image: radial-gradient(rgb(87 87 87 / 0.4) 1px, transparent 1px);
            }

            .bg-center {
                background-position: center;
            }

            .bg-gray-100 {
                --tw-bg-opacity: 1;
                background-color: rgb(243 244 246 / var(--tw-bg-opacity));
            }

            .selection\:bg-red-500 *::selection {
                --tw-bg-opacity: 1;
                background-color: rgb(239 68 68 / var(--tw-bg-opacity));
            }

            .selection\:text-white *::selection {
                --tw-text-opacity: 1;
                color: rgb(255 255 255 / var(--tw-text-opacity));
            }

            .selection\:bg-red-500 ::selection {
                --tw-bg-opacity: 1;
                background-color: rgb(239 68 68 / var(--tw-bg-opacity));
            }

            .selection\:text-white ::selection {
                --tw-text-opacity: 1;
                color: rgb(255 255 255 / var(--tw-text-opacity));
            }

            .fixed {
                position: fixed;
            }

            .top-0 {
                top: 0px;
            }

            .right-0 {
                right: 0px;
            }

            .p-6 {
                padding: 1.5rem;
            }

            .text-right {
                text-align: right;
            }

            .sm\:fixed {
                position: fixed;
            }

            .sm\:top-0 {
                top: 0px;
            }

            .sm\:right-0 {
                right: 0px;
            }

            .sm\:p-6 {
                padding: 1.5rem;
            }

            .font-semibold {
                font-weight: 600;
            }

            .text-gray-600 {
                --tw-text-opacity: 1;
                color: rgb(75 85 99 / var(--tw-text-opacity));
            }

            .hover\:text-gray-900:hover {
                --tw-text-opacity: 1;
                color: rgb(17 24 39 / var(--tw-text-opacity));
            }

            .ml-4 {
                margin-left: 1rem;
            }

            .focus\:outline-none:focus {
                outline: 2px solid transparent;
                outline-offset: 2px;
            }

            .focus\:ring-1:focus {
                --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
                --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);
                box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
            }

            .focus\:ring-indigo-500:focus {
                --tw-ring-opacity: 1;
                --tw-ring-color: rgb(99 102 241 / var(--tw-ring-opacity));
            }

            .rounded-md {
                border-radius: 0.375rem;
            }

            .max-w-7xl {
                max-width: 80rem;
            }

            .mx-auto {
                margin-left: auto;
                margin-right: auto;
            }

            .p-6 {
                padding: 1.5rem;
            }

            .lg\:p-8 {
                padding: 2rem;
            }

            .flex {
                display: flex;
            }

            .justify-center {
                justify-content: center;
            }

            .text-center {
                text-align: center;
            }

            .text-sm {
                font-size: 0.875rem;
                line-height: 1.25rem;
            }

            .text-lg {
                font-size: 1.125rem;
                line-height: 1.75rem;
            }

            .font-medium {
                font-weight: 500;
            }

            .leading-7 {
                line-height: 1.75rem;
            }

            .text-gray-900 {
                --tw-text-opacity: 1;
                color: rgb(17 24 39 / var(--tw-text-opacity));
            }

            .mt-6 {
                margin-top: 1.5rem;
            }

            .bg-white {
                --tw-bg-opacity: 1;
                background-color: rgb(255 255 255 / var(--tw-bg-opacity));
            }

            .overflow-hidden {
                overflow: hidden;
            }

            .shadow-sm {
                --tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
                --tw-shadow-colored: 0 1px 2px 0 var(--tw-shadow-color);
                box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
            }

            .ring-1 {
                --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
                --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);
                box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
            }

            .ring-gray-950\/5 {
                --tw-ring-color: rgb(3 7 18 / 0.05);
            }

            .rounded-lg {
                border-radius: 0.5rem;
            }

            .p-6 {
                padding: 1.5rem;
            }

            .grid {
                display: grid;
            }

            .grid-cols-1 {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .md\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .gap-6 {
                gap: 1.5rem;
            }

            .lg\:gap-8 {
                gap: 2rem;
            }

            .scale-100 {
                --tw-scale-x: 1;
                --tw-scale-y: 1;
                transform: translate(var(--tw-translate-x), var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
            }

            .flex {
                display: flex;
            }

            .items-center {
                align-items: center;
            }

            .gap-4 {
                gap: 1rem;
            }

            .h-12 {
                height: 3rem;
            }

            .w-12 {
                width: 3rem;
            }

            .h-16 {
                height: 4rem;
            }

            .w-16 {
                width: 4rem;
            }

            .bg-red-50 {
                --tw-bg-opacity: 1;
                background-color: rgb(254 242 242 / var(--tw-bg-opacity));
            }

            .bg-red-500 {
                --tw-bg-opacity: 1;
                background-color: rgb(239 68 68 / var(--tw-bg-opacity));
            }

            @media (min-width: 768px) {
                .md\:grid-cols-2 {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1024px) {
                .lg\:p-8 {
                    padding: 2rem;
                }

                .lg\:gap-8 {
                    gap: 2rem;
                }
            }

            /* Добавляем стили для сообщений */
            .messages-demo {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 0.5rem;
                padding: 1.5rem;
                color: white;
                margin-top: 1rem;
            }

            .messages-container {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 0.5rem;
                padding: 1rem;
                max-height: 200px;
                overflow-y: auto;
                margin: 1rem 0;
            }

            .message {
                background: rgba(255, 255, 255, 0.2);
                border-radius: 0.375rem;
                padding: 0.5rem;
                margin-bottom: 0.5rem;
                font-size: 0.875rem;
            }

            .message:last-child {
                margin-bottom: 0;
            }

            .btn-demo {
                background: rgba(255, 255, 255, 0.2);
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 0.375rem;
                padding: 0.5rem 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .btn-demo:hover {
                background: rgba(255, 255, 255, 0.3);
            }

            @media (prefers-color-scheme: dark) {
                .bg-dots-darker {
                    background-image: radial-gradient(rgb(255 255 255 / 0.4) 1px, transparent 1px);
                }
            }

            @media (min-width: 640px) {
                .sm\:fixed {
                    position: fixed;
                }

                .sm\:top-0 {
                    top: 0px;
                }

                .sm\:right-0 {
                    right: 0px;
                }

                .sm\:p-6 {
                    padding: 1.5rem;
                }
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="relative min-h-screen bg-gray-100 bg-dots-darker bg-center bg-gray-100 selection:bg-red-500 selection:text-white">
            @if (Route::has('login'))
                <div class="sm:fixed sm:top-0 sm:right-0 p-6 text-right">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-semibold text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-1 focus:ring-indigo-500 rounded-md">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-semibold text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-1 focus:ring-indigo-500 rounded-md">Войти</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="ml-4 font-semibold text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-1 focus:ring-indigo-500 rounded-md">Регистрация</a>
                        @endif
                    @endauth
                </div>
            @endif

            <div class="max-w-7xl mx-auto p-6 lg:p-8">
                <div class="flex justify-center">
                    <svg class="w-16 h-16 text-gray-600" viewBox="0 0 62 65" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="m61.1356 29.4611c-.1356-.6356-.7966-1.0169-1.4237-1.0169h-5.6949v-5.6949c0-.6271-.3814-1.2881-1.0169-1.4237-1.4237-.3051-2.9322.1356-3.9153 1.1186l-2.0339 2.0339c-.9831.9831-2.5763.9831-3.5594 0l-.6949-.6949c-.9831-.9831-.9831-2.5763 0-3.5594l2.0339-2.0339c.9831-.9831 1.4237-2.4915 1.1186-3.9153-.1356-.6356-.7966-1.0169-1.4237-1.0169h-5.6949v-5.6949c0-.6271-.3814-1.2881-1.0169-1.4237-1.4237-.3051-2.9322.1356-3.9153 1.1186l-2.0339 2.0339c-.9831.9831-2.5763.9831-3.5594 0l-.6949-.6949c-.9831-.9831-.9831-2.5763 0-3.5594l2.0339-2.0339c.9831-.9831 1.4237-2.4915 1.1186-3.9153-.1356-.6356-.7966-1.0169-1.4237-1.0169h-5.6949v-5.6949c0-.6271-.3814-1.2881-1.0169-1.4237-1.4237-.3051-2.9322.1356-3.9153 1.1186l-2.0339 2.0339c-.9831.9831-2.5763.9831-3.5594 0l-.6949-.6949c-.9831-.9831-.9831-2.5763 0-3.5594l2.0339-2.0339c.9831-.9831 1.4237-2.4915 1.1186-3.9153-.1356-.6356-.7966-1.0169-1.4237-1.0169h-5.6949c-1.6949 0-3.0678 1.3729-3.0678 3.0678v5.6949h-5.6949c-.6271 0-1.2881.3814-1.4237 1.0169-.3051 1.4237.1356 2.9322 1.1186 3.9153l2.0339 2.0339c.9831.9831.9831 2.5763 0 3.5594l-.6949.6949c-.9831.9831-2.5763.9831-3.5594 0l-2.0339-2.0339c-.9831-.9831-2.4915-1.4237-3.9153-1.1186-.6356.1356-1.0169.7966-1.0169 1.4237v5.6949h-5.6949c-.6271 0-1.2881.3814-1.4237 1.0169-.3051 1.4237.1356 2.9322 1.1186 3.9153l2.0339 2.0339c.9831.9831.9831 2.5763 0 3.5594l-.6949.6949c-.9831.9831-2.5763.9831-3.5594 0l-2.0339-2.0339c-.9831-.9831-2.4915-1.4237-3.9153-1.1186-.6356.1356-1.0169.7966-1.0169 1.4237v5.6949c0 1.6949 1.3729 3.0678 3.0678 3.0678h5.6949v5.6949c0 .6271.3814 1.2881 1.0169 1.4237 1.4237.3051 2.9322-.1356 3.9153-1.1186l2.0339-2.0339c.9831-.9831 2.5763-.9831 3.5594 0l.6949.6949c.9831.9831.9831 2.5763 0 3.5594l-2.0339 2.0339c-.9831.9831-1.4237 2.4915-1.1186 3.9153.1356.6356.7966 1.0169 1.4237 1.0169h5.6949v5.6949c0 .6271.3814 1.2881 1.0169 1.4237 1.4237.3051 2.9322-.1356 3.9153-1.1186l2.0339-2.0339c.9831-.9831 2.5763-.9831 3.5594 0l.6949.6949c.9831.9831.9831 2.5763 0 3.5594l-2.0339 2.0339c-.9831.9831-1.4237 2.4915-1.1186 3.9153.1356.6356.7966 1.0169 1.4237 1.0169h5.6949c1.6949 0 3.0678-1.3729 3.0678-3.0678v-5.6949h5.6949c.6271 0 1.2881-.3814 1.4237-1.0169.3051-1.4237-.1356-2.9322-1.1186-3.9153l-2.0339-2.0339c-.9831-.9831-.9831-2.5763 0-3.5594l.6949-.6949c.9831-.9831 2.5763-.9831 3.5594 0l2.0339 2.0339c.9831.9831 2.4915 1.4237 3.9153 1.1186.6356-.1356 1.0169-.7966 1.0169-1.4237v-5.6949h5.6949c.6271 0 1.2881-.3814 1.4237-1.0169.3051-1.4237-.1356-2.9322-1.1186-3.9153l-2.0339-2.0339c-.9831-.9831-.9831-2.5763 0-3.5594l.6949-.6949c.9831-.9831 2.5763-.9831 3.5594 0l2.0339 2.0339c.9831.9831 2.4915 1.4237 3.9153 1.1186.6356-.1356 1.0169-.7966 1.0169-1.4237v-5.6949c0-1.6949-1.3729-3.0678-3.0678-3.0678h-5.6949v-5.6949c0-.6271-.3814-1.2881-1.0169-1.4237z" fill="currentColor"/>
                    </svg>
                </div>

                <div class="mt-6">
                    <div class="flex justify-center">
                        <div class="max-w-3xl text-center">
                            <h1 class="text-3xl font-bold text-gray-900">Laravel</h1>
                            <p class="mt-4 text-lg leading-7 text-gray-600">
                                Laravel - это веб-фреймворк с выразительным, элегантным синтаксисом. 
                                Мы уже заложили фундамент — освободив вас для творчества.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-16">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                        <div class="scale-100 p-6 bg-white from-gray-700/50 via-transparent ring-1 ring-gray-950/5 rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 bg-red-50 flex items-center justify-center rounded-lg">
                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Документация</h2>
                            </div>
                            <p class="mt-4 text-gray-600 text-sm leading-6">
                                Laravel имеет замечательную, подробную документацию, охватывающую каждый аспект фреймворка. 
                                Независимо от того, новичок вы в Laravel или у вас есть предыдущий опыт, мы рекомендуем прочитать всю документацию.
                            </p>
                            <p class="mt-4">
                                <a href="https://laravel.com/docs" class="text-sm font-semibold text-red-500">Начать чтение →</a>
                            </p>
                        </div>

                        <div class="scale-100 p-6 bg-white from-gray-700/50 via-transparent ring-1 ring-gray-950/5 rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 bg-red-50 flex items-center justify-center rounded-lg">
                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v16a1 1 0 01-1 1H4a1 1 0 01-1-1V1a1 1 0 011-1h2a1 1 0 011 1v3m0 0h8m-8 0a1 1 0 00-1 1v10a1 1 0 001 1h8a1 1 0 001-1V5a1 1 0 00-1-1z"/>
                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Laracasts</h2>
                            </div>
                            <p class="mt-4 text-gray-600 text-sm leading-6">
                                Laracasts предлагает тысячи видеоуроков по Laravel, PHP и JavaScript. 
                                Проверьте их, посмотрите на себя и значительно повысьте свои навыки разработки.
                            </p>
                            <p class="mt-4">
                                <a href="https://laracasts.com" class="text-sm font-semibold text-red-500">Начать обучение →</a>
                            </p>
                        </div>

                        <div class="scale-100 p-6 bg-white from-gray-700/50 via-transparent ring-1 ring-gray-950/5 rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 bg-red-50 flex items-center justify-center rounded-lg">
                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Сообщения в реальном времени</h2>
                            </div>
                            <p class="mt-4 text-gray-600 text-sm leading-6">
                                Система обмена сообщениями с автоматическим обновлением каждые 2 секунды.
                                Попробуйте отправить сообщение прямо сейчас!
                            </p>
                            
                            <div class="messages-demo">
                                <div class="messages-container" id="messagesContainer">
                                    <div class="message">Система: Ожидание сообщений...</div>
                                </div>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="text" id="messageInput" placeholder="Введите сообщение..." style="flex: 1; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 0.375rem; padding: 0.5rem; color: white;">
                                    <button class="btn-demo" onclick="sendMessage()">Отправить</button>
                                </div>
                                <div style="margin-top: 10px;">
                                    <button class="btn-demo" onclick="sendTestMessage('Привет! 👋')">Тест 1</button>
                                    <button class="btn-demo" onclick="sendTestMessage('Laravel работает! 🚀')" style="margin-left: 5px;">Тест 2</button>
                                </div>
                            </div>
                        </div>

                        <div class="scale-100 p-6 bg-white from-gray-700/50 via-transparent ring-1 ring-gray-950/5 rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 bg-red-50 flex items-center justify-center rounded-lg">
                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Laravel News</h2>
                            </div>
                            <p class="mt-4 text-gray-600 text-sm leading-6">
                                Laravel News - это сообщество-портал, который публикует новости, учебники, заметки и другой интересный контент, 
                                связанный с веб-разработкой Laravel.
                            </p>
                            <p class="mt-4">
                                <a href="https://laravel-news.com/" class="text-sm font-semibold text-red-500">Прочитать новости →</a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center mt-16 px-0 sm:items-center sm:justify-between">
                    <div class="text-center text-sm text-gray-500 sm:text-left">
                        <div class="flex items-center gap-4">
                            <a href="https://github.com/sponsors/taylorotwell" class="group inline-flex items-center hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="-mt-px mr-1 w-5 h-5 group-hover:text-gray-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                                Спонсор
                            </a>
                        </div>
                    </div>

                    <div class="ml-4 text-center text-sm text-gray-500 sm:text-right sm:ml-0">
                        Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                    </div>
                </div>
            </div>
        </div>

        <script>
            let lastMessageId = 0;
            
            // Функция для отправки сообщения
            async function sendMessage() {
                const input = document.getElementById('messageInput');
                const message = input.value.trim();
                
                if (!message) return;
                
                try {
                    const response = await fetch('/send-message?message=' + encodeURIComponent(message), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    if (response.ok) {
                        input.value = '';
                        input.placeholder = 'Сообщение отправлено!';
                        setTimeout(() => {
                            input.placeholder = 'Введите сообщение...';
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Ошибка:', error);
                }
            }
            
            // Функция для отправки тестового сообщения
            function sendTestMessage(message) {
                document.getElementById('messageInput').value = message;
                sendMessage();
            }
            
            // Обработчик Enter в поле ввода
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
            
            // Функция для получения новых сообщений
            async function fetchMessages() {
                try {
                    const response = await fetch('/api/messages/latest?after=' + lastMessageId, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        if (data.messages && data.messages.length > 0) {
                            const container = document.getElementById('messagesContainer');
                            
                            data.messages.forEach(msg => {
                                const messageDiv = document.createElement('div');
                                messageDiv.className = 'message';
                                messageDiv.textContent = `${msg.user}: ${msg.message}`;
                                container.appendChild(messageDiv);
                                lastMessageId = Math.max(lastMessageId, msg.id || 0);
                            });
                            
                            // Ограничиваем количество сообщений
                            while (container.children.length > 10) {
                                container.removeChild(container.firstChild);
                            }
                            
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                } catch (error) {
                    console.error('Ошибка получения сообщений:', error);
                }
            }
            
            // Запускаем polling каждые 2 секунды
            setInterval(fetchMessages, 2000);
            fetchMessages(); // Первая загрузка
        </script>
    </body>
</html>
