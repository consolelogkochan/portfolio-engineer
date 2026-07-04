<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Contact/Index', [
            // 時間トラップ用：表示時刻を暗号化してクライアントに渡す。
            // 送信時に復号し、経過秒数を検証する（ContactRequest::detectSpam）。
            'form_token' => Crypt::encryptString((string) time()),
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        // コントローラ到達時点で ContactRequest により通常バリデーション済み。
        // 次に中身ベースのスパム判定（ハニーポット・時間トラップ・URL数）を行う。
        $spamReason = $request->detectSpam();

        if ($spamReason !== null) {
            // 静かに弾く：ボットにはメールが送られたように見せ、実際には送らない。
            // 誤検知把握のためログに残す。
            Log::warning('Contact spam detected', [
                'reason'     => $spamReason,
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return redirect()->back()->with('success', 'お問い合わせを受け付けました。折り返しご連絡いたします。');
        }

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
