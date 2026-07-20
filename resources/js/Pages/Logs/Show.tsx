import Tag from '@/Components/ui/Tag';
import { Log } from '@/types/log';
import { Head, Link } from '@inertiajs/react';

// slugはLogSchemaの一部ではなく、Controllerがルートパラメータから渡すもの
type Props = Log & { slug: string; bodyHtml: string; hasRelatedWork: boolean };

export default function Show(props: Props) {
  const {
    title,
    publishedAt,
    updatedAt,
    // frontmatterにtagsキーが無いとprops上undefinedになりうるため防御的にデフォルトを設ける
    tags = [],
    slug,
    hasRelatedWork,
    bodyHtml,
  } = props;

  return (
    <div>
      <Head title={title} />

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
       * dangerouslySetInnerHTML を許容する根拠：
       * bodyHtml は LogController でサーバー側に生成済みの HTML 文字列。
       * 元データは content/logs/*.md（Git管理・著者のみ編集可）であり、
       * ユーザー入力や外部入力は一切経由しない。
       * PHP側 MarkdownRenderer の allow_unsafe_links=false により
       * javascript: リンクは除去されている。
       * この前提（著者管理コンテンツ）が崩れる場合は使用禁止。
       */}
      <div className="case-study max-w-prose" dangerouslySetInnerHTML={{ __html: bodyHtml }} />
    </div>
  );
}
