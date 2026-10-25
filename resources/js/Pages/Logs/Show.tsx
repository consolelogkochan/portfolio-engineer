import PageMeta from '@/Components/PageMeta';
import Tag from '@/Components/ui/Tag';
import { Log } from '@/types/log';
import { Link } from '@inertiajs/react';

// slugはLogSchemaの一部ではなく、Controllerがルートパラメータから渡すもの
// pageTitleはLogController::showがPageMetaBuilderで組み立てた完成形タイトル
// （ogImageの解決もPageMetaBuilder側に一本化されたため、ここでは受け取らない）
type Props = Log & {
  slug: string;
  bodyHtml: string;
  hasRelatedWork: boolean;
  pageTitle: string;
};

export default function Show(props: Props) {
  const { title, publishedAt, updatedAt, tags, slug, hasRelatedWork, bodyHtml, pageTitle } = props;

  return (
    // mainのmax-w-4xlより一段狭いmax-w-3xlに委ねる（5-15 Aboutと同じ解）。
    // max-w-proseのままだと大画面で本文だけ狭まりmx-auto無しで左寄る＝右に不自然な余白ができるため、
    // ページ全体（見出し・タグ・本文）を同じ幅に揃える。
    <div className="max-w-3xl mx-auto">
      <PageMeta title={pageTitle} />

      {hasRelatedWork && (
        <Link
          href={`/works/${slug}`}
          className="inline-block bg-surface border border-border rounded-md px-3 py-1.5 md:px-4 md:py-2 text-xs md:text-sm text-text-muted hover:border-primary hover:text-primary transition-colors mb-3 md:mb-4"
        >
          ← 作品ページに戻る
        </Link>
      )}

      <h1 className="text-xl md:text-2xl font-bold mb-1">{title}</h1>
      <p className="text-text-muted text-xs md:text-sm mb-2">
        {publishedAt}
        {updatedAt && `（更新：${updatedAt}）`}
      </p>
      {tags.length > 0 && (
        <div className="flex flex-wrap gap-2 mb-6 md:mb-8">
          {tags.map((t) => (
            <Tag key={t}>{t}</Tag>
          ))}
        </div>
      )}

      {/*
       * bodyHtml は LogController がサーバー側で作った HTML（元は content/logs/*.md）。
       * dangerouslySetInnerHTML で出してよい根拠と、その前提が崩れる条件は
       * app/Services/MarkdownRenderer.php のクラスの説明を参照。
       */}
      <div className="case-study" dangerouslySetInnerHTML={{ __html: bodyHtml }} />
    </div>
  );
}
