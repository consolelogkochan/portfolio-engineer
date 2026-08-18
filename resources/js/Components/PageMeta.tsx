// <title>要素のみを、サーバー側（PHP: app/Services/PageMetaBuilder.php）で組み立て済みの
// 完成形文字列でそのまま出力する（サイト名サフィックス付与済み。加工しない）。
//
// og:*/twitter:*/descriptionはここでは出力しない（7-2）。理由：
// resources/views/app.blade.php がBladeから直接サーバーサイド出力するようになったため。
// React側で同名タグを重ねて出すと、Blade由来のタグはdata-inertia属性を持たずInertiaの
// Renderer.update管理対象外となる一方、React由来のタグは別途data-inertia付きで追加されるため、
// DOM上でmeta要素が重複して残ってしまう（実測で確認済み）。<title>だけはInertia core側に
// 「data-inertia無しのtitleを強制削除する」特別処理があるため、両方に書いても重複しない。
import { Head } from '@inertiajs/react';

type Props = {
  title: string;
};

export default function PageMeta({ title }: Props) {
  return <Head title={title} />;
}
