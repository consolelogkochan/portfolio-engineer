import Gallery from '@/Components/Gallery';
import Card from '@/Components/ui/Card';
import Tag from '@/Components/ui/Tag';
import { Work } from '@/types/work';
import { Head } from '@inertiajs/react';
import React from 'react';

type Props = Work & { bodyHtml: string };

export default function Show(props: Props) {
  const {
    title,
    category,
    status,
    summary,
    period,
    role,
    technologies,
    aiTools = [],
    liveUrl,
    repoUrl,
    // frontmatterにgalleryキーが無いとprops上undefinedになる（Zodのdefault([])はPHP側のパースには効かない）
    gallery = [],
    bodyHtml,
  } = props;

  return (
    <div>
      <Head title={title} />

      <h1 className="text-2xl font-bold mb-1">{title}</h1>
      <p className="text-text-muted text-sm mb-8">
        {category} ・ {status}
      </p>

      {gallery.length > 0 && (
        <div className="mb-8">
          <h2 className="text-sm font-mono text-text-muted mb-2">Gallery</h2>
          <Gallery gallery={gallery} />
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <Card className="space-y-4 md:sticky md:top-8 md:self-start">
          <Field label="概要">{summary}</Field>
          <Field label="分類・状態">
            {category} ・ {status}
          </Field>
          <Field label="期間">
            {period.start} 〜 {period.end ?? '継続中'}
          </Field>
          <Field label="担当">{role.join(', ')}</Field>
          <Field label="使用技術">
            <div className="flex flex-wrap gap-2">
              {technologies.map((t) => (
                <Tag key={t}>{t}</Tag>
              ))}
            </div>
          </Field>
          {aiTools.length > 0 && (
            <Field label="AIツール">
              <div className="flex flex-wrap gap-2">
                {aiTools.map((t) => (
                  <Tag key={t}>{t}</Tag>
                ))}
              </div>
            </Field>
          )}
          {liveUrl && (
            <Field label="公開URL">
              <a
                href={liveUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-primary hover:underline"
              >
                サイトを見る
              </a>
            </Field>
          )}
          {repoUrl && (
            <Field label="リポジトリ">
              <a
                href={repoUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-primary hover:underline"
              >
                GitHubで見る
              </a>
            </Field>
          )}
        </Card>

        <div>
          <h2 className="text-sm font-mono text-text-muted mb-2">ケーススタディ</h2>
          {/*
           * dangerouslySetInnerHTML を許容する根拠：
           * bodyHtml は WorkController でサーバー側に生成済みの HTML 文字列。
           * 元データは content/works/*.md（Git管理・著者のみ編集可）であり、
           * ユーザー入力や外部入力は一切経由しない。
           * PHP側 MarkdownRenderer の allow_unsafe_links=false により
           * javascript: リンクは除去されている。
           * この前提（著者管理コンテンツ）が崩れる場合は使用禁止。
           */}
          <div className="case-study max-w-prose" dangerouslySetInnerHTML={{ __html: bodyHtml }} />
        </div>
      </div>
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
