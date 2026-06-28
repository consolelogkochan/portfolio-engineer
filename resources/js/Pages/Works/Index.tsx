import { Work } from '@/types/work';

type WorkSummary = Pick<
  Work,
  'title' | 'category' | 'status' | 'summary' | 'publishedAt' | 'thumbnail'
> & {
  slug: string;
};

type Props = {
  works: WorkSummary[];
};

export default function Index({ works }: Props) {
  return (
    <div style={{ fontFamily: 'monospace', padding: '2rem', maxWidth: '720px' }}>
      <h1>作品一覧（公開中・新しい順）</h1>
      <p style={{ color: '#888' }}>{works.length} 件</p>
      <table style={{ borderCollapse: 'collapse', width: '100%', marginTop: '1rem' }}>
        <thead>
          <tr style={{ borderBottom: '2px solid #333' }}>
            <th style={{ textAlign: 'left', padding: '4px 12px 4px 0' }}>publishedAt</th>
            <th style={{ textAlign: 'left', padding: '4px 12px 4px 0' }}>slug</th>
            <th style={{ textAlign: 'left', padding: '4px 12px 4px 0' }}>title</th>
            <th style={{ textAlign: 'left', padding: '4px 0' }}>category</th>
          </tr>
        </thead>
        <tbody>
          {works.map((work) => (
            <tr key={work.slug} style={{ borderBottom: '1px solid #ddd' }}>
              <td style={{ padding: '6px 12px 6px 0', whiteSpace: 'nowrap' }}>
                {work.publishedAt}
              </td>
              <td style={{ padding: '6px 12px 6px 0', color: '#555' }}>{work.slug}</td>
              <td style={{ padding: '6px 12px 6px 0' }}>{work.title}</td>
              <td style={{ padding: '6px 0', color: '#888' }}>{work.category}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
