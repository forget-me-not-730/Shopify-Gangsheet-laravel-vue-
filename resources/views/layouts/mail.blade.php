<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Oswald">
    <style>
        * {
            font-family: Oswald, sans-serif;
        }

        .button {
            -webkit-text-size-adjust: none;
            border-radius: 4px;
            color: #fff;
            display: inline-block;
            overflow: hidden;
            text-decoration: none;
            background-color: #007a5c;
            padding: 5px 15px;
        }

        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }
            .footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>
</head>

<body style="background-color:rgb(208,208,208); padding: 20px;">
<div style="max-width: 660px; margin: auto; background-color: white;">
    <div style="height: 200px; width: 100%; box-sizing: border-box; padding: 25px; text-align: center;">
        <img src="{{ $order->user->logo_url ?? asset('assets/images/logo.png') }}" height="150" style="width: 100%; height: 100%; object-fit: contain; margin: auto;" alt=""/>
    </div>

    @yield('mail-body')

    <hr>
    <div style="height: 200px; width: 100%; background-color: black; box-sizing: border-box; padding: 20px; margin-top: 25px; text-align: center;">
        <img src="{{ $order->user->logo_url ?? asset('assets/images/logo_white.png') }}" height="150" style="width: 100%; height: 100%; object-fit: contain; margin: auto;" alt=""/>
    </div>
    <div style="padding: 40px 0; text-align: center">
        Copyright (C) {{ now()->format('Y') }} {{ $order->user->company_name ?? 'BAGS' }}. All rights reserved.
    </div>
</div>
</body>
</html>

