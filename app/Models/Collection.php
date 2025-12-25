<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Lunar\Models\Collection as LunarCollection;

class Collection extends LunarCollection
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
    use HasFactory;
}