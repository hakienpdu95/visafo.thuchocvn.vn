<?php

namespace Modules\HanoiCheck\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use InvalidArgumentException;
use Modules\HanoiCheck\Services\HanoiCheckClient;

class GetHanoiCheckCatalogHandler implements QueryHandlerInterface
{
    public function __construct(private readonly HanoiCheckClient $client) {}

    public function handle(QueryInterface $query): array
    {
        $path = GetHanoiCheckCatalogQuery::CATALOGS[$query->catalog]
            ?? throw new InvalidArgumentException('Danh mục hợp lệ: ' . implode(', ', array_keys(GetHanoiCheckCatalogQuery::CATALOGS)));

        $rows = collect($this->client->get($path)['json']['data'] ?? []);

        if (filled($query->search)) {
            $needle = mb_strtolower($query->search);
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower((string) ($r['name'] ?? '')), $needle));
        }

        return $rows->values()->all();
    }
}
