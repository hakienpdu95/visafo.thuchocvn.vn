<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Laravelcm\Subscriptions\Models\Subscription as SubscriptionsSubscription;

class Subscription extends SubscriptionsSubscription
{
    use HasUlids;
}
