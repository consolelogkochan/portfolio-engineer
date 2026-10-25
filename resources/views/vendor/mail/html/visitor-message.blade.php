{{-- 訪問者の本文。HTML は ContactMail が作り、1行にしてある（理由は ContactMail::content） --}}
@props(['html', 'text'])
<div class="visitor-message">{!! $html !!}</div>
