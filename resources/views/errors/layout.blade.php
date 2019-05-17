<!DOCTYPE html>
<html lang="">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/logo.png') }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="stylesheet" type="text/css"  href="{{asset('assets/fonts/fonts.css')}}" />
    <style>
        * {
            font-family: Oswald;
        }

        body {
            text-align: center;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
        }

        h1 {
            font-size: 48px;
            color: #333;
            margin-bottom: 20px;
        }

        p {
            font-size: 20px;
            color: #666;
            margin-bottom: 40px;
            max-width: 400px;
        }

        span {
            color: #999;
            font-size: 96px;
            font-weight: bold;
        }
    </style>
    <script>
        window.appEnv = "{{ config('app.env') }}";
    </script>
</head>
<body class="font-oswald">
@yield('content')
</body>
</html>
