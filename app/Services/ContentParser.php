<?php

declare(strict_types=1);

namespace App\Services;

use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;

class ContentParser
{
    private readonly FrontMatterParser $parser;

    public function __construct()
    {
        $this->parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
    }

    /**
     * Markdownファイルを読み込み、frontmatterと本文を分離して返す。
     * HTML変換・スキーマ検証は行わない。
     *
     * @return array{frontmatter: array<string, mixed>, body: string}
     */
    public function parse(string $path): array
    {
        $markdown = file_get_contents($path);
        $result   = $this->parser->parse($markdown);

        $frontmatter = $result->getFrontMatter() ?? [];

        return [
            'frontmatter' => $this->normalizeDates($frontmatter),
            'body'        => trim($result->getContent()),
        ];
    }

    /**
     * YAMLパーサが \DateTimeInterface に変換した値を YYYY-MM-DD 文字列に戻す。
     * Symfony YAMLはデフォルトで日付を文字列で返すが、YAML 1.1 の暗黙変換に対する防御策。
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeDates(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d');
            } elseif (is_array($value)) {
                $data[$key] = $this->normalizeDates($value);
            }
        }

        return $data;
    }
}
