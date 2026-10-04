<?php

namespace App\Http\Controllers;

use App\Models\BookPage;
use App\Models\BookSetting;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'settings' => BookSetting::query()->find(1)?->data ?? [],
            'pages' => BookPage::query()
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(fn (BookPage $page) => $page->toBookData())
                ->values(),
        ]);
    }
}
