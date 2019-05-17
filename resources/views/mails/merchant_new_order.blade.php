@extends('layouts.mail')

@section('mail-body')
    <h1 style="text-align: center; font-weight: bold; font-size: 2rem;">Gang Sheet(s) Created!</h1>
    <div style="padding: 0 20px;">
        <div style="font-size: 1rem; text-align: start;">
            This e-mail, is to notify you that a customer has used the link on your website to build a, or some, Gang Sheets!
        </div>
        <div>
            Use this e-mail notification as a heads up that an order should be incoming on your website OR an opportunity to sell to this customer.
            Either way, it’s on your admin login!
        </div>
    </div>
    <hr style="margin: 10px 0;">
    <div style="padding: 20px;">
        <table style="margin: auto;">
            <tr>
                <td>User Name:</td>
                <td>{{ $order->name }}</td>
            </tr>
            <tr>
                <td>User E-Mail:</td>
                <td>{{ $order->email }}</td>
            </tr>
            <tr>
                <td>User Phone:</td>
                <td>{{ $order->phone }}</td>
            </tr>
            <tr>
                <td>Date/Time:</td>
                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        </table>
    </div>
    <hr style="margin: 10px 0;">
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
            <td>$ {{ number_format($order->designs->sum(function($design) { return $design->size->price * $design->quantity; }), 2) }}</td>
        </tr>
        </tbody>
    </table>
@endsection
