import WorkCard from '@/Components/WorkCard';
import { WorkSummary } from '@/types/work';

type Props = {
  works: WorkSummary[];
};

export default function Index({ works }: Props) {
  return (
    <div>
      <h1 className="text-2xl font-bold mb-2">Works</h1>
      <p className="text-text-muted text-sm mb-6">{works.length} 件</p>
      {works.length === 0 ? (
        <p className="text-text-muted">まだ作品がありません</p>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {works.map((work) => (
            <WorkCard key={work.slug} work={work} />
          ))}
        </div>
      )}
    </div>
  );
}
