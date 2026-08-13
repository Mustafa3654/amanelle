<?php

namespace App\Providers;

use App\Services\CartService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * One cart per request.
         *
         * CartService memoises its resolved lines so a page does not re-query
         * the catalogue for every read. Without a shared instance, each
         * resolution gets its own memo: checkout clears the session on its
         * copy while a Livewire component still holds stale lines and shows
         * an emptied cart as full.
         *
         * scoped(), not singleton(): the instance must not survive into the
         * next request, or one customer's cart would be served to the next
         * under a persistent worker.
         */
        $this->app->scoped(CartService::class);
    }

    public function boot(): void
    {
        //
    }
}
