<?php

namespace App\Observers;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Category;

class CategoryCacheObserver
{
    public function saved(Category $category): void
    {
        HeaderComposer::forgetCategoryCache();
    }

    public function deleted(Category $category): void
    {
        HeaderComposer::forgetCategoryCache();
    }
}
