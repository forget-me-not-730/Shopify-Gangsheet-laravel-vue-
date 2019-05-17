@extends('layouts.mail')

@section('mail-body')
    <h1 style="text-align: center; font-weight: bold; font-size: 2rem;">Your request is approved.</h1>
    <div style="padding: 0 20px;">
        <p style="font-size: 1rem; text-align: center;">
            Your order {{"#".$design->order->id}} is approved to edit.
        </p>
        <div style="text-align: center; margin: 20px 0">
            <a href="{{ route('builder.design', $design->id) }}" class="button">
                View Requested Design
            </a>
        </div>
    </div>
@endsection
