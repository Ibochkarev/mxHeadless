<?php

declare(strict_types=1);

namespace MxHeadless\Query;

final class IncludeTree
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        private readonly array $paths = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    public function isEmpty(): bool
    {
        return $this->paths === [];
    }

    /**
     * @return list<string>
     */
    public function rootRelations(): array
    {
        $roots = [];
        foreach ($this->paths as $path) {
            $root = explode('.', $path)[0];
            if ($root !== '' && !in_array($root, $roots, true)) {
                $roots[] = $root;
            }
        }

        return $roots;
    }

    /**
     * @return list<string>
     */
    public function nestedPaths(string $relation): array
    {
        $prefix = $relation . '.';
        $nested = [];
        foreach ($this->paths as $path) {
            if (str_starts_with($path, $prefix)) {
                $nested[] = substr($path, strlen($prefix));
            }
        }

        return $nested;
    }
}
