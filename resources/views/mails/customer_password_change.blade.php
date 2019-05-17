@extends('layouts.mail')

@section('mail-body')
    <h1 style="text-align: center; font-weight: bold; font-size: 2rem;">
        Your Password Has Been Successfully Updated
    </h1>
    <div style="padding: 0 20px;">
        <div style="font-size: 1rem; text-align: start;">
            Dear <strong>{{$customer->name ?? 'Gangsheet user'}}</strong>,

            <p>
                This email is to confirm that the password for your account has been successfully updated.
            </p>
            <p>
                Account email: <strong>{{$customer->email ?? ''}}</strong>
            </p>
            <p>
                Your password: <strong>{{$password?? ''}}</strong>
            </p>
            <p>
                If you did not make this change, please contact our support team immediately or reset your password using our password recovery system.
            </p>
            <br>
            <p>
                For security reasons, we recommend:
                <ul>
                    <li>Not sharing your password with anyone</li>
                    <li>Using unique passwords for different accounts</li>
                </ul>
            </p>
            <br>
            <b>
                Best regards,<br>
                The Support Team
            </b>
        </div>
    </div>
@endsection