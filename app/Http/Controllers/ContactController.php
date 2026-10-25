<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SpamReason;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use App\Services\PageMetaBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ContactController extends Controller
{
    /**
     * 成功の文言。本当に送ったときと、ボットを静かに弾いたとき（ハニーポット・時間トラップ）の
     * 2か所で使う。ボットに違いを見せないため、2か所は同じでなければならない。
     */
    private const string SUCCESS_MESSAGE = 'お問い合わせを受け付けました。折り返しご連絡いたします。';

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
                'reason' => $spamReason->value,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // 静かに弾くか、エラーを返すかの分け方は SpamReason::isSilent()
            if ($spamReason->isSilent()) {
                return redirect()->back()->with('success', self::SUCCESS_MESSAGE);
            }

            // エラーを返す層。メールは送らない。
            $message = $spamReason === SpamReason::TooManyUrls
                ? '本文に含まれるリンクが多すぎます。リンクの数を減らして、もう一度お試しください。'
                : '送信内容を確認できませんでした。お手数ですが、ページを再読み込みしてから、もう一度お試しください。';

            return redirect()->back()->withInput()->withErrors(['general' => $message]);
        }

        $data = $request->validated();

        try {
            Mail::to(config('mail.contact_to'))->send(new ContactMail(
                userName: $data['name'],
                userEmail: $data['email'],
                contactSubject: $data['subject'],
                userMessage: $data['message'],
            ));
        } catch (TransportExceptionInterface $e) {
            // 送信失敗の原因把握のためログに残す。入力内容は記録しない。
            Log::error('Contact mail send failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return redirect()->back()->withInput()->withErrors([
                'general' => '申し訳ありません。メールの送信に失敗しました。お時間をおいて、もう一度お試しください。',
            ]);
        }

        return redirect()->back()->with('success', self::SUCCESS_MESSAGE);
    }
}
