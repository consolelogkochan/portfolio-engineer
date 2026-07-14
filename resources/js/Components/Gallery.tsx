// embla-carousel-react は headless（動作のみ提供）なライブラリ。
// 見た目（ボタン・ドット・カウンターの配色/形）は一切持たないため、全て自前トークンで組む。
// このコンポーネント自体の存在理由も「複数枚の画像を束ねて1つの体験として見せる」こと。
// 0枚は非表示、1枚は画像のみ（切り替えUIは無意味なので出さない）、複数枚のみ通常のカルーセルになる。
import { Work } from '@/types/work';
import useEmblaCarousel, { type UseEmblaCarouselType } from 'embla-carousel-react';
import { KeyboardEvent, useCallback, useSyncExternalStore } from 'react';

type Props = { gallery: Work['gallery'] };

// embla の内部状態（選択中インデックス等）は embla 自身が持つ外部ストアなので、
// useState+useEffectで複製せず useSyncExternalStore で直接同期する。
function subscribe(emblaApi: UseEmblaCarouselType[1]) {
  return (callback: () => void) => {
    if (!emblaApi) return () => {};
    emblaApi.on('select', callback).on('reInit', callback);
    return () => {
      emblaApi.off('select', callback).off('reInit', callback);
    };
  };
}

export default function Gallery({ gallery }: Props) {
  // loop:true のため末尾→先頭、先頭→末尾へ回り込む（常に両方向へ送れる＝前後ボタンの無効化は不要）
  const [emblaRef, emblaApi] = useEmblaCarousel({ loop: true });

  const selectedIndex = useSyncExternalStore(
    subscribe(emblaApi),
    () => emblaApi?.selectedScrollSnap() ?? 0
  );

  // キーボード操作（← →）：emblaコアにはキーボード制御が無いため手動で配線。スワイプ/ドラッグはembla標準機能。
  const handleKeyDown = useCallback(
    (e: KeyboardEvent) => {
      if (e.key === 'ArrowLeft') emblaApi?.scrollPrev();
      if (e.key === 'ArrowRight') emblaApi?.scrollNext();
    },
    [emblaApi]
  );

  if (gallery.length === 0) return null;

  const multiple = gallery.length > 1;
  const caption = gallery[selectedIndex]?.caption;

  return (
    <div>
      {/* px-10: スマホ幅では画像とコンテナの縦横比が近くcontainの余白が出ないため、
          ボタン/カウンター用のスペースを強制的に確保する（md以上は自然な余白で足りるため解除） */}
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
                  className="absolute inset-0 w-full h-full object-contain"
                />
              </div>
            ))}
          </div>
        </div>

        {multiple && (
          <>
            {/* カウンター：containで生まれる余白（画像右上のbg-surface部分）に配置 */}
            <span className="absolute top-1.5 right-1.5 md:top-2 md:right-2 text-text-muted text-[10px] md:text-xs font-mono">
              {selectedIndex + 1} / {gallery.length}
            </span>

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
