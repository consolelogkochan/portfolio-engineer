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
      <div className="flex flex-col gap-4">
        {works.map((work) => (
          <WorkCard key={work.slug} work={work} />
        ))}
      </div>
    </div>
  );
}
