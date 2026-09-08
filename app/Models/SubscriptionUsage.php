<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Laravelcm\Subscriptions\Models\SubscriptionUsage as SubscriptionsUsage;

class SubscriptionUsage extends SubscriptionsUsage
{
    use HasUlids;
}
