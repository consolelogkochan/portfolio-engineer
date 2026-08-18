// 404/403/500/503共通のエラーページ（6-2）。statusで中身（例外名・コメント文）だけ出し分け、
// 構造（数字→コード風の一文→コメント→トップへ戻る）は共通。
// bootstrap/app.php の respond() から Inertia::render('ErrorPage', ['status' => $statusCode]) で呼ばれる。
// BaseLayout（ヘッダー・ナビ）は app.tsx の resolve() が自動で適用するため、ここでは意識しない。
import PageMeta from '@/Components/PageMeta';
import { Link } from '@inertiajs/react';

type Props = { status: number };

type StatusContent = { exceptionName: string; comment: string; title: string; description: string };

const STATUS_CONTENT: Record<number, StatusContent> = {
  404: {
    exceptionName: 'PageNotFoundException',
    comment: 'お探しのページは見つかりませんでした',
    title: 'ページが見つかりません',
    description:
      'お探しのページは見つかりませんでした。URLをご確認いただくか、トップページからお探しください。',
  },
  403: {
    exceptionName: 'ForbiddenException',
    comment: 'このページにはアクセスできません',
    title: 'アクセスできません',
    description: 'このページへのアクセス権限がありません。',
  },
  500: {
    exceptionName: 'InternalServerError',
    comment: 'サーバー側で問題が発生しました',
    title: 'サーバーエラー',
    description: 'サーバー側で問題が発生しました。しばらくしてから再度お試しください。',
  },
  503: {
    exceptionName: 'ServiceUnavailableException',
    comment: '現在メンテナンス中です',
    title: 'メンテナンス中',
    description: '現在メンテナンス中です。しばらくしてから再度お試しください。',
  },
};

// 上記4種以外のstatusが渡ってきた場合のフォールバック
const FALLBACK_CONTENT: StatusContent = {
  exceptionName: 'UnexpectedErrorException',
  comment: 'エラーが発生しました',
  title: 'エラーが発生しました',
  description: '予期しないエラーが発生しました。しばらくしてから再度お試しください。',
};

export default function ErrorPage({ status }: Props) {
  const { exceptionName, comment, title } = STATUS_CONTENT[status] ?? FALLBACK_CONTENT;

  return (
    <div className="flex flex-col items-center text-center py-24 md:py-32 lg:py-40 font-mono">
      <PageMeta title={title} />
      {/* 数字：ページの主役。見せ場なのでlgまで段階調整する（app.cssのlg基準を参照） */}
      <p className="text-7xl md:text-8xl lg:text-9xl font-bold text-primary mb-8">{status}</p>

      {/* コード風の一文：throw/new=青、例外名=黄土色、();=mutedでシンタックスハイライトを模す */}
      <p className="text-base md:text-lg mb-2">
        <span className="text-syntax-keyword">throw</span>{' '}
        <span className="text-syntax-keyword">new</span>{' '}
        <span className="text-syntax-class">{exceptionName}</span>
        <span className="text-text-muted">();</span>
      </p>

      {/* コメント風日本語：コメント色として既存のprimary（緑）を流用 */}
      <p className="text-sm md:text-base text-primary mb-12">// {comment}</p>

      {/* タグ風の「トップへ戻る」。<a>/</a>部分は装飾テキストで、Link自体が唯一の実リンク（全体がクリック可能） */}
      <Link href="/" className="text-sm md:text-base hover:underline">
        <span className="text-text-muted">{'<'}</span>
        <span className="text-syntax-keyword">a</span>
        <span className="text-text-muted">{'>'}</span>
        <span className="text-text">トップへ戻る</span>
        <span className="text-text-muted">{'</'}</span>
        <span className="text-syntax-keyword">a</span>
        <span className="text-text-muted">{'>'}</span>
      </Link>
    </div>
  );
}
