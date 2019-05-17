@extends('layouts.mail')

@section('mail-body')
    <h1 style="text-align: center; font-weight: bold; font-size: 2rem;">
        A new account has been successfully created for you.
    </h1>
    <div style="padding: 0 20px;">
        <div style="font-size: 1rem; text-align: start;">
            Dear <strong>{{$customer->name ?? 'Gangsheet user'}}</strong>,

            <p>
                Your email address: <strong>{{$customer->email ?? ''}}</strong>
            </p>
            <p>
                Your password: <strong>{{$pwd_string ?? ''}}</strong>
            </p>
            <br>
            <b>
                Warm regards.
            </b>
        </div>
    </div>
@endsection
