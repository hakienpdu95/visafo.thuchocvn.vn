<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Laravelcm\Subscriptions\Models\Plan as SubscriptionsPlan;

class Plan extends SubscriptionsPlan
{
    use HasUlids;
}
