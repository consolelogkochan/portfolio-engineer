import Button from '@/Components/ui/Button';
import { ContactInput, ContactSchema } from '@/types/contact';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type ContactFormData = ContactInput & {
  website: string; // ハニーポット（人間には見えない囮フィールド）
  form_token: string; // 時間トラップ用トークン
};

type ServerErrors = Partial<Record<keyof ContactInput, string>>;

type Props = {
  form_token: string;
};

const inputClasses =
  'w-full bg-surface border border-border rounded-md px-3 py-2 text-text placeholder:text-text-muted focus:border-primary focus:outline-none transition-colors';

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

  return (
    <div className="max-w-xl mx-auto">
      <Head title="お問い合わせ" />

      <h1 className="text-2xl font-bold mb-6">お問い合わせ</h1>

      {flash.success && (
        <p className="border border-primary text-primary bg-surface rounded-md px-4 py-3 text-sm mb-6">
          {flash.success}
        </p>
      )}

      {/* throttle:3,1（4-13）超過時、サーバー（bootstrap/app.php）がThrottleRequestsExceptionを
          redirect()->back()->with('rate_limited', ...) に変換済みのため、通常のflashとして届く。
          クライアント側で429を検知する必要はない。 */}
      {flash.rate_limited && (
        <p className="border border-emphasis text-emphasis bg-surface rounded-md px-4 py-3 text-sm mb-6">
          {flash.rate_limited}
        </p>
      )}

      <form onSubmit={handleSubmit} className="space-y-5">
        {/* ハニーポット：人間には見えない囮フィールド。支援技術が触れないよう aria-hidden を付ける。
            Tailwindのhiddenだけではaria-hidden属性は付与されないため、両方を明示的に維持する。 */}
        <div aria-hidden="true" className="hidden">
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

        <div className="space-y-1.5">
          <label htmlFor="name" className="text-sm text-text-muted">
            お名前 *
          </label>
          <input
            id="name"
            type="text"
            value={data.name}
            onChange={(e) => setData('name', e.target.value)}
            className={inputClasses}
          />
          {errors.name && <p className="text-error text-sm">{errors.name}</p>}
        </div>

        <div className="space-y-1.5">
          <label htmlFor="email" className="text-sm text-text-muted">
            メールアドレス *
          </label>
          <input
            id="email"
            type="email"
            value={data.email}
            onChange={(e) => setData('email', e.target.value)}
            className={inputClasses}
          />
          {errors.email && <p className="text-error text-sm">{errors.email}</p>}
        </div>

        <div className="space-y-1.5">
          <label htmlFor="subject" className="text-sm text-text-muted">
            件名 *
          </label>
          <input
            id="subject"
            type="text"
            value={data.subject}
            onChange={(e) => setData('subject', e.target.value)}
            className={inputClasses}
          />
          {errors.subject && <p className="text-error text-sm">{errors.subject}</p>}
        </div>

        <div className="space-y-1.5">
          <label htmlFor="message" className="text-sm text-text-muted">
            お問い合わせ内容 *
          </label>
          <textarea
            id="message"
            rows={6}
            value={data.message}
            onChange={(e) => setData('message', e.target.value)}
            className={inputClasses}
          />
          {errors.message && <p className="text-error text-sm">{errors.message}</p>}
        </div>

        <Button type="submit" disabled={processing} className="w-full">
          {processing ? '送信中...' : '送信'}
        </Button>
      </form>
    </div>
  );
}
