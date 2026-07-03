<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Contact/Index');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        // コントローラ到達時点で ContactRequest により検証済み。
        // validated() で許可フィールドのみを取得する（マスアサインメント的な防御）。
        $data = $request->validated();

        Mail::to(config('mail.contact_to'))->send(new ContactMail(
            userName:       $data['name'],
            userEmail:      $data['email'],
            contactSubject: $data['subject'],
            userMessage:    $data['message'],
        ));

        return redirect()->back()->with('success', 'お問い合わせを受け付けました。折り返しご連絡いたします。');
    }
}
