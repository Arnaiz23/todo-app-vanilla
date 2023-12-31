<?php declare(strict_types=1);

namespace Lkrms\Sync\Exception;

use Lkrms\Sync\Contract\ISyncProvider;

/**
 * Thrown when an entity store's provider heartbeat check fails
 *
 * @see \Lkrms\Sync\Support\SyncStore::checkHeartbeats()
 */
class SyncProviderHeartbeatCheckFailedException extends SyncException
{
    /**
     * @var ISyncProvider[]
     */
    protected $Providers;

    public function __construct(ISyncProvider ...$provider)
    {
        $this->Providers = $provider;

        $count = count($provider);
        parent::__construct(
            $count === 1
                ? 'Provider backend unreachable'
                : sprintf('%d provider backends unreachable', $count)
        );
    }
}
