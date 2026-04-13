<?php
declare(strict_types=1);

namespace Tests\Builders;

/**
 * ResourceBuilder — fluent factory for library resource (book) test arrays.
 *
 * Usage:
 *   $book = ResourceBuilder::make()->withTitle('Don Quijote')->available()->build();
 *   $books = ResourceBuilder::make()->build(5);
 */
final class ResourceBuilder
{
    private static int $sequence = 0;

    private array $overrides = [];

    private function __construct() {}

    public static function make(): self
    {
        return new self();
    }

    public function withId(int $id): self
    {
        $clone = clone $this;
        $clone->overrides['id'] = $id;
        return $clone;
    }

    public function withTitle(string $title): self
    {
        $clone = clone $this;
        $clone->overrides['title'] = $title;
        return $clone;
    }

    public function withAuthor(string $author): self
    {
        $clone = clone $this;
        $clone->overrides['author'] = $author;
        return $clone;
    }

    public function withIsbn(string $isbn): self
    {
        $clone = clone $this;
        $clone->overrides['isbn'] = $isbn;
        return $clone;
    }

    public function withCategory(int $categoryId): self
    {
        $clone = clone $this;
        $clone->overrides['category_id'] = $categoryId;
        return $clone;
    }

    public function withCopies(int $total, int $available): self
    {
        $clone = clone $this;
        $clone->overrides['total_copies']     = $total;
        $clone->overrides['available_copies'] = $available;
        return $clone;
    }

    public function available(): self
    {
        return $this->withCopies(3, 3);
    }

    public function checkedOut(): self
    {
        return $this->withCopies(1, 0);
    }

    public function featured(): self
    {
        $clone = clone $this;
        $clone->overrides['featured'] = true;
        return $clone;
    }

    public function deleted(): self
    {
        $clone = clone $this;
        $clone->overrides['deleted_at'] = date('Y-m-d H:i:s');
        return $clone;
    }

    /** Build one resource array. */
    public function build(): array
    {
        $seq = ++self::$sequence;

        return array_merge([
            'id'               => $seq,
            'title'            => "Libro de Prueba {$seq}",
            'author'           => "Autor Prueba {$seq}",
            'isbn'             => '978-0-' . str_pad((string) $seq, 9, '0', STR_PAD_LEFT),
            'publisher'        => 'Editorial Prueba',
            'year'             => 2020,
            'edition'          => '1a ed.',
            'description'      => 'Descripción del libro de prueba.',
            'category_id'      => 1,
            'language'         => 'es',
            'pages'            => 200,
            'location'         => 'A-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'total_copies'     => 2,
            'available_copies' => 2,
            'cover_image'      => null,
            'featured'         => false,
            'deleted_at'       => null,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ], $this->overrides);
    }

    /**
     * Build multiple resource arrays.
     *
     * @return array<int, array>
     */
    public function buildMany(int $count): array
    {
        return array_map(fn() => $this->build(), range(1, $count));
    }
}
