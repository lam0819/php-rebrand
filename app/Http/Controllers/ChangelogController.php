<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Web\Models\ChangelogRelease;
use App\Web\Support\WebContentPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

final class ChangelogController extends Controller
{
    /**
     * The changelog index: released versions grouped by branch, newest first.
     */
    public function index(): View
    {
        /** @var Collection<string, Collection<int, ChangelogRelease>> $branches */
        $branches = ChangelogRelease::query()
            ->where('released', true)
            ->orderByDesc('released_on')
            ->get()
            ->groupBy('branch')
            ->sortKeysDesc();

        return view('pages.changelog', ['branches' => $branches]);
    }

    public function show(string $version, WebContentPresenter $presenter): View
    {
        $release = ChangelogRelease::query()->where('version', $version)->firstOrFail();

        $newer = ChangelogRelease::query()
            ->where('branch', $release->branch)
            ->where('released', true)
            ->where('version', '!=', $release->version)
            ->where('released_on', '>=', $release->released_on)
            ->orderBy('released_on')
            ->first();

        $older = ChangelogRelease::query()
            ->where('branch', $release->branch)
            ->where('released', true)
            ->where('version', '!=', $release->version)
            ->where('released_on', '<=', $release->released_on)
            ->orderByDesc('released_on')
            ->first();

        return view('pages.changelog-release', [
            'release' => $release,
            'newer' => $newer,
            'older' => $older,
            'presenter' => $presenter,
        ]);
    }
}
