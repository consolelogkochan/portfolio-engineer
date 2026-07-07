// 確認用UI（フェーズ5で本番体裁に整える）
import Button from '@/Components/ui/Button';
import { ContactInput, ContactSchema } from '@/types/contact';
import { useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type ContactFormData = ContactInput & {
  website: string; // ハニーポット（人間には見えない囮フィールド）
  form_token: string; // 時間トラップ用トークン
};

type ServerErrors = Partial<Record<keyof ContactInput, string>>;

type Props = {
  form_token: string;
};

export default function Index({ form_token }: Props) {
  const { props } = usePage<{
    flash: { success?: string; rate_limited?: string };
    errors: ServerErrors;
  }>();
  const flash = props.flash ?? {};
  const serverErrors: ServerErrors = props.errors ?? {};

  const { data, setData, post, processing } = useForm<ContactFormData>({
    name: '',
    email: '',
    subject: '',
    message: '',
    website: '', // ハニーポット：常に空のまま送信される
    form_token, // 表示時刻の暗号化トークン
  });

  const [clientErrors, setClientErrors] = useState<Partial<Record<keyof ContactInput, string>>>({});

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();

    // クライアント検証（親切な二層目）：ユーザー入力フィールドのみ検証
    const result = ContactSchema.safeParse({
      name: data.name,
      email: data.email,
      subject: data.subject,
      message: data.message,
    });
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

      {flash.rate_limited && (
        <p style={{ color: '#c60', border: '1px solid #c60', padding: '8px' }}>
          {flash.rate_limited}
        </p>
      )}

      <form
        onSubmit={handleSubmit}
        style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}
      >
        {/* ハニーポット：人間には見えない囮フィールド。支援技術が触れないよう aria-hidden を付ける */}
        <div aria-hidden="true" style={{ display: 'none' }}>
          <label htmlFor="website">Website</label>
          <input
            id="website"
            type="text"
            name="website"
            value={data.website}
            onChange={(e) => setData('website', e.target.value)}
            tabIndex={-1}
            autoComplete="off"
          />
        </div>

        {/* 時間トラップ：暗号化された表示時刻を隠しフィールドで返送 */}
        <input type="hidden" name="form_token" value={data.form_token} />

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

        <Button type="submit" disabled={processing}>
          {processing ? '送信中...' : '送信'}
        </Button>
      </form>
    </div>
  );
}
