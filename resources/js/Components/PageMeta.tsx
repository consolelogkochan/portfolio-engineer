// 各ページ共通のdescription/OGP/Twitter Cardタグ。構造はここに集約し、
// ページごとの差分（title/description/image/type）だけを渡す（5-4/5-10と同じ「構造共通・中身固有」）。
//
// <title>要素自体はここで文字列を組まず、生のtitleをそのまま<Head title>に渡す。
// サフィックス付与はapp.tsxのcreateInertiaApp({ title })に一元化されているため、
// og:title等ではpageTitle()で同じ文字列を再現するだけで、二重にサフィックスを付けない。
//
// og:url・og:imageは絶対URLが必須。SSR無し（window不可の環境がありうる）のため、
// window.location.origin ではなくサーバー共有のapp_url（config('app.url')）を単一の情報源にする。
import { pageTitle } from '@/lib/siteMeta';
import { Head, usePage } from '@inertiajs/react';

const DEFAULT_OG_IMAGE = '/images/og/default.png';

type Props = {
  title?: string;
  description: string;
  ogImage?: string;
  ogType?: 'website' | 'article';
};

export default function PageMeta({
  title,
  description,
  ogImage = DEFAULT_OG_IMAGE,
  ogType = 'website',
}: Props) {
  const { props, url } = usePage<{ app_url: string }>();
  const origin = props.app_url.replace(/\/$/, '');
  const fullTitle = pageTitle(title);
  const absoluteImage = ogImage.startsWith('http') ? ogImage : `${origin}${ogImage}`;
  const absoluteUrl = `${origin}${url}`;

  return (
    <Head title={title}>
      <meta name="description" content={description} />

      <meta property="og:title" content={fullTitle} />
      <meta property="og:description" content={description} />
      <meta property="og:type" content={ogType} />
      <meta property="og:url" content={absoluteUrl} />
      <meta property="og:image" content={absoluteImage} />

      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content={fullTitle} />
      <meta name="twitter:description" content={description} />
      <meta name="twitter:image" content={absoluteImage} />
    </Head>
  );
}
