<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use App\Services\PageMetaBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(private readonly PageMetaBuilder $pageMeta) {}

    public function create(): Response
    {
        $meta = $this->pageMeta->build('contact');

        return Inertia::render('Contact/Index', [
            // 時間トラップ用：表示時刻を暗号化してクライアントに渡す。
            // 送信時に復号し、経過秒数を検証する（ContactRequest::detectSpam）。
            'form_token' => Crypt::encryptString((string) time()),
            'pageTitle' => $meta['title'],
        ])->withViewData(['pageMeta' => $meta]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        // コントローラ到達時点で ContactRequest により通常バリデーション済み。
        // 次に中身ベースのスパム判定（ハニーポット・時間トラップ・URL数）を行う。
        $spamReason = $request->detectSpam();

        if ($spamReason !== null) {
            // 誤検知把握のためログに残す。入力内容は記録しない。
            Log::warning('Contact spam detected', [
                'reason' => $spamReason,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // ボットの特徴（ハニーポット・時間トラップ）は静かに弾く：
            // ボットにはメールが送られたように見せ、実際には送らない。
            if (in_array($spamReason, ['honeypot', 'too_fast'], true)) {
                return redirect()->back()->with('success', 'お問い合わせを受け付けました。折り返しご連絡いたします。');
            }

            // 人間が現実に掛かりうる層（URL数・トークン破損）は、本人が気づいて
            // 送り直せるようにエラーを返す。メールは送らない。
            $message = $spamReason === 'too_many_urls'
                ? '本文に含まれるリンクが多すぎます。リンクの数を減らして、もう一度お試しください。'
                : '送信内容を確認できませんでした。お手数ですが、ページを再読み込みしてから、もう一度お試しください。';

            return redirect()->back()->withInput()->withErrors(['message' => $message]);
        }

        $data = $request->validated();

        Mail::to(config('mail.contact_to'))->send(new ContactMail(
            userName: $data['name'],
            userEmail: $data['email'],
            contactSubject: $data['subject'],
            userMessage: $data['message'],
        ));

        return redirect()->back()->with('success', 'お問い合わせを受け付けました。折り返しご連絡いたします。');
    }
}
