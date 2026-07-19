// 数値(大)＋ラベル(小)の視覚的階層で「事実として淡々と」見せる部品。
// metricsが無い/空の作品もあるため、その場合はセクションごと非表示にする。
// 基本情報Cardとデザイン言語を統一するため、同じCard(5-5)で囲む。
import Card from '@/Components/ui/Card';
import { Work } from '@/types/work';

type Props = { metrics: Work['metrics'] };

export default function MetricsCard({ metrics }: Props) {
  if (!metrics || metrics.length === 0) return null;

  return (
    <Card>
      <div className="flex divide-x divide-border">
        {metrics.map((metric) => (
          <div key={metric.label} className="flex-1 text-center px-2 first:pl-0 last:pr-0">
            <p className="text-text font-mono text-2xl">
              {metric.value}
              {metric.unit && <span className="text-lg">{metric.unit}</span>}
            </p>
            <p className="text-text-muted text-xs mt-1">{metric.label}</p>
          </div>
        ))}
      </div>
    </Card>
  );
}
