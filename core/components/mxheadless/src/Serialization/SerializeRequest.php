<?php

declare(strict_types=1);

namespace MxHeadless\Serialization;

use MxHeadless\Authentication\Identity;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Query\IncludeTree;
use Psr\Http\Message\ServerRequestInterface;

final class SerializeRequest
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        private readonly ObjectDefinition $definition,
        private readonly array $fields,
        private readonly string $context,
        private readonly IncludeTree $includes,
        private readonly ServerRequestInterface $request,
        private readonly Authorizer $authorizer,
        private readonly bool $includeTvs = false,
    ) {
    }

    public function definition(): ObjectDefinition
    {
        return $this->definition;
    }

    /**
     * @return list<string>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function context(): string
    {
        return $this->context;
    }

    public function includes(): IncludeTree
    {
        return $this->includes;
    }

    public function request(): ServerRequestInterface
    {
        return $this->request;
    }

    public function authorizer(): Authorizer
    {
        return $this->authorizer;
    }

    public function includeTvs(): bool
    {
        return $this->includeTvs;
    }

    public function canReadField(string $field, bool $protected = false): bool
    {
        return $this->authorizer->canReadField(
            $this->request,
            $this->definition,
            $field,
            $protected || in_array($field, $this->definition->getProtectedFields(), true),
        );
    }

    public function identity(): ?Identity
    {
        /** @var Identity|null $identity */
        $identity = $this->request->getAttribute('identity');

        return $identity;
    }
}
