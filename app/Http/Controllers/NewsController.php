<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Web\Models\NewsItem;
use App\Web\Support\WebContentPresenter;
use Illuminate\Contracts\View\View;

final class NewsController extends Controller
{
    public function index(WebContentPresenter $presenter): View
    {
        $featured = NewsItem::query()->latest('published_at')->first();

        $items = NewsItem::query()->latest('published_at');
        if ($featured !== null) {
            $items->whereKeyNot($featured->getKey());
        }
        $items = $items->paginate(20);

        return view('pages.news', [
            'featured' => $featured,
            'items' => $items,
            'presenter' => $presenter,
        ]);
    }

    public function show(string $entry, WebContentPresenter $presenter): View
    {
        $item = NewsItem::query()->where('entry_id', $entry)->firstOrFail();

        return view('pages.news-item', [
            'item' => $item,
            'presenter' => $presenter,
        ]);
    }
}
