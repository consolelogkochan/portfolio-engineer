// トップページ：ヒーロー（3行キャッチ）→ featured作品ショーケース → 他ページへの最小限の導線。
// featuredの絞り込みはサーバー（ContentRepository::listFeaturedWorks）が担うため、
// ここでは受け取った featuredWorks をそのまま FeaturedShowcase に渡すだけ（表示に集中）。
import FeaturedShowcase from '@/Components/FeaturedShowcase';
import { buttonClasses } from '@/Components/ui/Button';
import { WorkSummary } from '@/types/work';
import { Head, Link } from '@inertiajs/react';

type Props = {
  featuredWorks: WorkSummary[];
};

export default function Home({ featuredWorks }: Props) {
  return (
    <div>
      <Head title="portfolio-engineer — AIと作る。判断は、自分で。" />

      <section className="text-center py-12 md:py-20">
        <p className="font-mono text-base md:text-lg lg:text-xl text-text-muted mb-6">
          portfolio-engineer
        </p>
        {/* 3行キャッチ（仮置き）：1行目を強く、3行目を控えめにして階層をつける。
            ただし3行目はサイトの核心（AIとの協働プロセス）なので、抑えつつも読める大きさは確保する。
            スマホ2xl／タブレット4xl／PC6xlで、ヒーローだけ大きくジャンプさせて主張を強める */}
        <h1 className="text-2xl md:text-4xl lg:text-6xl font-bold text-primary leading-snug mb-4">
          AIと作る。判断は、自分で。
        </h1>
        <p className="text-lg md:text-xl lg:text-2xl text-text leading-relaxed mb-3">
          このサイトは、その実践です。
        </p>
        <p className="text-base md:text-lg lg:text-xl text-text-muted leading-relaxed max-w-xl mx-auto">
          AIと協働し、その提案を検証しながら、100枚以上のカードで一つずつ判断を重ねてきました。
        </p>
      </section>

      {/* featuredが0件の場合はFeaturedShowcaseがnullを返しセクションごと消える。
          Works誘導は下に残るため、その場合でも導線は失われない。 */}
      {featuredWorks.length > 0 && (
        <section className="mb-16 md:mb-24">
          <h2 className="text-sm md:text-base lg:text-lg font-mono text-text-muted mb-4">
            Featured Works
          </h2>
          <FeaturedShowcase works={featuredWorks} />
        </section>
      )}

      <section className="text-center py-10 border-t border-border">
        <Link href="/works" className={buttonClasses()}>
          すべての作品を見る
        </Link>

        <div className="flex justify-center gap-6 mt-6 text-sm">
          <Link href="/about" className="text-text-muted hover:text-primary">
            About
          </Link>
          <Link href="/contact" className="text-text-muted hover:text-primary">
            Contact
          </Link>
        </div>
      </section>
    </div>
  );
}
