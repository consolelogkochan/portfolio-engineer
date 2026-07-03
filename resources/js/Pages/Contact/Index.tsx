// 確認用UI（フェーズ5で本番体裁に整える）
import { ContactInput, ContactSchema } from '@/types/contact';
import { useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type ServerErrors = Partial<Record<keyof ContactInput, string>>;

export default function Index() {
  const { props } = usePage<{ flash: { success?: string }; errors: ServerErrors }>();
  const flash = props.flash ?? {};
  const serverErrors: ServerErrors = props.errors ?? {};

  const { data, setData, post, processing } = useForm<ContactInput>({
    name: '',
    email: '',
    subject: '',
    message: '',
  });

  const [clientErrors, setClientErrors] = useState<Partial<Record<keyof ContactInput, string>>>({});

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();

    // クライアント検証（親切な二層目）：失敗時はサーバーに送らない
    const result = ContactSchema.safeParse(data);
    if (!result.success) {
      const fieldErrors: Partial<Record<keyof ContactInput, string>> = {};
      for (const issue of result.error.issues) {
        const field = issue.path[0] as keyof ContactInput;
        if (!fieldErrors[field]) fieldErrors[field] = issue.message;
      }
      setClientErrors(fieldErrors);
      return;
    }

    setClientErrors({});
    post('/contact');
  }

  // クライアントエラーがあればそちら優先、なければサーバーエラーを表示。
  // processing 中（post() 呼び出し〜レスポンス到着）は serverErrors を隠す。
  // これにより「クライアント検証を通ったのに前回のサーバーエラーが一瞬残る」を防ぐ。
  const errors: Partial<Record<keyof ContactInput, string>> =
    Object.keys(clientErrors).length > 0 ? clientErrors : processing ? {} : serverErrors;

  const fieldStyle = { display: 'flex', flexDirection: 'column' as const, gap: '4px' };
  const inputStyle = {
    padding: '6px 8px',
    fontSize: '14px',
    width: '100%',
    boxSizing: 'border-box' as const,
  };
  const errorStyle = { color: '#c00', fontSize: '13px' };

  return (
    <div style={{ fontFamily: 'monospace', padding: '2rem', maxWidth: '560px' }}>
      <h1>お問い合わせ</h1>

      {flash.success && (
        <p style={{ color: 'green', border: '1px solid green', padding: '8px' }}>{flash.success}</p>
      )}

      <form
        onSubmit={handleSubmit}
        style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}
      >
        <div style={fieldStyle}>
          <label htmlFor="name">お名前 *</label>
          <input
            id="name"
            type="text"
            value={data.name}
            onChange={(e) => setData('name', e.target.value)}
            style={inputStyle}
          />
          {errors.name && <span style={errorStyle}>{errors.name}</span>}
        </div>

        <div style={fieldStyle}>
          <label htmlFor="email">メールアドレス *</label>
          <input
            id="email"
            type="email"
            value={data.email}
            onChange={(e) => setData('email', e.target.value)}
            style={inputStyle}
          />
          {errors.email && <span style={errorStyle}>{errors.email}</span>}
        </div>

        <div style={fieldStyle}>
          <label htmlFor="subject">件名 *</label>
          <input
            id="subject"
            type="text"
            value={data.subject}
            onChange={(e) => setData('subject', e.target.value)}
            style={inputStyle}
          />
          {errors.subject && <span style={errorStyle}>{errors.subject}</span>}
        </div>

        <div style={fieldStyle}>
          <label htmlFor="message">お問い合わせ内容 *</label>
          <textarea
            id="message"
            rows={6}
            value={data.message}
            onChange={(e) => setData('message', e.target.value)}
            style={inputStyle}
          />
          {errors.message && <span style={errorStyle}>{errors.message}</span>}
        </div>

        <button
          type="submit"
          disabled={processing}
          style={{ padding: '8px 24px', cursor: processing ? 'not-allowed' : 'pointer' }}
        >
          {processing ? '送信中...' : '送信'}
        </button>
      </form>
    </div>
  );
}
