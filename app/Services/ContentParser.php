<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ContentNotFoundException;
use App\Exceptions\ContentParseException;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\Exception\InvalidFrontMatterException;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;

class ContentParser
{
    /** 最低限存在しなければならないフィールド。値の正当性検証はZodに任せる */
    private const REQUIRED_FIELDS = ['title'];

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
     * @throws ContentNotFoundException ファイルが存在しない・読めない場合
     * @throws ContentParseException    frontmatterのパース失敗・必須フィールド欠落の場合
     */
    public function parse(string $path): array
    {
        // ガードa: ファイル存在・読み取り可能チェック
        if (!file_exists($path)) {
            throw new ContentNotFoundException("File not found: {$path}");
        }
        if (!is_readable($path)) {
            throw new ContentNotFoundException("File not readable: {$path}");
        }

        // ガードb: frontmatterのパース
        try {
            $markdown = file_get_contents($path);
            $result   = $this->parser->parse($markdown);
        } catch (InvalidFrontMatterException $e) {
            throw new ContentParseException(
                "Failed to parse frontmatter in: {$path}",
                previous: $e,
            );
        }

        $frontmatter = $this->normalizeDates($result->getFrontMatter() ?? []);

        // 必須フィールドの最低限存在チェック（値の正当性はZodに任せる）
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($frontmatter[$field])) {
                throw new ContentParseException(
                    "Missing required field '{$field}' in: {$path}",
                );
            }
        }

        return [
            'frontmatter' => $frontmatter,
            'body'        => trim($result->getContent()),
        ];
    }

    /**
     * YAMLパーサが \DateTimeInterface に変換した値を YYYY-MM-DD 文字列に戻す。
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
