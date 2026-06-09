<?php

namespace App\Http\Controllers;

use App\Mail\ContactInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return back()->with('success', 'Pesan Anda telah terkirim. Tim kami akan segera menghubungi Anda.');
        }

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'company' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $contactEmail = config('marketing.contact_email');

        if ($contactEmail) {
            Mail::to($contactEmail)->send(new ContactInquiry($validated));
        }

        return back()->with('success', 'Pesan Anda telah terkirim. Tim kami akan segera menghubungi Anda.');
    }
}
