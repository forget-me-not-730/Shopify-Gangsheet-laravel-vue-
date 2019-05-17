@extends('layouts.mail')

@section('mail-body')
    <h1 style="text-align: center; font-weight: bold; font-size: 2rem;">Your request is declined.</h1>
    <div style="padding: 0 20px;">
        <p style="font-size: 1rem; text-align: center;">
            Your design edit request for the order {{ "#".$design->order->id }} is declined.
        </p>
        @if($design->decline_reason)
            <div style="margin: 20px 0; text-align: center">
                <b>Reason for decline.</b>
                <p style="font-weight: 100">
                    {{$design->decline_reason}}
                </p>
            </div>
        @endif
    </div>
@endsection
