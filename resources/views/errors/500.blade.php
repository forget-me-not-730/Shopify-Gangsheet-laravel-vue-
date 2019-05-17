@extends('errors.layout')

@section('content')
    <script>
        window.onload = () => {
            setTimeout(() => {
                window.location.reload()
            }, 3000)
        }
    </script>
@endsection
