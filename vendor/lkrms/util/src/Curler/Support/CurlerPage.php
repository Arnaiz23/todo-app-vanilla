<?php declare(strict_types=1);

namespace Lkrms\Curler\Support;

use Lkrms\Curler\Contract\ICurlerHeaders;
use Lkrms\Curler\Contract\ICurlerPage;
use Lkrms\Curler\Curler;
use RuntimeException;

/**
 * Implements ICurlerPage
 */
final class CurlerPage implements ICurlerPage
{
    /**
     * @var mixed[]
     */
    private $Entities;

    /**
     * @var int
     */
    private $EntityCount;

    /**
     * @var bool
     */
    private $IsLastPage;

    /**
     * @var string|null
     */
    private $NextUrl;

    /**
     * @var mixed[]|null
     */
    private $NextData;

    /**
     * @var ICurlerHeaders|null
     */
    private $NextHeaders;

    /**
     * @param mixed[] $entities Data extracted from the upstream response
     * @param Curler $curler The Curler instance that retrieved the page
     * @param null|string $nextUrl The URL of the next page, including the query component (if any)
     * @param null|bool $isLastPage Set if no more data is available
     * @param null|mixed[] $nextData Data to send in the body of the next request
     * @param null|ICurlerHeaders $nextHeaders Replaces the next request's HTTP headers
     */
    public function __construct(
        array $entities,
        Curler $curler,
        ?ICurlerPage $previous,
        ?string $nextUrl = null,
        ?bool $isLastPage = null,
        ?array $nextData = null,
        ?ICurlerHeaders $nextHeaders = null
    ) {
        $this->Entities = $entities;
        $this->EntityCount = count($entities) + ($previous ? $previous->entityCount() : 0);
        $this->IsLastPage = is_null($isLastPage) ? empty($nextUrl) : $isLastPage;

        if (!$this->IsLastPage) {
            $this->NextUrl = $nextUrl ?: $curler->BaseUrl . $curler->QueryString;
            $this->NextData = $nextData;
            $this->NextHeaders = $nextHeaders;
        }
    }

    final public function entities(): array
    {
        return $this->Entities;
    }

    final public function entityCount(): int
    {
        return $this->EntityCount;
    }

    final public function isLastPage(): bool
    {
        return $this->IsLastPage;
    }

    private function assertHasNextPage(): void
    {
        if ($this->IsLastPage) {
            throw new RuntimeException('No more pages');
        }
    }

    final public function nextUrl(): string
    {
        $this->assertHasNextPage();

        return $this->NextUrl;
    }

    final public function nextData(): ?array
    {
        $this->assertHasNextPage();

        return $this->NextData;
    }

    final public function nextHeaders(): ?ICurlerHeaders
    {
        $this->assertHasNextPage();

        return $this->NextHeaders;
    }
}
