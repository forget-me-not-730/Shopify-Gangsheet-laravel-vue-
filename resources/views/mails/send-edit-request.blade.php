@extends('layouts.mail')

@section('mail-body')
<h1 style="text-align: center; font-weight: bold; font-size: 2rem;">Design Edit Request.</h1>
<div style="padding: 0 20px;">
    <p style="font-size: 1rem; text-align: center;">
        Gang Sheet Design for the order #{{ $design->order->wc_order_id ?? $design->order->id }} is requested to
        edit.
    </p>
    <div style="font-size: 1rem; text-align: center;">
        <strong>------ Requested By ----- </strong>
        <div>
            {{ $design->customer->name ?? $design->order->name }}
        </div>
        <div style="color: #454d5d">
            <small>{{ $design->customer->email ?? $design->order->email }}</small>
        </div>
    </div>
    <p style="font-size: 1rem; text-align: center;">
        Please click the button below to view the requested design.
    </p>
    <div style="margin: 20px 0; text-align: center">
        <a href="{{ route('builder.design', ['design_id' => $design->id, 'token' => $design->access_token]) }}"
            class="button">
            View Requested Design
        </a>
    </div>
</div>
@endsection