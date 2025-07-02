<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>deus-ex.site</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */
            html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}a{background-color:transparent}[hidden]{display:none}html{font-family:system-ui,sans-serif;line-height:1.4}*,*:before,*:after{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}*:before,*:after{--tw-content:''}body{margin:0;line-height:inherit}a{color:inherit;text-decoration:inherit}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-size:100%;font-weight:inherit;line-height:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button,[type='button'],[type='reset'],[type='submit']{-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type='search']{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dl,dd,h1,h2,h3,h4,h5,h6,hr,figure,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}ol,ul,menu{list-style:none;margin:0;padding:0}textarea{resize:vertical}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}button,[role="button"]{cursor:pointer}:disabled{cursor:default}img,svg,video,canvas,audio,iframe,embed,object{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[type='text'],[type='email'],[type='url'],[type='password'],[type='number'],[type='date'],[type='datetime-local'],[type='month'],[type='search'],[type='tel'],[type='time'],[type='week'],[multiple],textarea,select{appearance:none;background-color:#fff;border-color:#6b7280;border-width:1px;border-radius:0;padding-top:0.5rem;padding-right:0.75rem;padding-bottom:0.5rem;padding-left:0.75rem;font-size:1rem;line-height:1.5rem;--tw-shadow:0 0 #0000}[type='text']:focus,[type='email']:focus,[type='url']:focus,[type='password']:focus,[type='number']:focus,[type='date']:focus,[type='datetime-local']:focus,[type='month']:focus,[type='search']:focus,[type='tel']:focus,[type='time']:focus,[type='week']:focus,[multiple]:focus,textarea:focus,select:focus{outline:2px solid transparent;outline-offset:2px;--tw-ring-inset:var(--tw-empty,/*!*/ /*!*/);--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:#2563eb;--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow);border-color:#2563eb}[type='checkbox'],[type='radio']{appearance:none;padding:0;-webkit-print-color-adjust:exact;print-color-adjust:exact;display:inline-block;vertical-align:middle;background-origin:border-box;-webkit-user-select:none;-moz-user-select:none;user-select:none;flex-shrink:0;height:1rem;width:1rem;color:#2563eb;background-color:#fff;border-color:#6b7280;border-width:1px;--tw-shadow:0 0 #0000}

            body {
                font-family: 'Figtree', sans-serif;
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
                color: #e2e8f0;
            }

            .relative {
                position: relative;
            }

            .min-h-screen {
                min-height: 100vh;
            }

            .bg-dots-darker {
                background-image: radial-gradient(rgb(255 255 255 / 0.1) 1px, transparent 1px);
            }

            .bg-center {
                background-position: center;
            }

            .bg-gray-100 {
                background: transparent;
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
                color: rgba(226, 232, 240, 0.8);
            }

            .hover\:text-gray-900:hover {
                --tw-text-opacity: 1;
                color: rgb(255 255 255 / var(--tw-text-opacity));
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

            .text-red-500 {
                color: #60a5fa;
            }

            .text-gray-500 {
                color: rgba(226, 232, 240, 0.6);
            }

            .font-medium {
                font-weight: 500;
            }

            .leading-7 {
                line-height: 1.75rem;
            }

            .text-gray-900 {
                --tw-text-opacity: 1;
                color: rgb(255 255 255 / var(--tw-text-opacity));
            }

            .mt-6 {
                margin-top: 1.5rem;
            }

            .bg-white {
                --tw-bg-opacity: 1;
                background-color: rgba(30, 30, 60, 0.8);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
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
                background-color: rgba(59, 130, 246, 0.2);
            }

            .bg-red-500 {
                --tw-bg-opacity: 1;
                background-color: rgb(59, 130, 246);
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
        </div>
    </body>
</html>
