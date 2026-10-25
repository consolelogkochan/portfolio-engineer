<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ContentNotFoundException;
use App\Exceptions\ContentParseException;
use League\CommonMark\Extension\FrontMatter\Data\FrontMatterDataParserInterface;
use League\CommonMark\Extension\FrontMatter\Exception\InvalidFrontMatterException;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ContentParser
{
    /** 最低限存在しなければならないフィールド。値の正当性検証はZodに任せる */
    private const REQUIRED_FIELDS = ['title'];

    private readonly FrontMatterParser $parser;

    public function __construct()
    {
        $this->parser = new FrontMatterParser(new class implements FrontMatterDataParserInterface
        {
            /**
             * league/commonmark の SymfonyYamlFrontMatterParser と同じ処理に、PARSE_DATETIME を足したもの
             * （あちらは final で、フラグを渡せない）。Symfony YAML は既定では、引用符の無い日付
             * （publishedAt: 2026-01-02）を Unix 時刻の整数にする。PARSE_DATETIME を付けると
             * DateTimeImmutable になり、normalizeDates が YYYY-MM-DD の文字列に戻す。
             */
            public function parse(string $frontMatter): mixed
            {
                try {
                    return Yaml::parse($frontMatter, Yaml::PARSE_DATETIME);
                } catch (ParseException $e) {
                    throw InvalidFrontMatterException::wrap($e);
                }
            }
        });
    }

    /**
     * Markdownファイルを読み込み、frontmatterと本文を分離して返す。
     * HTML変換・スキーマ検証は行わない。
     *
     * @return array{frontmatter: array<string, mixed>, body: string}
     *
     * @throws ContentNotFoundException ファイルが存在しない・読めない場合
     * @throws ContentParseException frontmatterのパース失敗・連想配列でない・必須フィールド欠落の場合
     */
    public function parse(string $path): array
    {
        // ガードa: ファイル存在・読み取り可能チェック
        if (! file_exists($path)) {
            throw new ContentNotFoundException("File not found: {$path}");
        }
        if (! is_readable($path)) {
            throw new ContentNotFoundException("File not readable: {$path}");
        }

        // ガードb: frontmatterのパース
        try {
            $markdown = file_get_contents($path);
            $result = $this->parser->parse($markdown);
        } catch (InvalidFrontMatterException $e) {
            throw new ContentParseException(
                "Failed to parse frontmatter in: {$path}",
                previous: $e,
            );
        }

        // ガードc: frontmatterの形。YAML として読めても、連想配列（キーと値の組）でなければ
        // 「壊れた1件」とする（文字列だけ・箇条だけの frontmatter など）。空は下の必須の確認に任せる
        $frontmatter = $result->getFrontMatter() ?? [];
        if (! is_array($frontmatter) || ($frontmatter !== [] && array_is_list($frontmatter))) {
            throw new ContentParseException("Frontmatter is not a mapping in: {$path}");
        }

        $frontmatter = $this->normalizeDates($frontmatter);

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
            'body' => trim($result->getContent()),
        ];
    }

    /**
     * YAMLパーサが \DateTimeInterface に変換した値（引用符の無い日付。PARSE_DATETIME による）を
     * YYYY-MM-DD 文字列に戻す。
     *
     * @param  array<string, mixed>  $data
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
