<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function customIntegration()
    {
        return inertia('Document/CustomIntegration', [
            'document' => [
                'title' => 'Custom Integration',
                'description' => 'Custom Integration Description',
                'content' => file_get_contents(public_path('docs/custom-integration.md'))
            ]
        ]);
    }
}
