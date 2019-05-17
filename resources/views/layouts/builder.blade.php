<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.png') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}"/>

    <link rel="stylesheet" type="text/css" href="{{asset('assets/fonts/fonts.css?v=2')}}"/>

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <script>
        window.appEnv = "{{ app()->environment() }}"
    </script>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family={{$fontName ?? 'Oswald'}}">

    <style>

        :root {
            --gs-bg-color: {{ $bgColor ?? '#ffffff' }};
            --gs-fg-color: {{ $fgColor ?? '#333333' }};
            --gs-side-bar-bg-color: {{ $sideBarBgColor ?? '#ffffff' }};
            --gs-top-bar-bg-color: {{ $topBarBgColor ?? '#ffffff' }};
            --gs-primary-color: {{ $primaryColor ?? '#0f172a' }};
            --gs-secondary-color: {{ $secondaryColor ?? '#9d3002' }};

            --gs-side-bar-fg-color: {{ color_contrast($sideBarBgColor ?? '#ffffff') }};
            --gs-top-bar-fg-color: {{ color_contrast($topBarBgColor ?? '#ffffff') }};
            --gs-primary-fg-color: {{ color_contrast($primaryColor ?? '#0f172a') }};
            --gs-secondary-fg-color: {{ color_contrast($secondaryColor ?? '#019aff') }};
        }

        html, body, #app {
            height: 100%;
            max-height: 100%;
            font-family: "{{$fontName ?? 'Oswald'}}", sans-serif;
        }

        #app {
            background-image: url("{{asset('assets/spinner.svg')}}");
            background-repeat: no-repeat;
            background-position: center;
        }
    </style>

    @routes
    @yield('content')
    @inertiaHead
</head>
<body>
@inertia

@if(isset($shop))
    <script>
        window.shopUUID = "{{ $shop->shop_uuid ?? '' }}";
    </script>
@endif

@if(isset($chatScript) && $chatScript)
    <div style="display: none!important;">
        {!! $chatScript ?? null !!}
    </div>
    @if(str_contains($chatScript, 'code.tidio.co'))
        <script>
            (function () {
                function onTidioChatApiReady() {
                    window.tidioChatApi.hide();
                    window.tidioChatApi.on('close', function () {
                        window.tidioChatApi.hide();
                    });
                }

                if (window.tidioChatApi) {
                    window.tidioChatApi.on('ready', onTidioChatApiReady);
                } else {
                    document.addEventListener('tidioChat-ready', onTidioChatApiReady);
                }
            })();
        </script>
    @endif
@endif

</body>
</html>
