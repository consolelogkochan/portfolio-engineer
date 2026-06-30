import { LogSummary } from '@/types/log';

type Props = {
  logs: LogSummary[];
};

export default function Index({ logs }: Props) {
  return (
    <div style={{ fontFamily: 'monospace', padding: '2rem', maxWidth: '720px' }}>
      <h1>ビルドログ一覧（draft以外・新しい順）</h1>
      <p style={{ color: '#888' }}>{logs.length} 件</p>
      <table style={{ borderCollapse: 'collapse', width: '100%', marginTop: '1rem' }}>
        <thead>
          <tr style={{ borderBottom: '2px solid #333' }}>
            <th style={{ textAlign: 'left', padding: '4px 12px 4px 0' }}>publishedAt</th>
            <th style={{ textAlign: 'left', padding: '4px 12px 4px 0' }}>slug</th>
            <th style={{ textAlign: 'left', padding: '4px 12px 4px 0' }}>title</th>
            <th style={{ textAlign: 'left', padding: '4px 0' }}>tags</th>
          </tr>
        </thead>
        <tbody>
          {logs.map((log) => (
            <tr key={log.slug} style={{ borderBottom: '1px solid #ddd' }}>
              <td style={{ padding: '6px 12px 6px 0', whiteSpace: 'nowrap' }}>{log.publishedAt}</td>
              <td style={{ padding: '6px 12px 6px 0', color: '#555' }}>{log.slug}</td>
              <td style={{ padding: '6px 12px 6px 0' }}>{log.title}</td>
              <td style={{ padding: '6px 0', color: '#888' }}>{log.tags.join(', ')}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
