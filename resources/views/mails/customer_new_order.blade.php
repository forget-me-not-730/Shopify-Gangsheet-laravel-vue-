@extends('layouts.mail')

@section('mail-body')
    <h1 style="text-align: center; font-weight: bold; font-size: 2rem;">A Copy of Your Designs</h1>
    <div style="padding: 0 20px;">
        <p style="font-size: 1rem; text-align: start;">
            Follow these steps to complete your purchase with <b>{{ $order->user->company_name }}</b>:
        </p>
        <ul style="list-style-type: decimal; font-size: 1rem; text-align: start;">
            <li>
                Reference this e-mail to see what size gang sheets you designed for <b>{{ $order->product->title  }}</b>
            </li>
            <li>
                You have already been re-directed to <a href="{{ $order->product->redirect_url }}">{{ $order->product->redirect_url  }}</a>,
                select the size gang sheets that you designed and make sure to check out for the correct size and quantity.
            </li>
            <li>
                Pay and Receive your product!
            </li>
        </ul>
    </div>
    <hr>
    <table style="width: 100%; font-size: 1rem; text-align: center">
        <thead>
        <tr>
            <th>Image</th>
            <th>Size</th>
            <th>Quantity</th>
            <th>Price</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->designs as $design)
            <tr>
                <td>
                    <img src="{{ $design->thumbnail_url }}" height="100" width="100" style="height: 100px; width: 100px; object-fit: contain;" alt="">
                </td>
                <td>{{ $design->size->label }}</td>
                <td>{{ $design->quantity }}</td>
                <td>$ {{ number_format($design->size->price, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="3" style="text-align: right; font-weight: bold;">Subtotal:</td>
            <td>$ {{ number_format($order->designs->sum(function($design) { 
                return $design->quantity * $design->size->price;
            }), 2) }}</td>
        </tr>
        </tbody>
    </table>
@endsection
