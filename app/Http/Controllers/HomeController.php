<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Web\Models\NewsItem;
use App\Web\Models\PhpRelease;
use App\Web\Support\WebContentPresenter;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __invoke(WebContentPresenter $presenter): View
    {
        $cards = $presenter->releaseCards(PhpRelease::query()->get());

        return view('home', [
            'releases' => $cards,
            'latestVersion' => $cards[0]['version'] ?? '8.5',
            'news' => NewsItem::query()->latest('published_at')->limit(3)->get(),
            'presenter' => $presenter,
        ]);
    }
}
