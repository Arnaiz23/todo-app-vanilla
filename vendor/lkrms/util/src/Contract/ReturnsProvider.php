<?php declare(strict_types=1);

namespace Lkrms\Contract;

/**
 * Returns the provider servicing the object
 *
 * @template TProvider of IProvider
 */
interface ReturnsProvider
{
    /**
     * Get the object's provider
     *
     * @return TProvider|null
     */
    public function provider(): ?IProvider;

    /**
     * Get the object's provider, or throw an exception if no provider has been
     * set
     *
     * @return TProvider
     */
    public function requireProvider(): IProvider;
}
