// About は works/logs のような複数コンテンツの一覧・詳細ではなく単一固定ページ。
// 更新頻度が低く1件しかないため、Zodスキーマ・validate-content への追加は行わず、
// props の型は素朴な type 定義のみで表現する（AboutController::__invoke 参照）。
import Tag from '@/Components/ui/Tag';
import { Head } from '@inertiajs/react';
import React from 'react';

type Props = {
  name: string;
  title: string;
  location: string;
  github: string;
  avatar: string;
  skills: { backend: string[]; frontend: string[] };
  bodyHtml: string;
};

export default function About({ name, title, location, github, avatar, skills, bodyHtml }: Props) {
  return (
    // mainのmax-w-4xlより一段狭いmax-w-3xlで全体を包む（中間案）。
    // グリッドと本文を同じラッパーで揃えることで、幅は絞りつつ「2カラムと本文の幅は揃える」を維持する
    <div className="max-w-3xl mx-auto">
      <Head title="About" />

      <div className="grid grid-cols-1 md:grid-cols-[auto_1fr] gap-8 mb-12">
        {/* 画像の背景が明るいため、border でダークなサイト背景との境界を明確にする */}
        <img
          src={avatar}
          alt={name}
          className="w-56 h-56 md:w-64 md:h-64 rounded-full object-cover border-4 border-border mx-auto md:mx-0"
        />

        <div className="space-y-6 text-center md:text-left">
          <div>
            <h1 className="text-2xl font-bold mb-1">{name}</h1>
            <p className="text-text-muted text-sm">{title}</p>
          </div>

          <Field label="出身">{location}</Field>

          <Field label="Skills">
            <div className="space-y-3">
              <div>
                <p className="text-text-muted font-mono text-xs mb-1.5">Backend</p>
                <div className="flex flex-wrap justify-center md:justify-start gap-2">
                  {skills.backend.map((s) => (
                    <Tag key={s}>{s}</Tag>
                  ))}
                </div>
              </div>
              <div>
                <p className="text-text-muted font-mono text-xs mb-1.5">Frontend</p>
                <div className="flex flex-wrap justify-center md:justify-start gap-2">
                  {skills.frontend.map((s) => (
                    <Tag key={s}>{s}</Tag>
                  ))}
                </div>
              </div>
            </div>
          </Field>

          <Field label="GitHub">
            <a
              href={`https://github.com/${github}`}
              target="_blank"
              rel="noopener noreferrer"
              className="text-primary hover:underline"
            >
              GitHubで見る
            </a>
          </Field>
        </div>
      </div>

      {/*
       * dangerouslySetInnerHTML を許容する根拠：
       * bodyHtml は AboutController でサーバー側に生成済みの HTML 文字列。
       * 元データは content/about.md（Git管理・著者のみ編集可）であり、
       * ユーザー入力や外部入力は一切経由しない。
       * PHP側 MarkdownRenderer の allow_unsafe_links=false により
       * javascript: リンクは除去されている。
       * この前提（著者管理コンテンツ）が崩れる場合は使用禁止。
       */}
      {/* max-w-prose を付けない：Works/Showはグリッドの片側カラムに収まるため実質無効化されるが、
          Aboutは本文がフル幅の単独ブロックのため、付けると上のアバター＋基本情報グリッドより
          狭くなり右側だけ余白が大きく見える。 */}
      <div className="case-study" dangerouslySetInnerHTML={{ __html: bodyHtml }} />
    </div>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <h3 className="text-xs font-mono text-text-muted mb-1">{label}</h3>
      <div className="text-sm">{children}</div>
    </div>
  );
}
