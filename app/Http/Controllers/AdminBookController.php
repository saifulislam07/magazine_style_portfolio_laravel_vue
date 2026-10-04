<?php

namespace App\Http\Controllers;

use App\Models\BookPage;
use App\Models\BookSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminBookController extends Controller
{
    public function index(): View
    {
        return view('admin');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'settings' => BookSetting::query()->find(1)?->data ?? [],
            'pages' => BookPage::query()
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(fn (BookPage $page) => $page->toAdminData())
                ->values(),
        ]);
    }

    public function storePage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'content' => ['required', 'array'],
            ...$this->contentRules(),
        ]);

        $baseSlug = Str::slug($validated['label']) ?: 'chapter';
        $slug = $baseSlug;
        $suffix = 2;

        while (BookPage::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        $page = DB::transaction(function () use ($slug, $validated): BookPage {
            $pages = BookPage::query()->orderBy('position')->get();
            $colophon = $pages->firstWhere('kind', 'colophon');
            $position = $colophon?->position ?? $pages->count();

            BookPage::query()
                ->where('position', '>=', $position)
                ->update(['position' => DB::raw('position + 1')]);

            return BookPage::query()->create([
                'slug' => $slug,
                'kind' => 'chapter',
                'label' => $validated['label'],
                'position' => $position,
                'content' => $validated['content'],
            ]);
        });

        return response()->json(['page' => $page->toAdminData()], 201);
    }

    public function updatePage(Request $request, BookPage $page): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'content' => ['required', 'array'],
            ...$this->contentRules(),
        ]);

        $page->update([
            'label' => $validated['label'],
            'content' => $validated['content'],
        ]);

        return response()->json(['page' => $page->fresh()->toAdminData()]);
    }

    public function deletePage(BookPage $page): JsonResponse
    {
        abort_unless($page->kind === 'chapter', 422, 'Only chapter pages can be deleted.');

        DB::transaction(function () use ($page): void {
            $position = $page->position;
            $page->delete();

            BookPage::query()
                ->where('position', '>', $position)
                ->decrement('position');
        });

        return response()->json(['deleted' => true]);
    }

    public function reorderPages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'distinct', 'exists:book_pages,slug'],
        ]);

        $chapterIds = BookPage::query()
            ->where('kind', 'chapter')
            ->pluck('slug')
            ->all();
        $requestedIds = $validated['ids'];

        abort_unless(
            count($chapterIds) === count($requestedIds)
                && array_diff($chapterIds, $requestedIds) === []
                && array_diff($requestedIds, $chapterIds) === [],
            422,
            'The chapter list has changed. Refresh and try again.',
        );

        DB::transaction(function () use ($requestedIds): void {
            foreach ($requestedIds as $index => $slug) {
                BookPage::query()
                    ->where('slug', $slug)
                    ->update(['position' => $index + 2]);
            }

            BookPage::query()
                ->where('kind', 'colophon')
                ->update(['position' => count($requestedIds) + 2]);

            BookPage::query()
                ->where('kind', 'back-cover')
                ->update(['position' => count($requestedIds) + 3]);
        });

        return response()->json([
            'pages' => BookPage::query()
                ->orderBy('position')
                ->get()
                ->map(fn (BookPage $page) => $page->toAdminData())
                ->values(),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.brand' => ['required', 'string', 'max:80'],
            'settings.author' => ['required', 'string', 'max:100'],
            'settings.photography_label' => ['required', 'string', 'max:80'],
            'settings.cover_issue' => ['required', 'string', 'max:80'],
            'settings.cover_kicker' => ['required', 'string', 'max:100'],
            'settings.cover_title' => ['required', 'string', 'max:120'],
            'settings.cover_strapline' => ['required', 'string', 'max:140'],
            'settings.cover_open_text' => ['required', 'string', 'max:80'],
            'settings.back_cover_text' => ['required', 'string', 'max:140'],
            'settings.back_cover_year' => ['required', 'string', 'max:20'],
            'settings.contact_kicker' => ['required', 'string', 'max:100'],
            'settings.return_to_cover_text' => ['required', 'string', 'max:80'],
            'settings.site_description' => ['required', 'string', 'max:300'],
        ]);

        $setting = BookSetting::query()->find(1);

        if ($setting) {
            $setting->update(['data' => $validated['settings']]);
        } else {
            $setting = BookSetting::query()->create([
                'id' => 1,
                'data' => $validated['settings'],
            ]);
        }

        return response()->json(['settings' => $setting->fresh()->data]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function contentRules(): array
    {
        return [
            'content.section' => ['nullable', 'string', 'max:120'],
            'content.title' => ['nullable', 'string', 'max:200'],
            'content.intro' => ['nullable', 'string', 'max:2000'],
            'content.image' => ['nullable', 'url:http,https', 'max:2048'],
            'content.imageAlt' => ['nullable', 'string', 'max:255'],
            'content.caption' => ['nullable', 'string', 'max:255'],
            'content.note' => ['nullable', 'string', 'max:255'],
            'content.number' => ['nullable', 'string', 'max:20'],
            'content.layout' => ['nullable', Rule::in(['image', 'list', 'contact'])],
            'content.email' => ['nullable', 'email', 'max:254'],
            'content.location' => ['nullable', 'string', 'max:255'],
            'content.signature' => ['nullable', 'string', 'max:100'],
            'content.items' => ['nullable', 'array', 'max:12'],
            'content.items.*.kicker' => ['nullable', 'string', 'max:100'],
            'content.items.*.title' => ['required_with:content.items', 'string', 'max:160'],
            'content.items.*.description' => ['nullable', 'string', 'max:300'],
        ];
    }
}
