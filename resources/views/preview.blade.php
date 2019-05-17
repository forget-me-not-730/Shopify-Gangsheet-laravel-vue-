<!DOCTYPE html>
<html lang="">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/logo.png') }}">
    <title>Preview Image</title>
    @vite(['resources/css/app.scss'])
</head>
<body class="font-oswald w-screen h-screen flex bg-white">
@if($image_url)
    <div class="w-screen h-screen">
        <img src="{{ $image_url }}" class="h-full mx-auto object-contain border" alt="">
    </div>
@else
    <div class="max-w-xl m-auto space-y-10">
        <div class="flex space-x-5 items-center justify-center">
            @if($design->merchant->logo_url)
                <img src="{{ $design->merchant->logo_url }}" class="h-12" alt="logo">
            @else
                <div class="text-gray-600 text-5xl font-bold">{{ $design->merchant->company_name }}</div>
            @endif
        </div>
        @if($design->status == 'failed')
            <div class="text-gray-600 text-xl rounded border-l-4 border-l-red-500 p-3 bg-gray-200 flex items-center space-x-3">
                <i class="mdi mdi-emoticon-sad text-red-500 text-4xl"></i>
                <div>
                    <div class="font-semibold">Something went wrong!</div>
                    <div>Failed to generate the preview image, try again.</div>
                </div>
            </div>
        @else
            <div class="text-gray-600 text-xl rounded border-l-4 border-l-orange-500 p-3 bg-gray-200 flex items-center space-x-3">
                <svg aria-hidden="true" class="w-10 h-10 text-white animate-spin fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                        fill="currentColor"/>
                    <path
                        d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                        fill="currentFill"/>
                </svg>
                <div>
                    <div class="font-semibold">The preview image is being generated.</div>
                    <div>Please wait a moment. You will see  once it's ready.</div>
                </div>
            </div>
            <script>
                setTimeout(()=>{
                    window.location.reload()
                }, 5000)
            </script>
        @endif
    </div>
@endif
</body>
</html>
