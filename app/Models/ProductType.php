<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Lunar\Models\ProductType as LunarProductType;

class ProductType extends LunarProductType
{
    /** @use HasFactory<\Database\Factories\ProductTypeFactory> */
    use HasFactory;
}