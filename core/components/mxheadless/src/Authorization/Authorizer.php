<?php

declare(strict_types=1);

namespace MxHeadless\Authorization;

use MODX\Revolution\modX;
use MxHeadless\Authentication\BearerTokenExtractor;
use MxHeadless\Authentication\Identity;
use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Exception\ForbiddenException;
use MxHeadless\Exception\NotFoundException;
use MxHeadless\Exception\UnauthorizedException;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;

final class Authorizer
{
    public function __construct(
        private readonly modX $modx,
        private readonly ?ObjectRegistry $registry = null,
    ) {
    }

    public function authorizeEndpoint(ServerRequestInterface $request, Route $route): void
    {
        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');

        $params = $request->getAttribute('route_params');
        if (!is_array($params)) {
            $params = [];
        }

        if (
            $this->registry !== null
            && str_starts_with($route->name(), 'objects.')
            && isset($params['name'])
            && $this->registry->get((string) $params['name']) === null
        ) {
            throw new NotFoundException('Object not found: ' . $params['name']);
        }

        $permission = $route->resolvePermission($params);
        $method = strtoupper($request->getMethod());
        $isSafeRead = $method === 'GET' || $method === 'HEAD';

        if ($route->isPublic() && $permission === null) {
            return;
        }

        if ($route->isPublic() && $isSafeRead) {
            if ($identity === null || $identity->isAnonymous()) {
                return;
            }

            if ($permission !== null && !$identity->allows($permission)) {
                throw new ForbiddenException('Insufficient permissions');
            }

            return;
        }

        if ($identity === null || $identity->isAnonymous()) {
            throw BearerTokenExtractor::hasCredentialHeader($request)
                ? UnauthorizedException::invalid()
                : UnauthorizedException::missing();
        }

        if ($permission !== null && !$identity->allows($permission)) {
            throw new ForbiddenException('Insufficient permissions');
        }
    }

    public function enforceContext(ServerRequestInterface $request, ObjectDefinition $definition, string $context): string
    {
        if ($definition->isContextAccessGated()) {
            return $context;
        }

        $allowed = $definition->getContexts();
        if ($allowed !== [] && !in_array($context, $allowed, true)) {
            throw new ForbiddenException('Context not allowed for this object');
        }

        if (!$this->canAccessContext($request, $context)) {
            throw new ForbiddenException('Cross-context access denied');
        }

        return $context;
    }

    public function assertPreviewAllowed(ServerRequestInterface $request, bool $preview): void
    {
        if (!$preview) {
            return;
        }

        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');
        if ($identity === null || $identity->isAnonymous()) {
            throw new ForbiddenException('Preview requires authentication');
        }

        if (!$identity->allows('preview') && !$this->modx->hasPermission('view_unpublished')) {
            throw new ForbiddenException('Preview permission required');
        }
    }

    public function assertIncludeDeletedAllowed(ServerRequestInterface $request, bool $includeDeleted): void
    {
        if (!$includeDeleted) {
            return;
        }

        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');
        if ($identity === null || $identity->isAnonymous()) {
            throw new ForbiddenException('include_deleted requires authentication');
        }

        if (
            !$identity->allows('preview')
            && !$identity->allows('resources.update')
            && !$identity->allows('resources.delete')
            && !$this->modx->hasPermission('view_unpublished')
            && !$this->modx->hasPermission('empty_recycle_bin')
        ) {
            throw new ForbiddenException('Permission required to include deleted objects');
        }
    }

    public function canReadField(ServerRequestInterface $request, ObjectDefinition $definition, string $field, bool $protected = false): bool
    {
        if (in_array($field, $definition->getHiddenFields(), true)) {
            return false;
        }

        if (!$protected && !in_array($field, $definition->getProtectedFields(), true)) {
            return in_array($field, $definition->getFields(), true);
        }

        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');
        if ($identity === null || $identity->isAnonymous()) {
            return false;
        }

        if ($identity->type() === Identity::TYPE_SESSION) {
            return $this->modx->hasPermission('mxheadless_fields_' . $field)
                || $this->modx->hasPermission('mxheadless_fields_all');
        }

        return $identity->allows('fields.' . $field);
    }

    public function canWriteField(ServerRequestInterface $request, ObjectDefinition $definition, string $field, bool $protected = false): bool
    {
        if (in_array($field, $definition->getImmutableFields(), true)) {
            return false;
        }

        if (!$protected && !in_array($field, $definition->getProtectedFields(), true)) {
            return in_array($field, $definition->getFields(), true);
        }

        return $this->canReadField($request, $definition, $field, true);
    }

    /**
     * @return list<string>
     */
    public function resolveReadableFields(ServerRequestInterface $request, ObjectDefinition $definition, ?array $requested = null): array
    {
        $base = $requested ?? $definition->getFields();
        $readable = [];
        foreach ($base as $field) {
            $isProtected = in_array($field, $definition->getProtectedFields(), true);
            if ($this->canReadField($request, $definition, $field, $isProtected)) {
                $readable[] = $field;
            }
        }

        return $readable;
    }

    public function assertReadable(ObjectDefinition $definition): void
    {
        if (!$definition->isReadable()) {
            throw new ForbiddenException('Object is not readable');
        }
    }

    public function assertCreatable(ObjectDefinition $definition): void
    {
        if (!$definition->isCreatable()) {
            throw new ForbiddenException('Object is not creatable');
        }
    }

    public function assertUpdatable(ObjectDefinition $definition): void
    {
        if (!$definition->isUpdatable()) {
            throw new ForbiddenException('Object is not updatable');
        }
    }

    public function assertDeletable(ObjectDefinition $definition): void
    {
        if (!$definition->isDeletable()) {
            throw new ForbiddenException('Object is not deletable');
        }
    }

    /**
     * @return list<string>
     */
    public function accessibleContextKeys(ServerRequestInterface $request): array
    {
        $allowed = array_map(
            'trim',
            explode(',', (string) $this->modx->getOption('mxheadless.allowed_contexts', null, 'web,mgr')),
        );
        $keys = [];
        foreach ($allowed as $key) {
            if ($key === '') {
                continue;
            }
            if ($this->canAccessContext($request, $key)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function assertContextKeyAccess(ServerRequestInterface $request, string $contextKey): void
    {
        if (!$this->canAccessContext($request, $contextKey)) {
            throw new ForbiddenException('Context access denied');
        }
    }

    private function canAccessContext(ServerRequestInterface $request, string $context): bool
    {
        $current = $this->modx->context ? (string) $this->modx->context->get('key') : 'web';
        if ($context === $current) {
            return true;
        }

        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');
        if ($identity !== null && $identity->type() === Identity::TYPE_SESSION) {
            return $this->modx->hasPermission('context_' . $context);
        }

        if ($identity !== null && $identity->allows('context.' . $context)) {
            return true;
        }

        return false;
    }
}
