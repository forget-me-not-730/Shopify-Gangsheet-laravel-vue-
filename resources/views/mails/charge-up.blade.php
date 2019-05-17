@extends('layouts.mail')

@section('mail-body')
<h1 style="text-align: center; font-weight: bold; font-size: 2rem;">
    {{$amount}}usd has been successfully added to your account!
</h1>
<div style="padding: 0 20px;">
    <div style="font-size: 1rem; text-align: start;">
        Dear <strong>{{$user->name ?? 'Gangsheet user'}}</strong>,
        <br>
        <p>
            Your email address: <strong>{{$user->email ?? ''}}</strong>
        </p>
        <p>
            Credits Added: <strong>{{$amount ?? ''}}</strong>
        </p>
        <p>
            Your Current Credits: <strong>{{$user->credits ?? ''}}</strong>
        </p>
        <br>
        <b>
            Warm regards.
        </b>
    </div>
</div>
@endsection