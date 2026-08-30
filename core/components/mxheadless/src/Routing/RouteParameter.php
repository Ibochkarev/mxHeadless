<?php

declare(strict_types=1);

namespace MxHeadless\Routing;

final class RouteParameter
{
    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(
        private readonly string $name,
        private readonly string $in,
        private readonly bool $required = true,
        private readonly ?string $description = null,
        private readonly array $schema = ['type' => 'string'],
    ) {
        if (!in_array($this->in, ['path', 'query', 'header'], true)) {
            throw new \InvalidArgumentException('Parameter location must be path, query, or header');
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function in(): string
    {
        return $this->in;
    }

    public function required(): bool
    {
        return $this->required;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return $this->schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function toOpenApi(): array
    {
        $param = [
            'name' => $this->name,
            'in' => $this->in,
            'required' => $this->required,
            'schema' => $this->schema,
        ];

        if ($this->description !== null && $this->description !== '') {
            $param['description'] = $this->description;
        }

        return $param;
    }

    /**
     * @return array<string, mixed>
     */
    public function toCatalogEntry(): array
    {
        return [
            'name' => $this->name,
            'in' => $this->in,
            'required' => $this->required,
            'description' => $this->description,
            'schema' => $this->schema,
        ];
    }
}
