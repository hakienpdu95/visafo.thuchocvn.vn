<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Laravelcm\Subscriptions\Models\Feature as SubscriptionsFeature;

class Feature extends SubscriptionsFeature
{
    use HasUlids;
}
