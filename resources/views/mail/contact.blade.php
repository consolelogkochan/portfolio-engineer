<x-mail::message>
# お問い合わせが届きました

**お名前：** {{ $userName }}
**メールアドレス：** {{ $userEmail }}

---

{{ $userMessage }}

---

このメールへの返信先は {{ $userEmail }} に設定されています（Reply-To）。

{{ config('app.name') }}
</x-mail::message>
