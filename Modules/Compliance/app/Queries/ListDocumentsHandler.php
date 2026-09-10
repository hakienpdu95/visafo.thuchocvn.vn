<?php

namespace Modules\Compliance\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Compliance\Enums\ComplianceDocumentStatus;
use Modules\Compliance\Models\ComplianceDocument;

class ListDocumentsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['document_number', 'issue_date', 'expiration_date', 'status', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListDocumentsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'expiration_date';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = ComplianceDocument::query()->with(['documentType', 'documentable']);

        if ($query->search !== null && $query->search !== '') {
            $q->where('document_number', 'like', '%' . $query->search . '%');
        }

        if ($query->documentableType !== null && $query->documentableType !== '') {
            $q->where('documentable_type', $query->documentableType);
        }

        if ($query->expiring) {
            $q->expiringWithinDays(30);
        } elseif ($query->expired) {
            $q->where(fn ($sub) => $sub->expired()->orWhere('status', ComplianceDocumentStatus::Expired->value));
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $q->orderBy('id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
