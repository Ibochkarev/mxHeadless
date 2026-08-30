<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MODX\Revolution\modContext;
use MODX\Revolution\modResource;
use MODX\Revolution\modX;
use MxHeadless\Authentication\Identity;
use MxHeadless\Authorization\Authorizer;
use MxHeadless\Exception\NotFoundException;
use MxHeadless\Exception\ValidationException;
use MxHeadless\Http\PageUriResolver;
use MxHeadless\Query\ObjectQuery;
use MxHeadless\Query\QueryParser;
use MxHeadless\Query\VisibilityPolicy;
use MxHeadless\Query\XpdoQueryCompiler;
use MxHeadless\Registry\ObjectRegistry;
use MxHeadless\Serialization\RelationLoader;
use MxHeadless\Serialization\SerializeRequest;
use MxHeadless\Serialization\XpdoObjectSerializer;
use MxHeadless\Tv\TvProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use xPDOObject;

final class ObjectService
{
    /** @var list<string> */
    private const BOOLEAN_FIELDS = [
        'published',
        'deleted',
        'hidemenu',
        'isfolder',
        'richtext',
        'searchable',
        'cacheable',
        'uri_override',
        'hide_children_in_tree',
        'show_in_tree',
    ];

    /** @var list<string> */
    private const INTEGER_FIELDS = [
        'parent',
        'template',
        'menuindex',
        'createdby',
        'editedby',
        'deletedby',
        'publishedby',
        'pub_date',
        'unpub_date',
        'createdon',
        'editedon',
        'deletedon',
        'publishedon',
        'content_dispo',
        'content_type',
    ];

    public function __construct(
        private readonly modX $modx,
        private readonly ObjectRegistry $registry,
        private readonly Authorizer $authorizer,
        private readonly XpdoObjectSerializer $serializer,
        private readonly QueryParser $queryParser,
        private readonly XpdoQueryCompiler $queryCompiler,
        private readonly RelationLoader $relationLoader,
        private readonly VisibilityPolicy $visibility,
        private readonly MutationHooks $mutations,
        private readonly TvProviderInterface $tvProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function list(ServerRequestInterface $request, string $name): array
    {
        $definition = $this->requireDefinition($name);
        $query = $this->queryParser->parse($name, $request->getQueryParams(), $request->getHeaderLine('X-Context'));
        $this->assertReadAccess($request, $definition, $query);
        $this->assertRequestedFields($definition, $query);
        $this->assertRequestedIncludes($definition, $query);

        $fields = $this->authorizer->resolveReadableFields(
            $request,
            $definition,
            $query->fields()->isEmpty() ? null : $query->fields()->fields(),
        );

        $total = (int) $this->modx->getCount(
            $definition->objectClass(),
            $this->queryCompiler->compileCount($definition, $query, $request),
        );
        $xpdoQuery = $this->queryCompiler->compile($definition, $query, $fields, $request);
        $collection = $this->modx->getCollection($definition->objectClass(), $xpdoQuery);

        $items = [];
        foreach ($collection as $object) {
            $items[] = $this->serializeObject($request, $definition, $object, $fields, $query);
        }

        $limit = $query->pagination()->limit();
        $offset = $query->pagination()->offset();
        $count = count($items);
        $hasMore = ($offset + $count) < $total;

        return [
            'data' => $items,
            'meta' => [
                'total' => $total,
                'count' => $count,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $hasMore,
            ],
            'links' => $this->paginationLinks($request, $limit, $offset, $hasMore),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get(ServerRequestInterface $request, string $name, string $id): array
    {
        $definition = $this->requireDefinition($name);
        $query = $this->queryParser->parse($name, $request->getQueryParams(), $request->getHeaderLine('X-Context'));
        $this->assertReadAccess($request, $definition, $query);
        $this->assertRequestedFields($definition, $query);
        $this->assertRequestedIncludes($definition, $query);

        $object = $this->findObject(
            $request,
            $definition,
            $query,
            $definition->getPrimaryKey(),
            $id,
            false,
            $query->includeDeleted(),
        );
        $fields = $this->authorizer->resolveReadableFields(
            $request,
            $definition,
            $query->fields()->isEmpty() ? null : $query->fields()->fields(),
        );

        return [
            'data' => $this->serializeObject($request, $definition, $object, $fields, $query),
            'meta' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getByField(ServerRequestInterface $request, string $name, string $field, string $value): array
    {
        $definition = $this->requireDefinition($name);
        $query = $this->queryParser->parse($name, $request->getQueryParams(), $request->getHeaderLine('X-Context'));
        $this->assertReadAccess($request, $definition, $query);
        $this->assertRequestedFields($definition, $query);
        $this->assertRequestedIncludes($definition, $query);

        if ($field === 'uri') {
            $requested = trim($value, '/');
            $object = $this->findObjectByUriCandidates($request, $definition, $query, $requested);
            $fields = $this->authorizer->resolveReadableFields(
                $request,
                $definition,
                $query->fields()->isEmpty() ? null : $query->fields()->fields(),
            );
            $data = $this->serializeObject($request, $definition, $object, $fields, $query);

            return [
                'data' => $data,
                'meta' => [
                    'uri' => $requested,
                    'resolved_uri' => (string) ($object->get('uri') ?? $requested),
                    'context' => $query->context(),
                ],
            ];
        }

        $object = $this->findObject(
            $request,
            $definition,
            $query,
            $field,
            $value,
            false,
            $query->includeDeleted(),
        );
        $fields = $this->authorizer->resolveReadableFields(
            $request,
            $definition,
            $query->fields()->isEmpty() ? null : $query->fields()->fields(),
        );
        $data = $this->serializeObject($request, $definition, $object, $fields, $query);

        return [
            'data' => $data,
            'meta' => ['uri' => $value, 'context' => $query->context()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function create(ServerRequestInterface $request, string $name): array
    {
        $definition = $this->requireDefinition($name);
        $this->authorizer->assertCreatable($definition);
        $context = $this->authorizer->enforceContext(
            $request,
            $definition,
            $this->queryParser->parse($name, $request->getQueryParams(), $request->getHeaderLine('X-Context'))->context(),
        );

        $payload = $this->parseBody($request);
        $this->assertRequiredFields($definition, $payload, true);
        $createAsDeleted = $this->extractDeletedIntent($definition, $payload, false);
        $object = $this->modx->newObject($definition->objectClass());
        if (!$object instanceof xPDOObject) {
            throw new ValidationException('Unable to create object');
        }

        $this->applyWritableFields($request, $object, $definition, $payload, true);
        if ($object->get('context_key') === null && in_array('context_key', $definition->getFields(), true)) {
            $object->set('context_key', $context);
        }

        if ($createAsDeleted === true) {
            $this->authorizer->assertDeletable($definition);
        }
        $this->persistObject($object, 'Failed to create object', $definition);
        if ($createAsDeleted === true) {
            $this->softDeleteObject($request, $definition, $object);
        }

        $this->mutations->afterMutation($name, 'created', $object);
        $fields = $this->authorizer->resolveReadableFields($request, $definition);
        $query = $this->queryParser->parse($name, [], $context);

        return [
            'data' => $this->serializeObject($request, $definition, $object, $fields, $query),
            'meta' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function update(ServerRequestInterface $request, string $name, string $id): array
    {
        $definition = $this->requireDefinition($name);
        $this->authorizer->assertUpdatable($definition);
        $query = $this->queryParser->parse($name, $request->getQueryParams(), $request->getHeaderLine('X-Context'));
        $this->authorizer->enforceContext($request, $definition, $query->context());

        $supportsSoftDelete = in_array('deleted', $definition->getFields(), true);
        $object = $this->findObject(
            $request,
            $definition,
            $query,
            $definition->getPrimaryKey(),
            $id,
            true,
            $supportsSoftDelete,
        );
        $payload = $this->parseBody($request);
        $isReplace = strtoupper($request->getMethod()) === 'PUT';
        $this->assertRequiredFields($definition, $payload, $isReplace);
        $wasDeleted = $supportsSoftDelete && (bool) $object->get('deleted');
        $deletedIntent = $this->extractDeletedIntent($definition, $payload, $wasDeleted);
        $this->applyWritableFields($request, $object, $definition, $payload, false);

        if ($deletedIntent === true) {
            $this->authorizer->assertDeletable($definition);
            $this->persistObject($object, 'Failed to update object', $definition);
            $this->softDeleteObject($request, $definition, $object);
        } else {
            if ($deletedIntent === false) {
                $this->restoreSoftDeleted($object, $definition);
            }
            $this->persistObject($object, 'Failed to update object', $definition);
        }

        $this->mutations->afterMutation($name, 'updated', $object, $id);
        $fields = $this->authorizer->resolveReadableFields($request, $definition);

        return [
            'data' => $this->serializeObject($request, $definition, $object, $fields, $query),
            'meta' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(ServerRequestInterface $request, string $name, string $id): array
    {
        $definition = $this->requireDefinition($name);
        $this->authorizer->assertDeletable($definition);
        $query = $this->queryParser->parse($name, $request->getQueryParams(), $request->getHeaderLine('X-Context'));
        $this->authorizer->enforceContext($request, $definition, $query->context());

        $force = filter_var($request->getQueryParams()['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $supportsSoftDelete = in_array('deleted', $definition->getFields(), true);
        $object = $this->findObject(
            $request,
            $definition,
            $query,
            $definition->getPrimaryKey(),
            $id,
            true,
            $supportsSoftDelete,
        );
        $permanent = $force || !$supportsSoftDelete;

        if ($permanent) {
            if (!$object->remove()) {
                throw new ValidationException('Failed to delete object');
            }
        } else {
            $this->softDeleteObject($request, $definition, $object);
        }

        $this->mutations->afterMutation($name, 'deleted', $object, $id);

        return [
            'data' => [
                'id' => $id,
                'deleted' => true,
                'permanent' => $permanent,
            ],
            'meta' => [],
        ];
    }

    private function assertReadAccess(
        ServerRequestInterface $request,
        \MxHeadless\Definition\ObjectDefinition $definition,
        ObjectQuery $query,
    ): void {
        $this->authorizer->assertReadable($definition);
        $this->authorizer->assertPreviewAllowed($request, $query->preview());
        $this->authorizer->assertIncludeDeletedAllowed($request, $query->includeDeleted());
        $this->authorizer->enforceContext($request, $definition, $query->context());
    }

    private function assertRequestedFields(
        \MxHeadless\Definition\ObjectDefinition $definition,
        ObjectQuery $query,
    ): void {
        if ($query->fields()->isEmpty()) {
            return;
        }

        $allowed = $definition->getFields();
        $hidden = $definition->getHiddenFields();
        foreach ($query->fields()->fields() as $field) {
            if (!in_array($field, $allowed, true) || in_array($field, $hidden, true)) {
                throw new ValidationException('Field not allowed', [$field => ['Field is not readable']]);
            }
        }
    }

    private function assertRequestedIncludes(
        \MxHeadless\Definition\ObjectDefinition $definition,
        ObjectQuery $query,
    ): void {
        if ($query->includes()->isEmpty()) {
            return;
        }

        foreach ($query->includes()->paths() as $path) {
            $segments = explode('.', $path);
            $current = $definition;
            foreach ($segments as $index => $name) {
                if ($name === 'tv' || $name === 'tvs') {
                    if ($index !== count($segments) - 1) {
                        throw new ValidationException('Include not allowed', [$path => ['Unknown relation']]);
                    }
                    break;
                }

                $relation = $current->getRelation($name);
                if ($relation === null || !$relation->isReadable()) {
                    throw new ValidationException('Include not allowed', [$path => ['Unknown relation']]);
                }

                if ($index === count($segments) - 1) {
                    break;
                }

                $next = $this->registry->get($relation->targetObject());
                if ($next === null) {
                    throw new ValidationException('Include not allowed', [$path => ['Unknown relation']]);
                }
                $current = $next;
            }
        }
    }

    private function requireDefinition(string $name): \MxHeadless\Definition\ObjectDefinition
    {
        $definition = $this->registry->get($name);
        if ($definition === null) {
            throw new NotFoundException('Object not found: ' . $name);
        }

        return $definition;
    }

    private function findObject(
        ServerRequestInterface $request,
        \MxHeadless\Definition\ObjectDefinition $definition,
        ObjectQuery $query,
        string $lookupField,
        string $value,
        bool $forMutation = false,
        bool $includeDeleted = false,
    ): xPDOObject {
        $criteria = $this->visibility->applyCriteria(
            $definition,
            $query,
            [$lookupField => $value],
            $request,
            $forMutation,
            $includeDeleted,
        );

        /** @var xPDOObject|null $object */
        $object = $this->modx->getObject($definition->objectClass(), $criteria);
        if ($object === null) {
            throw new NotFoundException('Object record not found');
        }

        if ($definition->isContextAccessGated()) {
            $this->authorizer->assertContextKeyAccess(
                $request,
                (string) $object->get($definition->getPrimaryKey()),
            );
        } elseif (
            $forMutation
            && in_array('context_key', $definition->getFields(), true)
        ) {
            $objectContext = (string) ($object->get('context_key') ?? '');
            if ($objectContext !== '') {
                $this->authorizer->assertContextKeyAccess($request, $objectContext);
            }
        }

        return $object;
    }

    private function findObjectByUriCandidates(
        ServerRequestInterface $request,
        \MxHeadless\Definition\ObjectDefinition $definition,
        ObjectQuery $query,
        string $requested,
    ): xPDOObject {
        $last = null;
        foreach (PageUriResolver::candidates($requested) as $candidate) {
            try {
                return $this->findObject(
                    $request,
                    $definition,
                    $query,
                    'uri',
                    $candidate,
                    false,
                    $query->includeDeleted(),
                );
            } catch (NotFoundException $e) {
                $last = $e;
            }
        }

        throw $last ?? new NotFoundException('Object record not found');
    }

    /**
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    private function serializeObject(
        ServerRequestInterface $request,
        \MxHeadless\Definition\ObjectDefinition $definition,
        xPDOObject $object,
        array $fields,
        ObjectQuery $query,
    ): array {
        $includeTvs = $this->shouldIncludeTvs($request, $query);
        $serializeRequest = new SerializeRequest(
            $definition,
            $fields,
            $query->context(),
            $query->includes(),
            $request,
            $this->authorizer,
            $includeTvs,
        );

        $data = $this->serializer->serialize($object, $serializeRequest);
        $relations = $this->relationLoader->load($object, $definition, $query->includes(), $serializeRequest, $query);
        if ($relations !== []) {
            $data = array_merge($data, $relations);
        }

        if ($includeTvs && $object instanceof modResource) {
            $data['tvs'] = $this->tvProvider->getForResource($object, $request->getQueryParams());
        }

        return $data;
    }

    private function shouldIncludeTvs(ServerRequestInterface $request, ObjectQuery $query): bool
    {
        $params = $request->getQueryParams();
        if (isset($params['tv_fields']) || isset($params['include_tv'])) {
            return true;
        }

        foreach ($query->includes()->paths() as $path) {
            if ($path === 'tv' || $path === 'tvs' || str_starts_with($path, 'tv.') || str_starts_with($path, 'tvs.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{self: string, next?: string, prev?: string}
     */
    private function paginationLinks(ServerRequestInterface $request, int $limit, int $offset, bool $hasMore): array
    {
        $path = $request->getUri()->getPath();
        $query = $request->getQueryParams();
        unset($query['page']);

        $build = static function (int $nextOffset) use ($path, $query, $limit): string {
            $params = $query;
            $params['limit'] = $limit;
            $params['offset'] = $nextOffset;

            return $path . '?' . http_build_query($params);
        };

        $links = ['self' => $build($offset)];
        if ($hasMore) {
            $links['next'] = $build($offset + $limit);
        }
        if ($offset > 0) {
            $links['prev'] = $build(max(0, $offset - $limit));
        }

        return $links;
    }

    /**
     * Pull `deleted` from the write payload and return the desired soft-delete state.
     * Null means no change (field absent or already in that state).
     *
     * @param array<string, mixed> $payload
     */
    private function extractDeletedIntent(
        \MxHeadless\Definition\ObjectDefinition $definition,
        array &$payload,
        bool $currentlyDeleted,
    ): ?bool {
        if (!array_key_exists('deleted', $payload) || !in_array('deleted', $definition->getFields(), true)) {
            return null;
        }

        $wantDeleted = (bool) $this->normalizeWritableValue('deleted', $payload['deleted']);
        unset($payload['deleted']);

        if ($wantDeleted === $currentlyDeleted) {
            return null;
        }

        return $wantDeleted;
    }

    private function restoreSoftDeleted(
        xPDOObject $object,
        \MxHeadless\Definition\ObjectDefinition $definition,
    ): void {
        $object->set('deleted', false);
        if (in_array('deletedon', $definition->getFields(), true)) {
            $object->set('deletedon', 0);
        }
        if (in_array('deletedby', $definition->getFields(), true)) {
            $object->set('deletedby', 0);
        }
    }

    private function softDeleteObject(
        ServerRequestInterface $request,
        \MxHeadless\Definition\ObjectDefinition $definition,
        xPDOObject $object,
    ): void {
        $deletedBy = $this->actorUserId($request);
        $deletedOn = time();

        if ($object instanceof modResource) {
            $this->softDeleteResourceTree($object, $deletedBy, $deletedOn);

            return;
        }

        $this->markSoftDeleted($object, $definition, $deletedBy, $deletedOn);
        if (!$object->save()) {
            throw new ValidationException('Failed to delete object');
        }
    }

    private function softDeleteResourceTree(modResource $resource, int $deletedBy, int $deletedOn): void
    {
        $children = [];
        $this->collectResourceChildren($resource, $children);
        foreach ($children as $child) {
            $child->set('deleted', true);
            $child->set('deletedby', $deletedBy);
            $child->set('deletedon', $deletedOn);
            if (!$child->save()) {
                throw new ValidationException('Failed to delete child resource');
            }
        }

        $resource->set('deleted', true);
        $resource->set('deletedby', $deletedBy);
        $resource->set('deletedon', $deletedOn);
        if (!$resource->save()) {
            throw new ValidationException('Failed to delete object');
        }
    }

    /**
     * @param list<modResource> $children
     */
    private function collectResourceChildren(modResource $parent, array &$children): void
    {
        /** @var list<modResource> $direct */
        $direct = $parent->getMany('Children');
        foreach ($direct as $child) {
            if (!$child instanceof modResource) {
                continue;
            }
            $children[] = $child;
            $this->collectResourceChildren($child, $children);
        }
    }

    private function markSoftDeleted(
        xPDOObject $object,
        \MxHeadless\Definition\ObjectDefinition $definition,
        int $deletedBy,
        int $deletedOn,
    ): void {
        $object->set('deleted', true);
        if (in_array('deletedon', $definition->getFields(), true)) {
            $object->set('deletedon', $deletedOn);
        }
        if (in_array('deletedby', $definition->getFields(), true)) {
            $object->set('deletedby', $deletedBy);
        }
    }

    private function actorUserId(ServerRequestInterface $request): int
    {
        /** @var Identity|null $identity */
        $identity = $request->getAttribute('identity');
        if ($identity instanceof Identity && $identity->userId() !== null) {
            return max(0, $identity->userId());
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseBody(ServerRequestInterface $request): array
    {
        $raw = (string) $request->getBody();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new ValidationException('Invalid JSON body');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertRequiredFields(
        \MxHeadless\Definition\ObjectDefinition $definition,
        array $payload,
        bool $creating,
    ): void {
        $errors = [];
        foreach ($definition->getRequiredFields() as $field) {
            if (!array_key_exists($field, $payload)) {
                if ($creating) {
                    $errors[$field] = ['This field is required.'];
                }
                continue;
            }
            $value = $payload[$field];
            if ($value === null) {
                $errors[$field] = ['This field is required.'];
                continue;
            }
            if (is_array($value) || is_bool($value)) {
                $errors[$field] = ['Must be a scalar string or number.'];
                continue;
            }
            if (is_string($value) && trim($value) === '') {
                $errors[$field] = ['This field must not be empty.'];
            }
        }

        if ($errors !== []) {
            $detail = 'Missing required fields';
            foreach ($errors as $messages) {
                foreach ($messages as $message) {
                    if (str_contains($message, 'scalar') || str_contains($message, 'empty')) {
                        $detail = 'Invalid field value';
                        break 2;
                    }
                }
            }
            throw new ValidationException($detail, $errors);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyWritableFields(
        ServerRequestInterface $request,
        xPDOObject $object,
        \MxHeadless\Definition\ObjectDefinition $definition,
        array $payload,
        bool $creating,
    ): void {
        foreach ($payload as $field => $value) {
            if (!is_string($field)) {
                continue;
            }
            if (in_array($field, $definition->getHiddenFields(), true)) {
                throw new ValidationException('Field not allowed', [$field => ['Field is not writable']]);
            }
            if (in_array($field, $definition->getImmutableFields(), true)) {
                throw new ValidationException('Field not writable', [$field => ['Field is immutable']]);
            }
            if (!in_array($field, $definition->getFields(), true)) {
                throw new ValidationException('Field not allowed', [$field => ['Unknown field']]);
            }
            $isProtected = in_array($field, $definition->getProtectedFields(), true);
            if (!$this->authorizer->canWriteField($request, $definition, $field, $isProtected)) {
                throw new ValidationException('Field not writable', [$field => ['Insufficient permissions']]);
            }
            $normalized = $this->normalizeWritableValue($field, $value);
            if ($field === 'context_key') {
                $normalized = $this->assertWritableContextKey($request, $normalized);
            }
            if ($field === 'class_key') {
                $normalized = $this->assertWritableClassKey($normalized);
            }
            if ($field === 'parent') {
                $normalized = $this->assertWritableParent($object, $definition, $normalized);
            }
            if ($field === 'content_type') {
                $normalized = $this->assertWritableContentType($normalized);
            }
            if ($field === 'template') {
                $normalized = $this->assertWritableTemplate($normalized);
            }
            if ($field === 'alias') {
                $normalized = $this->normalizeAliasValue($normalized);
            }
            $object->set($field, $normalized);
        }

        if ($creating && in_array('createdon', $definition->getFields(), true)) {
            $object->set('createdon', time());
        }
        if (in_array('editedon', $definition->getFields(), true)) {
            $object->set('editedon', time());
        }
    }

    private function persistObject(
        xPDOObject $object,
        string $failureMessage,
        ?\MxHeadless\Definition\ObjectDefinition $definition = null,
    ): void {
        if ($definition !== null) {
            $this->assertAliasUnique($object, $definition);
        }

        try {
            if (!$object->save()) {
                throw new ValidationException($failureMessage);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'getOption()') || str_contains($e->getMessage(), 'context')) {
                throw new ValidationException('Invalid context_key', [
                    'context_key' => ['Context cannot be applied to this resource'],
                ]);
            }

            throw $e;
        }
    }

    private function assertWritableContextKey(ServerRequestInterface $request, mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException('Invalid field type', [
                'context_key' => ['Must be a non-empty string'],
            ]);
        }

        $key = trim($value);
        $allowed = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $this->modx->getOption('mxheadless.allowed_contexts', null, 'web,mgr')),
        )));
        if ($allowed !== [] && !in_array($key, $allowed, true)) {
            throw new ValidationException('Invalid context', [
                'context_key' => ['Context not allowed'],
            ]);
        }

        $this->authorizer->assertContextKeyAccess($request, $key);

        $table = $this->modx->getTableName(modContext::class);
        $statement = $this->modx->prepare("SELECT 1 FROM {$table} WHERE {$this->modx->escape('key')} = ? LIMIT 1");
        if ($statement === false || !$statement->execute([$key]) || $statement->fetchColumn() === false) {
            throw new ValidationException('Invalid context', [
                'context_key' => ['Context does not exist'],
            ]);
        }

        if ($this->modx->getContext($key) === null) {
            throw new ValidationException('Invalid context', [
                'context_key' => ['Context is not loadable in this request'],
            ]);
        }

        return $key;
    }

    private function assertWritableClassKey(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException('Invalid field type', [
                'class_key' => ['Must be a non-empty class name'],
            ]);
        }

        $classKey = trim($value);
        if (!class_exists($classKey)) {
            throw new ValidationException('Invalid field type', [
                'class_key' => ['Class does not exist'],
            ]);
        }

        if ($classKey !== modResource::class && !is_subclass_of($classKey, modResource::class)) {
            throw new ValidationException('Invalid field type', [
                'class_key' => ['Class must extend modResource'],
            ]);
        }

        return $classKey;
    }

    private function assertWritableParent(
        xPDOObject $object,
        \MxHeadless\Definition\ObjectDefinition $definition,
        mixed $value,
    ): int {
        if (!is_int($value)) {
            throw new ValidationException('Invalid field type', [
                'parent' => ['Must be an integer'],
            ]);
        }

        if ($value < 0) {
            throw new ValidationException('Invalid field type', [
                'parent' => ['Must be a non-negative integer'],
            ]);
        }

        $currentId = (int) ($object->get($definition->getPrimaryKey()) ?? 0);
        if ($value > 0 && $currentId > 0 && $value === $currentId) {
            throw new ValidationException('Invalid field value', [
                'parent' => ['Resource cannot be its own parent'],
            ]);
        }

        if ($value === 0) {
            return 0;
        }

        $parentClass = $definition->objectClass() !== '' ? $definition->objectClass() : modResource::class;
        $parent = $this->modx->getObject($parentClass, [
            $definition->getPrimaryKey() => $value,
        ]);
        if (!$parent instanceof xPDOObject) {
            // class_key on parent may differ; fall back to base resource table lookup.
            $parent = $this->modx->getObject(modResource::class, ['id' => $value]);
        }
        if (!$parent instanceof xPDOObject) {
            throw new ValidationException('Invalid field value', [
                'parent' => ['Parent resource not found'],
            ]);
        }

        if ($currentId > 0) {
            $this->assertParentDoesNotCreateCycle($currentId, $value);
        }

        return $value;
    }

    private function assertParentDoesNotCreateCycle(int $resourceId, int $parentId): void
    {
        $table = $this->modx->getTableName(modResource::class);
        $statement = $this->modx->prepare(
            "SELECT {$this->modx->escape('parent')} FROM {$table} WHERE {$this->modx->escape('id')} = ? LIMIT 1",
        );
        if ($statement === false) {
            return;
        }

        $seen = [];
        $cursor = $parentId;
        for ($depth = 0; $depth < 1000 && $cursor > 0; $depth++) {
            if ($cursor === $resourceId) {
                throw new ValidationException('Invalid field value', [
                    'parent' => ['Parent would create a cycle in the resource tree'],
                ]);
            }
            if (isset($seen[$cursor])) {
                break;
            }
            $seen[$cursor] = true;
            if (!$statement->execute([$cursor])) {
                break;
            }
            $next = $statement->fetchColumn();
            $statement->closeCursor();
            if ($next === false || $next === null) {
                break;
            }
            $cursor = (int) $next;
        }
    }

    private function assertWritableContentType(mixed $value): int
    {
        if (!is_int($value)) {
            throw new ValidationException('Invalid field type', [
                'content_type' => ['Must be an integer'],
            ]);
        }
        if ($value < 1) {
            throw new ValidationException('Invalid field type', [
                'content_type' => ['Must be a positive integer'],
            ]);
        }

        $table = $this->modx->getTableName('MODX\\Revolution\\modContentType');
        $statement = $this->modx->prepare(
            "SELECT 1 FROM {$table} WHERE {$this->modx->escape('id')} = ? LIMIT 1",
        );
        if ($statement === false || !$statement->execute([$value]) || $statement->fetchColumn() === false) {
            throw new ValidationException('Invalid field value', [
                'content_type' => ['Content type not found'],
            ]);
        }

        return $value;
    }

    private function assertWritableTemplate(mixed $value): int
    {
        if (!is_int($value)) {
            throw new ValidationException('Invalid field type', [
                'template' => ['Must be an integer'],
            ]);
        }
        if ($value < 0) {
            throw new ValidationException('Invalid field type', [
                'template' => ['Must be a non-negative integer'],
            ]);
        }
        if ($value === 0) {
            return 0;
        }

        $table = $this->modx->getTableName('MODX\\Revolution\\modTemplate');
        $statement = $this->modx->prepare(
            "SELECT 1 FROM {$table} WHERE {$this->modx->escape('id')} = ? LIMIT 1",
        );
        if ($statement === false || !$statement->execute([$value]) || $statement->fetchColumn() === false) {
            throw new ValidationException('Invalid field value', [
                'template' => ['Template not found'],
            ]);
        }

        return $value;
    }

    private function normalizeAliasValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new ValidationException('Invalid field type', [
                'alias' => ['Must be a string'],
            ]);
        }

        $alias = trim((string) $value);
        if ($alias === '') {
            return '';
        }

        if (strlen($alias) > 255) {
            throw new ValidationException('Invalid field value', [
                'alias' => ['Alias must be at most 255 characters'],
            ]);
        }

        return $alias;
    }

    private function assertAliasUnique(
        xPDOObject $object,
        \MxHeadless\Definition\ObjectDefinition $definition,
    ): void {
        if (!in_array('alias', $definition->getFields(), true)) {
            return;
        }

        $alias = trim((string) ($object->get('alias') ?? ''));
        if ($alias === '') {
            return;
        }

        $table = $this->modx->getTableName(modResource::class);
        $currentId = (int) ($object->get($definition->getPrimaryKey()) ?? 0);
        $parent = (int) ($object->get('parent') ?? 0);
        $context = (string) ($object->get('context_key') ?? 'web');

        $sql = "SELECT {$this->modx->escape('id')} FROM {$table}
            WHERE {$this->modx->escape('alias')} = ?
              AND {$this->modx->escape('parent')} = ?
              AND {$this->modx->escape('context_key')} = ?
              AND {$this->modx->escape('deleted')} = 0";
        $params = [$alias, $parent, $context];
        if ($currentId > 0) {
            $sql .= " AND {$this->modx->escape('id')} <> ?";
            $params[] = $currentId;
        }
        $sql .= ' LIMIT 1';

        $statement = $this->modx->prepare($sql);
        if ($statement !== false && $statement->execute($params) && $statement->fetchColumn() !== false) {
            throw new ValidationException('Invalid field value', [
                'alias' => ['Alias already exists for this parent and context'],
            ]);
        }
    }

    private function normalizeWritableValue(string $field, mixed $value): mixed
    {
        if (is_array($value)) {
            throw new ValidationException('Invalid field type', [$field => ['Must be a scalar value']]);
        }

        if ($value === null) {
            return null;
        }

        if (in_array($field, self::BOOLEAN_FIELDS, true)) {
            return $this->normalizeBooleanValue($field, $value);
        }

        if (in_array($field, self::INTEGER_FIELDS, true)) {
            return $this->normalizeIntegerValue($field, $value);
        }

        if (is_string($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        throw new ValidationException('Invalid field type', [$field => ['Must be a scalar value']]);
    }

    private function normalizeBooleanValue(string $field, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 0 || $value === 1) {
            return $value === 1;
        }
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if ($normalized === '1' || $normalized === 'true') {
                return true;
            }
            if ($normalized === '0' || $normalized === 'false') {
                return false;
            }
        }

        throw new ValidationException('Invalid field type', [$field => ['Must be a boolean']]);
    }

    private function normalizeIntegerValue(string $field, mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }
        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        throw new ValidationException('Invalid field type', [$field => ['Must be an integer']]);
    }
}
