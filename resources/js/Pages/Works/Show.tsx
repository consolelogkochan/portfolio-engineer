import { Work } from '@/types/work';

type Props = Work;

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
  } = props;

  return (
    <div style={{ fontFamily: 'monospace', padding: '2rem', maxWidth: '720px' }}>
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
