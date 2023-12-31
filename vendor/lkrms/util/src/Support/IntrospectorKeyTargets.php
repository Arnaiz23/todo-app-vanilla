<?php declare(strict_types=1);

namespace Lkrms\Support;

use Lkrms\Concern\TFullyReadable;
use Lkrms\Contract\IProvider;
use Lkrms\Contract\IProviderContext;
use Lkrms\Contract\IReadable;
use Closure;

/**
 * How to create or update an instance from an array
 *
 * @property-read array<string,int> $Parameters Key => constructor parameter index
 * @property-read array<string,true> $PassByRefParameters Key => `true`
 * @property-read array<Closure(mixed[], ?string, TClass, ?TProvider, ?TContext): void> $Callbacks Arbitrary callbacks
 * @property-read array<string,string> $Methods Key => "magic" property method
 * @property-read array<string,string> $Properties Key => declared property name
 * @property-read string[] $MetaProperties Arbitrary keys
 * @property-read string[] $DateProperties Date keys
 * @property-read array<TIntrospector::*_KEY,string> $CustomKeys Identifier => key
 *
 * @see Introspector
 *
 * @template TIntrospector of Introspector
 * @template TClass of object
 * @template TProvider of IProvider
 * @template TContext of IProviderContext
 */
final class IntrospectorKeyTargets implements IReadable
{
    use TFullyReadable;

    /**
     * Key => constructor parameter index
     *
     * @var array<string,int>
     */
    protected $Parameters;

    /**
     * Key => `true`
     *
     * @var array<string,true>
     */
    protected $PassByRefParameters;

    /**
     * Arbitrary callbacks
     *
     * @var array<Closure(mixed[], ?string, TClass, ?TProvider, ?TContext): void>
     */
    protected $Callbacks;

    /**
     * Key => "magic" property method
     *
     * @var array<string,string>
     */
    protected $Methods;

    /**
     * Key => declared property name
     *
     * @var array<string,string>
     */
    protected $Properties;

    /**
     * Arbitrary keys
     *
     * @var string[]
     */
    protected $MetaProperties;

    /**
     * Date keys
     *
     * @var string[]
     */
    protected $DateProperties;

    /**
     * Identifier => key
     *
     * @var array<TIntrospector::*_KEY,string>
     */
    protected $CustomKeys;

    /**
     * @param array<string,int> $parameters
     * @param array<string,true> $passByRefProperties
     * @param array<Closure(mixed[], ?string, TClass, ?TProvider, ?TContext): void> $callbacks
     * @param array<string,string> $methods
     * @param array<string,string> $properties
     * @param string[] $metaProperties
     * @param string[] $dateProperties
     * @param array<TIntrospector::*_KEY,string> $customKeys
     */
    public function __construct(
        array $parameters,
        array $passByRefProperties,
        array $callbacks,
        array $methods,
        array $properties,
        array $metaProperties,
        array $dateProperties,
        array $customKeys
    ) {
        $this->Parameters = $parameters;
        $this->PassByRefParameters = $passByRefProperties;
        $this->Callbacks = $callbacks;
        $this->Methods = $methods;
        $this->Properties = $properties;
        $this->MetaProperties = $metaProperties;
        $this->DateProperties = $dateProperties;
        $this->CustomKeys = $customKeys;
    }
}
