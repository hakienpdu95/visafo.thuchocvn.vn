<?php

namespace App\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

/**
 * Extends Spatie's Media model.
 *
 * IMPORTANT: extends SpatieMedia directly — NOT TenantAwareModel.
 * Reason: TenantAwareModel pulls in SoftDeletes which breaks Spatie's
 * hard-delete assumptions and schema (no deleted_at column on media table).
 */
class Media extends SpatieMedia
{
    use BelongsToOrganization;
    use HasUlids;
}
