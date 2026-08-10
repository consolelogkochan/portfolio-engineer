export const SITE_NAME = 'portfolio-engineer';

// app.tsx の createInertiaApp({ title }) と同じサフィックス規則をここに一元化する。
// <Head title={...}> 自体はこの関数を通さず生のtitleを渡すこと（app.tsxのcallbackが
// <title>要素に対して一度だけ適用する）。PageMetaのog:title等、<title>と同じ文字列を
// 別のタグにも出す必要がある箇所でこの関数を使う。
export function pageTitle(title?: string): string {
  return title ? `${title} — ${SITE_NAME}` : SITE_NAME;
}
