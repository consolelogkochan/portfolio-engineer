import { Work } from '@/types/work';
import { Head } from '@inertiajs/react';

type Props = Work & { bodyHtml: string };

export default function Show(props: Props) {
  const {
    title,
    category,
    status,
    summary,
    publishedAt,
    period,
    role,
    technologies,
    aiTools,
    thumbnail,
    featured,
    bodyHtml,
  } = props;

  return (
    <div style={{ fontFamily: 'monospace', padding: '2rem', maxWidth: '720px' }}>
      <Head title={title} />
      <h1>{title}</h1>
      <table style={{ borderCollapse: 'collapse', width: '100%' }}>
        <tbody>
          <Row label="category" value={category} />
          <Row label="status" value={status} />
          <Row label="featured" value={String(featured)} />
          <Row label="publishedAt" value={publishedAt} />
          <Row label="period.start" value={period.start} />
          <Row label="period.end" value={period.end ?? '—'} />
          <Row label="summary" value={summary} />
          <Row label="role" value={role.join(', ')} />
          <Row label="technologies" value={technologies.join(', ')} />
          <Row label="aiTools" value={aiTools.join(', ')} />
          <Row label="thumbnail" value={thumbnail} />
        </tbody>
      </table>
      {/*
       * dangerouslySetInnerHTML を許容する根拠：
       * bodyHtml は WorkController でサーバー側に生成済みの HTML 文字列。
       * 元データは content/works/*.md（Git管理・著者のみ編集可）であり、
       * ユーザー入力や外部入力は一切経由しない。
       * PHP側 MarkdownRenderer の allow_unsafe_links=false により
       * javascript: リンクは除去されている。
       * この前提（著者管理コンテンツ）が崩れる場合は使用禁止。
       * 確認用表示。本番の体裁はフェーズ5。
       */}
      <hr style={{ margin: '2rem 0' }} />
      <div dangerouslySetInnerHTML={{ __html: bodyHtml }} />
    </div>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <tr style={{ borderBottom: '1px solid #ddd' }}>
      <td style={{ padding: '4px 12px 4px 0', color: '#888', whiteSpace: 'nowrap' }}>{label}</td>
      <td style={{ padding: '4px 0' }}>{value}</td>
    </tr>
  );
}
