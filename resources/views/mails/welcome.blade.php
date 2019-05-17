@extends('layouts.mail')

@section('mail-body')
    <h1 style="text-align: center; font-weight: bold; font-size: 2rem;">
        Welcome to Build a Gang Sheet – Get Started Now!
    </h1>
    <div style="padding: 0 20px;">
        <div style="font-size: 1rem; text-align: start;">
            Dear {{$merchant->first_name ?? ''}} {{$merchant->last_name ?? ''}},

            <p>
                Welcome to Build a Gang Sheet!
            </p>
            <p>
                We're thrilled to have you on board and can't wait for you to start exploring all the amazing features we've designed to help you create and manage your gang sheets effortlessly.
            </p>

            <b>Getting Started:</b>
            <p>
                To kick things off, here's a quick guide to get you up and running.
            </p>

            <ul>
                <li>Choose a name for your shop and upload its logo.</li>
                <li>Create your own product and incorporate size options.</li>
                <li>Customize the button with a title and color, and set the checkout redirect URL.</li>
                <li>Obtain the script for your product and integrate it into your website.</li>
                <li>Buy credits to access the gang sheets ordered for download.</li>
            </ul>

            <p>
                Thank you for choosing Build a Gang Sheet. We're excited to see what you'll create!
            </p>

            <b>
                Warm regards.
            </b>
        </div>
    </div>
@endsection
