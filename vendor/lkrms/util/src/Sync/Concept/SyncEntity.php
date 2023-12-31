<?php declare(strict_types=1);

namespace Lkrms\Sync\Concept;

use Lkrms\Concept\Entity;
use Lkrms\Concern\HasNormaliser;
use Lkrms\Concern\RequiresContainer;
use Lkrms\Concern\TConstructible;
use Lkrms\Concern\TExtensible;
use Lkrms\Concern\TProvidable;
use Lkrms\Concern\TReadable;
use Lkrms\Concern\TWritable;
use Lkrms\Contract\IContainer;
use Lkrms\Contract\IProvider;
use Lkrms\Contract\IProviderContext;
use Lkrms\Contract\ReturnsNormaliser;
use Lkrms\Facade\Sync;
use Lkrms\Iterator\Contract\FluentIteratorInterface;
use Lkrms\Iterator\IterableIterator;
use Lkrms\Support\Catalog\ArrayKeyConformity;
use Lkrms\Support\Catalog\NormaliserFlag;
use Lkrms\Support\Catalog\TextComparisonAlgorithm as Algorithm;
use Lkrms\Support\Catalog\TextComparisonFlag as Flag;
use Lkrms\Support\DateFormatter;
use Lkrms\Sync\Catalog\SyncEntityLinkType as LinkType;
use Lkrms\Sync\Catalog\SyncEntityState;
use Lkrms\Sync\Contract\ISyncContext;
use Lkrms\Sync\Contract\ISyncEntity;
use Lkrms\Sync\Contract\ISyncEntityProvider;
use Lkrms\Sync\Contract\ISyncProvider;
use Lkrms\Sync\Exception\SyncEntityNotFoundException;
use Lkrms\Sync\Support\DeferredEntity;
use Lkrms\Sync\Support\DeferredRelationship;
use Lkrms\Sync\Support\SyncIntrospector;
use Lkrms\Sync\Support\SyncSerializeRules as SerializeRules;
use Lkrms\Sync\Support\SyncSerializeRulesBuilder as SerializeRulesBuilder;
use Lkrms\Sync\Support\SyncStore;
use Lkrms\Utility\Convert;
use Lkrms\Utility\Pcre;
use Lkrms\Utility\Test;
use Closure;
use DateTimeInterface;
use Generator;
use LogicException;
use ReflectionClass;
use RuntimeException;
use UnexpectedValueException;

/**
 * Represents the state of an entity in an external system
 *
 * {@see SyncEntity} implements {@see \Lkrms\Contract\IReadable} and
 * {@see \Lkrms\Contract\IWritable}, but `protected` properties are not
 * accessible by default. Override {@see SyncEntity::getReadable()} and/or
 * {@see SyncEntity::getWritable()} to change this.
 *
 * The following "magic" property methods are discovered automatically and don't
 * need to be returned by {@see SyncEntity::getReadable()} or
 * {@see SyncEntity::getWritable()}:
 * - `protected function _get<PropertyName>()`
 * - `protected function _isset<PropertyName>()` (optional; falls back to
 *   `_get<PropertyName>()` if not declared)
 * - `protected function _set<PropertyName>($value)`
 * - `protected function _unset<PropertyName>()` (optional; falls back to
 *   `_set<PropertyName>(null)` if not declared)
 *
 * Accessible properties are mapped to associative arrays with snake_case keys
 * when {@see SyncEntity} objects are serialized. Override
 * {@see SyncEntity::buildSerializeRules()} to provide serialization rules for
 * nested entities.
 */
abstract class SyncEntity extends Entity implements ISyncEntity, ReturnsNormaliser
{
    /**
     * @use TProvidable<ISyncProvider,ISyncContext>
     */
    use TConstructible, TReadable, TWritable, TExtensible, TProvidable, HasNormaliser, RequiresContainer;

    /**
     * The unique identifier assigned to the entity by its provider
     *
     * @see ISyncEntity::id()
     *
     * @var int|string|null
     */
    public $Id;

    /**
     * The unique identifier assigned to the entity by its canonical backend
     *
     * @see ISyncEntity::canonicalId()
     *
     * @var int|string|null
     */
    public $CanonicalId;

    /**
     * @var ISyncProvider|null
     */
    private $Provider;

    /**
     * @var ISyncContext|null
     */
    private $Context;

    /**
     * @var int-mask-of<SyncEntityState::*>
     */
    private $State = 0;

    /**
     * Entity => entity type ID
     *
     * @var array<class-string<SyncEntity>,int>
     */
    private static $EntityTypeId = [];

    /**
     * Entity => [ property name => normalised property name ]
     *
     * @var array<class-string<SyncEntity>,array<string,string>>
     */
    private static $NormalisedPropertyMap = [];

    /**
     * @inheritDoc
     */
    public static function plural(): string
    {
        return Convert::nounToPlural(Convert::classToBasename(static::class));
    }

    /**
     * @inheritDoc
     */
    public static function getRelationships(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getDateProperties(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function name(): ?string
    {
        return SyncIntrospector::get(static::class)
            ->getGetNameClosure()($this);
    }

    /**
     * @inheritDoc
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * Override to specify how object graphs below entities of this type should
     * be serialized
     *
     * To prevent infinite recursion when entities of this type are serialized,
     * return a {@see SerializeRulesBuilder} object configured to remove or
     * replace circular references.
     *
     * @param SerializeRulesBuilder<static> $rulesB
     * @return SerializeRulesBuilder<static>
     */
    protected static function buildSerializeRules(SerializeRulesBuilder $rulesB): SerializeRulesBuilder
    {
        return $rulesB;
    }

    /**
     * Override to specify prefixes to remove when normalising field/property
     * names
     *
     * Entity names are removed by default, e.g. for a {@see SyncEntity}
     * subclass called `User`, "User" is removed to ensure fields like "USER_ID"
     * and "USER_NAME" match properties like "Id" and "Name". For a subclass of
     * `User` called `AdminUser`, both "User" and "AdminUser" are removed.
     *
     * Return `null` to suppress prefix removal.
     *
     * @return string[]|null
     */
    protected static function getRemovablePrefixes(): ?array
    {
        $current = new ReflectionClass(static::class);
        while ($current->isSubclassOf(self::class)) {
            $prefixes[] = Convert::classToBasename($current->getName());
            $current = $current->getParentClass();
        }
        return $prefixes ?? [];
    }

    // --

    /**
     * @inheritDoc
     */
    final public function id()
    {
        return $this->Id;
    }

    /**
     * @inheritDoc
     */
    final public function canonicalId()
    {
        return $this->CanonicalId;
    }

    /**
     * @inheritDoc
     */
    final public static function defaultProvider(?IContainer $container = null): ISyncProvider
    {
        return self::requireContainer($container)->get(
            SyncIntrospector::entityToProvider(static::class)
        );
    }

    /**
     * @inheritDoc
     */
    final public static function withDefaultProvider(?IContainer $container = null, ?ISyncContext $context = null): ISyncEntityProvider
    {
        return static::defaultProvider($container)->with(
            static::class, $context
        );
    }

    /**
     * @inheritDoc
     */
    final public static function getSerializeRules(?IContainer $container = null): SerializeRules
    {
        $container = self::requireContainer($container);
        $rulesB = SerializeRulesBuilder::build($container)->entity(static::class);
        $rulesB = static::buildSerializeRules($rulesB);

        return $rulesB->go();
    }

    /**
     * Get a closure that normalises a property name
     *
     * If {@see SyncEntity::getRemovablePrefixes()} doesn't return any prefixes,
     * a closure that converts the property name to snake_case is returned.
     *
     * Otherwise, the closure converts the property name to snake_case, and if
     * `$greedy` is `true` (the default) and the property name doesn't match one
     * of the provided `$hints`, prefixes are removed before it is returned.
     *
     * If a prefix returned by {@see SyncEntity::getRemovablePrefixes()}
     * contains multiple words (e.g. "admin_user_group"), additional prefixes to
     * remove are derived by shifting each word off the beginning of the prefix
     * (e.g. "user_group" and "group").
     */
    final public static function normaliser(): Closure
    {
        // If there aren't any prefixes to remove, return a closure that
        // converts everything to snake_case
        if (!($prefixes = static::getRemovablePrefixes())) {
            return
                static function (string $name): string {
                    return self::$NormalisedPropertyMap[static::class][$name]
                        ?? (self::$NormalisedPropertyMap[static::class][$name] =
                            Convert::toSnakeCase($name));
                };
        }

        // ['admin_user_group'] -> ['admin_user_group', 'user_group', 'group']
        foreach ($prefixes as $prefix) {
            $prefix = Convert::toSnakeCase($prefix);
            $expanded[$prefix] = true;
            $prefix = explode('_', $prefix);
            while (array_shift($prefix)) {
                $expanded[implode('_', $prefix)] = true;
            }
        }
        $prefixes = array_keys($expanded);
        $regex = implode('|', $prefixes);
        $regex = count($prefixes) > 1 ? "(?:$regex)" : $regex;
        $regex = "/^{$regex}_/";

        return
            static function (string $name, bool $greedy = true, string ...$hints) use ($regex): string {
                if ($greedy && !$hints) {
                    return self::$NormalisedPropertyMap[static::class][$name]
                        ?? (self::$NormalisedPropertyMap[static::class][$name] =
                            Pcre::replace($regex, '', Convert::toSnakeCase($name)));
                }
                $_name = Convert::toSnakeCase($name);
                if (!$greedy || in_array($_name, $hints)) {
                    return $_name;
                }

                return Pcre::replace($regex, '', $_name);
            };
    }

    /**
     * @inheritDoc
     */
    final public function toArray(): array
    {
        return $this->_toArray(static::getSerializeRules());
    }

    /**
     * @inheritDoc
     */
    final public function toArrayWith($rules): array
    {
        return $this->_toArray(SerializeRules::resolve($rules));
    }

    /**
     * @inheritDoc
     */
    final public function toLink(int $type = LinkType::DEFAULT, bool $compact = true): array
    {
        switch ($type) {
            case LinkType::INTERNAL:
            case LinkType::DEFAULT:
                return [
                    '@type' => $this->typeUri($compact),
                    '@id' => is_null($this->Id)
                        ? spl_object_id($this)
                        : $this->Id,
                ];

            case LinkType::COMPACT:
                return [
                    '@id' => $this->uri($compact),
                ];

            case LinkType::FRIENDLY:
                return array_filter([
                    '@type' => $this->typeUri($compact),
                    '@id' => is_null($this->Id)
                        ? spl_object_id($this)
                        : $this->Id,
                    '@name' => $this->name(),
                    '@description' => $this->description(),
                ]);

            default:
                throw new LogicException("Invalid link type: $type");
        }
    }

    /**
     * @inheritDoc
     */
    final public function uri(bool $compact = true): string
    {
        return sprintf(
            '%s/%s',
            $this->typeUri($compact),
            is_null($this->Id)
                ? spl_object_id($this)
                : $this->Id
        );
    }

    /**
     * Get the current state of the entity
     *
     * @return int-mask-of<SyncEntityState::*>
     */
    final public function state(): int
    {
        return $this->State;
    }

    /**
     * Get the entity store servicing the entity's provider or the Sync facade
     */
    final protected function store(): SyncStore
    {
        return $this->Provider
            ? $this->Provider->store()
            : Sync::getInstance();
    }

    private function typeUri(bool $compact): string
    {
        $service = $this->service();
        $typeUri = $this->store()->getEntityTypeUri($service, $compact);

        return $typeUri
            ?? '/' . str_replace('\\', '/', ltrim($service, '\\'));
    }

    /**
     * @param SerializeRules<static> $rules
     * @return array<string,mixed>
     */
    private function _toArray(SerializeRules $rules): array
    {
        $array = $this;
        $this->_serialize($array, [], $rules);

        return (array) $array;
    }

    /**
     * @param SyncEntity|DeferredEntity<SyncEntity>|DeferredRelationship<SyncEntity>|mixed[] $node
     * @param string[] $path
     * @param SerializeRules<static> $rules
     * @param array<int,true> $parents
     */
    private function _serialize(&$node, array $path, SerializeRules $rules, array $parents = []): void
    {
        if (!is_null($maxDepth = $rules->getMaxDepth()) && count($path) > $maxDepth) {
            throw new RuntimeException('In too deep: ' . implode('.', $path));
        }

        /** @todo Serialize deferred relationships */
        if ($node instanceof DeferredRelationship) {
            $node = null;

            return;
        }

        if ($node instanceof DeferredEntity) {
            $node = $node->toLink($rules->getFlags() & SerializeRules::SYNC_STORE
                ? LinkType::INTERNAL
                : LinkType::DEFAULT);

            return;
        }

        if ($node instanceof SyncEntity) {
            if ($path && $rules->getFlags() & SerializeRules::SYNC_STORE) {
                $node = $node->toLink(LinkType::INTERNAL);

                return;
            }

            if ($rules->getDetectRecursion()) {
                // Prevent infinite recursion by replacing each SyncEntity
                // descended from itself with a link
                if ($parents[spl_object_id($node)] ?? false) {
                    $node = $node->toLink(LinkType::DEFAULT);
                    $node['@why'] = 'Circular reference detected';

                    return;
                }
                $parents[spl_object_id($node)] = true;
            }

            $class = get_class($node);
            $node = $node->serialize($rules);
        }

        $delete = $rules->getRemove($class ?? null, null, $path);
        $replace = $rules->getReplace($class ?? null, null, $path);

        // Don't delete values returned in both lists
        $delete = array_diff_key($delete, $replace);

        if ($delete) {
            $node = array_diff_key($node, array_flip($delete));
        }
        foreach ($replace as $rule) {
            if (is_array($rule)) {
                $_rule = $rule;
                $key = array_shift($rule);
                $newKey = $key;
                $callback = null;

                while ($rule) {
                    $arg = array_shift($rule);
                    if (is_string($arg)) {
                        $newKey = SyncIntrospector::get(static::class)
                            ->maybeNormalise($arg, NormaliserFlag::CAREFUL);
                        continue;
                    }
                    if ($arg instanceof Closure) {
                        $callback = $arg;
                        continue;
                    }
                    throw new LogicException('Invalid rule: ' . var_export($_rule, true));
                }

                if ($key !== '[]') {
                    if (!array_key_exists($key, $node)) {
                        continue;
                    }

                    if ($key !== $newKey) {
                        if (array_key_exists($newKey, $node)) {
                            throw new UnexpectedValueException("Cannot rename '$key': '$newKey' already set");
                        }
                        Convert::arraySpliceAtKey($node, $key, 1, [$newKey => $node[$key]]);
                        $key = $newKey;
                    }

                    if ($callback) {
                        $node[$key] = $callback($node[$key]);

                        continue;
                    }
                } elseif ($callback) {
                    $node = array_map($callback, $node);

                    continue;
                }
            } else {
                $key = $rule;
            }

            if ($key === '[]') {
                $_path = $path;
                $lastKey = array_pop($_path);
                $_path[] = $lastKey . '[]';

                foreach ($node as &$child) {
                    $this->_serializeId($child, $_path);
                }
                unset($child);

                continue;
            }

            if (!array_key_exists($key, $node)) {
                continue;
            }

            $_path = $path;
            $_path[] = $key;
            $this->_serializeId($node[$key], $_path);
        }

        if (is_array($node)) {
            if (Test::isIndexedArray($node)) {
                $isList = true;
                $lastKey = array_pop($path);
                $path[] = $lastKey . '[]';
            }
            foreach ($node as $key => &$child) {
                if (is_null($child) || is_scalar($child)) {
                    continue;
                }
                if (!($isList ?? null)) {
                    $_path = $path;
                    $_path[] = $key;
                }
                $this->_serialize($child, $_path ?? $path, $rules, $parents);
            }
        } elseif ($node instanceof DateTimeInterface) {
            $node = ($rules->getDateFormatter()
                ?: ($this->provider() ? $this->provider()->dateFormatter() : null)
                ?: new DateFormatter())->format($node);
        } else {
            throw new UnexpectedValueException('Array or SyncEntity expected: ' . print_r($node, true));
        }
    }

    /**
     * @param SyncEntity[]|SyncEntity|null $node
     * @param string[] $path
     */
    private function _serializeId(&$node, array $path): void
    {
        if (is_null($node)) {
            return;
        }

        if (Test::isArrayOf($node, SyncEntity::class, true) && Test::isIndexedArray($node, true)) {
            /** @var SyncEntity $child */
            foreach ($node as &$child) {
                $child = $child->Id;
            }

            return;
        }

        if (!($node instanceof SyncEntity)) {
            throw new UnexpectedValueException('Cannot replace (not a SyncEntity): ' . implode('.', $path));
        }

        $node = $node->Id;
    }

    /**
     * Convert the entity to an associative array
     *
     * Nested objects and lists are returned as-is. Only the top-level entity is
     * replaced.
     *
     * @param SerializeRules<static> $rules
     * @return array<string,mixed>
     */
    private function serialize(SerializeRules $rules): array
    {
        $clone = clone $this;
        $clone->State |= SyncEntityState::SERIALIZING;
        $array = SyncIntrospector::get(static::class)
            ->getSerializeClosure($rules)($clone);

        if ($rules->getRemoveCanonicalId()) {
            unset($array[
                SyncIntrospector::get(static::class)
                    ->maybeNormalise('CanonicalId', NormaliserFlag::CAREFUL)
            ]);
        }

        return $array;
    }

    /**
     * @inheritDoc
     */
    final public static function setEntityTypeId(int $entityTypeId): void
    {
        self::$EntityTypeId[static::class] = $entityTypeId;
    }

    /**
     * @inheritDoc
     */
    final public static function getEntityTypeId(): ?int
    {
        return self::$EntityTypeId[static::class] ?? null;
    }

    /**
     * @internal
     *
     * @return array<string,mixed>
     */
    final public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     *
     * @param ISyncProvider $provider
     * @param ISyncContext|null $context
     */
    final public static function provide(
        array $data,
        IProvider $provider,
        ?IProviderContext $context = null
    ) {
        $container = $context
            ? $context->container()
            : $provider->container();
        $container = $container->inContextOf(get_class($provider));

        $context = $context
            ? $context->withContainer($container)
            : $provider->getContext($container);

        $closure = SyncIntrospector::getService(
            $container, static::class
        )->getCreateSyncEntityFromClosure();

        return $closure($data, $provider, $context);
    }

    /**
     * @inheritDoc
     *
     * @param ISyncProvider $provider
     * @param ISyncContext|null $context
     */
    final public static function provideList(
        iterable $list,
        IProvider $provider,
        $conformity = ArrayKeyConformity::NONE,
        ?IProviderContext $context = null
    ): FluentIteratorInterface {
        return IterableIterator::from(
            self::_provideList($list, $provider, $conformity, $context)
        );
    }

    /**
     * @inheritDoc
     */
    final public static function idFromNameOrId(
        $nameOrId,
        $providerOrContext,
        ?float $uncertaintyThreshold = null,
        ?string $nameProperty = null,
        ?float &$uncertainty = null
    ) {
        if ($nameOrId === null) {
            $uncertainty = null;
            return null;
        }

        if ($providerOrContext instanceof ISyncProvider) {
            $provider = $providerOrContext;
            $context = $provider->container();
        } else {
            $context = $providerOrContext;
            $provider = $context->provider();
        }

        if ($provider->isValidIdentifier($nameOrId, static::class)) {
            $uncertainty = 0.0;
            return $nameOrId;
        }

        $entity =
            $provider
                ->with(static::class, $context)
                ->getResolver(
                    $nameProperty,
                    Algorithm::SAME | Algorithm::CONTAINS | Algorithm::NGRAM_SIMILARITY | Flag::NORMALISE,
                    $uncertaintyThreshold,
                    null,
                    true,
                )
                ->getByName($nameOrId, $uncertainty);

        if ($entity) {
            return $entity->Id;
        }

        throw new SyncEntityNotFoundException(
            $provider,
            static::class,
            $nameProperty === null
                ? ['name' => $nameOrId]
                : [$nameProperty => $nameOrId],
        );
    }

    /**
     * @inheritDoc
     */
    public function postLoad(): void {}

    /**
     * @return array<string,mixed>
     */
    public function __serialize(): array
    {
        foreach ([
            ...SyncIntrospector::get(static::class)->SerializableProperties,
            'MetaProperties',
            'MetaPropertyNames',
        ] as $property) {
            $data[$property] = $this->{$property};
        }

        $data['Provider'] = $this->Provider === null
            ? null
            : $this->store()->getProviderHash($this->Provider);

        return $data;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $property => $value) {
            if ($property === 'Provider' && $value !== null) {
                $value = $this->store()->getProvider($value);
                if (!$value) {
                    throw new RuntimeException('Cannot unserialize missing provider');
                }
            }
            $this->{$property} = $value;
        }
    }

    /**
     * @param iterable<array-key,mixed[]> $list
     * @param ISyncProvider $provider
     * @param ArrayKeyConformity::* $conformity
     * @param ISyncContext|null $context
     * @return Generator<array-key,static>
     */
    private static function _provideList(
        iterable $list,
        IProvider $provider,
        $conformity,
        ?IProviderContext $context
    ): Generator {
        $container = $context
            ? $context->container()
            : $provider->container();
        $container = $container->inContextOf(get_class($provider));

        $context = $context
            ? $context->withContainer($container)
            : $provider->getContext($container);
        $context = $context->withConformity($conformity);

        $introspector = SyncIntrospector::getService($container, static::class);

        foreach ($list as $key => $data) {
            if (!isset($closure)) {
                $closure =
                    in_array($conformity, [ArrayKeyConformity::PARTIAL, ArrayKeyConformity::COMPLETE])
                        ? $introspector->getCreateSyncEntityFromSignatureClosure(array_keys($data))
                        : $introspector->getCreateSyncEntityFromClosure();
            }

            yield $key => $closure($data, $provider, $context);
        }
    }
}
