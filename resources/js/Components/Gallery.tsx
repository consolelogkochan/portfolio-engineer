// embla-carousel-react は headless（動作のみ提供）なライブラリ。
// 見た目（ボタン・ドット・カウンターの配色/形）は一切持たないため、全て自前トークンで組む。
// このコンポーネント自体の存在理由も「複数枚の画像を束ねて1つの体験として見せる」こと。
// 0枚は非表示、1枚は画像のみ（切り替えUIは無意味なので出さない）、複数枚のみ通常のカルーセルになる。
import { useCarousel } from '@/hooks/useCarousel';
import { Work } from '@/types/work';

type Props = { gallery: Work['gallery'] };

export default function Gallery({ gallery }: Props) {
  const { emblaRef, emblaApi, selectedIndex, handleKeyDown } = useCarousel();

  if (gallery.length === 0) return null;

  const multiple = gallery.length > 1;
  const caption = gallery[selectedIndex]?.caption;

  return (
    <div>
      {/* カウンターは画像の直前に通常フロー（絶対配置ではない）の行として置く。
          containの余白の出方に依存せず、幅・比率に関わらず画像と重なりようがない。
          位置は右上のまま（FeaturedShowcase・従来のカウンター位置を踏襲）で見た目の一貫性を保つ */}
      {multiple && (
        <div className="flex justify-end mb-1.5 md:mb-2">
          <span className="text-text-muted text-[10px] md:text-xs font-mono">
            {selectedIndex + 1} / {gallery.length}
          </span>
        </div>
      )}

      {/* px-10: スマホ幅では画像とコンテナの縦横比が近くcontainの余白が出ないため、
          前後ボタン用のスペースを強制的に確保する（md以上は自然な余白で足りるため解除） */}
      <div className="relative px-10 md:px-0">
        <div
          className="overflow-hidden rounded-lg"
          ref={emblaRef}
          tabIndex={multiple ? 0 : undefined}
          onKeyDown={multiple ? handleKeyDown : undefined}
        >
          <div className="flex">
            {gallery.map((item, i) => (
              <div
                key={i}
                className="relative flex-[0_0_100%] min-w-0 aspect-video md:aspect-auto md:h-96 bg-surface"
              >
                <img
                  src={item.src}
                  alt={item.alt}
                  loading={i === 0 ? 'eager' : 'lazy'}
                  className="absolute inset-0 w-full h-full object-contain"
                />
              </div>
            ))}
          </div>
        </div>

        {multiple && (
          <>
            <button
              type="button"
              onClick={() => emblaApi?.scrollPrev()}
              aria-label="前へ"
              className="absolute left-1 md:left-2 top-1/2 -translate-y-1/2 w-7 h-7 md:w-8 md:h-8 rounded-full bg-primary text-background hover:text-text transition-colors flex items-center justify-center"
            >
              <span className="text-lg md:text-xl leading-none">‹</span>
            </button>
            <button
              type="button"
              onClick={() => emblaApi?.scrollNext()}
              aria-label="次へ"
              className="absolute right-1 md:right-2 top-1/2 -translate-y-1/2 w-7 h-7 md:w-8 md:h-8 rounded-full bg-primary text-background hover:text-text transition-colors flex items-center justify-center"
            >
              <span className="text-lg md:text-xl leading-none">›</span>
            </button>
          </>
        )}
      </div>

      {caption && <p className="text-text-muted text-xs text-center mt-3">{caption}</p>}

      {multiple && (
        <div className="flex justify-center gap-2 mt-2">
          {gallery.map((_, i) => (
            <button
              key={i}
              type="button"
              aria-label={`${i + 1}枚目へ`}
              onClick={() => emblaApi?.scrollTo(i)}
              className={`w-2 h-2 rounded-full ${i === selectedIndex ? 'bg-primary' : 'bg-border'}`}
            />
          ))}
        </div>
      )}
    </div>
  );
}
