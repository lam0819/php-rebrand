<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Web\Models\PhpRelease;
use Illuminate\Contracts\View\View;

final class DownloadsController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.downloads', [
            'releases' => PhpRelease::query()->orderByDesc('branch')->get(),
        ]);
    }
}
