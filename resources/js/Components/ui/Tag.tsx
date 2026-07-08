import React from 'react';

// 表示専用のため <span>（操作しない・インラインで並ぶ）
// 色は semanticトークンのみ参照（base層の --color-green-* 等は直接使わない）
type TagProps = { children: React.ReactNode; className?: string };

const base =
  'inline-flex items-center rounded-sm border-2 border-primary/50 bg-primary/12 text-text text-xs px-2 py-0.5 font-mono';

export default function Tag({ children, className }: TagProps) {
  return <span className={[base, className].filter(Boolean).join(' ')}>{children}</span>;
}
