<?php

namespace App\Http\Controllers;

use App\Models\BookPage;
use App\Models\BookSetting;
use App\Models\ContactMessage;
use App\Models\PageVisit;
use App\Models\SiteSetting;
use App\Support\SiteMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class AdminSiteController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $pages = BookPage::query()->get(['kind', 'content']);
        $since = now()->subDays(29)->startOfDay();
        $visitsByDay = PageVisit::query()
            ->where('visited_on', '>=', $since->toDateString())
            ->pluck('views', 'visited_on')
            ->mapWithKeys(fn (int $views, string $day) => [substr($day, 0, 10) => $views]);

        $visits = collect(range(29, 0))->map(function (int $daysAgo) use ($visitsByDay) {
            $day = now()->subDays($daysAgo)->toDateString();

            return ['date' => $day, 'views' => $visitsByDay[$day] ?? 0];
        })->values();

        $seo = SiteSetting::valuesFor('seo');
        $mail = SiteSetting::valuesFor('mail');
        $contact = SiteSetting::valuesFor('contact');
        $analytics = SiteSetting::valuesFor('analytics');
        $book = BookSetting::query()->find(1)?->data ?? [];

        return response()->json([
            'stats' => [
                'pages' => $pages->count(),
                'chapters' => $pages->where('kind', 'chapter')->count(),
                'photos' => $pages->sum(fn (BookPage $page) => count($page->content['photos'] ?? []) + (filled($page->content['image'] ?? null) ? 1 : 0)),
                'messages' => ContactMessage::query()->count(),
                'unreadMessages' => ContactMessage::query()->whereNull('read_at')->count(),
                'visitsToday' => $visits->last()['views'],
                'visitsMonth' => $visits->sum('views'),
                'visitsTotal' => (int) PageVisit::query()->sum('views'),
            ],
            'visits' => $visits,
            'recentMessages' => ContactMessage::query()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (ContactMessage $message) => $message->toAdminData()),
            'checklist' => [
                ['key' => 'description', 'label' => 'Write a search description', 'section' => 'seo', 'done' => filled($seo['meta_description']) || filled($book['site_description'] ?? null)],
                ['key' => 'share-image', 'label' => 'Choose a social share image', 'section' => 'seo', 'done' => filled($seo['og_image'])],
                ['key' => 'analytics', 'label' => 'Connect Google Analytics', 'section' => 'analytics', 'done' => filled($analytics['ga4_measurement_id']) || filled($analytics['gtm_container_id'])],
                ['key' => 'smtp', 'label' => 'Set up SMTP email delivery', 'section' => 'mail', 'done' => $mail['mailer'] === 'smtp' && filled($mail['host'])],
                ['key' => 'recipient', 'label' => 'Choose where contact messages go', 'section' => 'contact', 'done' => filled($contact['recipient_email'])],
                ['key' => 'socials', 'label' => 'Add your social profiles', 'section' => 'contact', 'done' => SiteSetting::socialLinks() !== []],
            ],
            'links' => [
                'site' => url('/'),
                'sitemap' => route('sitemap'),
                'robots' => route('robots'),
            ],
        ]);
    }

    public function settings(): JsonResponse
    {
        return response()->json(['settings' => $this->allSettings()]);
    }

    public function update(Request $request, string $group): JsonResponse
    {
        abort_unless(array_key_exists($group, SiteSetting::DEFAULTS), 404);

        $validated = $request->validate($this->rulesFor($group));
        $values = collect($validated['settings'])
            ->only(array_keys(SiteSetting::DEFAULTS[$group]))
            ->all();

        if ($group === 'mail') {
            $values['password'] = filled($values['password'] ?? null)
                ? SiteMailer::encryptPassword($values['password'])
                : SiteSetting::valuesFor('mail')['password'];
        }

        if ($group === 'seo' && filled($values['twitter_handle'] ?? null)) {
            $values['twitter_handle'] = '@'.ltrim($values['twitter_handle'], '@');
        }

        SiteSetting::saveGroup($group, collect($values)->map(fn ($value) => $value ?? '')->all());

        return response()->json(['settings' => $this->allSettings()]);
    }

    public function testMail(Request $request, SiteMailer $mailer): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:254'],
        ]);

        $recipient = $validated['email'] ?? $mailer->recipient() ?? $request->user()->email;

        try {
            $mailer->configure();
            Mail::raw(
                "This is a test email from your website.\n\nIf you can read this, SMTP is set up correctly.",
                fn ($message) => $message->to($recipient)->subject('Test email from your studio'),
            );
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'The test email could not be sent: '.Str::limit($exception->getMessage(), 240),
            ], 422);
        }

        return response()->json(['message' => "Test email sent to {$recipient}."]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
        ]);

        $path = $request->file('image')->store('uploads/'.now()->format('Y/m'), 'public');

        return response()->json(['url' => asset('storage/'.$path)], 201);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function allSettings(): array
    {
        $settings = collect(SiteSetting::DEFAULTS)
            ->keys()
            ->mapWithKeys(fn (string $group) => [$group => SiteSetting::valuesFor($group)])
            ->all();

        $settings['mail']['has_password'] = filled($settings['mail']['password']);
        $settings['mail']['password'] = '';

        return $settings;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesFor(string $group): array
    {
        $rules = match ($group) {
            'seo' => [
                'meta_title' => ['nullable', 'string', 'max:70'],
                'meta_description' => ['nullable', 'string', 'max:300'],
                'meta_keywords' => ['nullable', 'string', 'max:255'],
                'og_image' => ['nullable', 'url:http,https', 'max:2048'],
                'canonical_url' => ['nullable', 'url:http,https', 'max:255'],
                'twitter_handle' => ['nullable', 'string', 'max:30', 'regex:/^@?[A-Za-z0-9_]+$/'],
                'allow_indexing' => ['required', 'boolean'],
                'google_site_verification' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-]+$/'],
                'bing_site_verification' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-]+$/'],
            ],
            'analytics' => [
                'ga4_measurement_id' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{4,20}$/'],
                'gtm_container_id' => ['nullable', 'string', 'regex:/^GTM-[A-Z0-9]{4,20}$/'],
                'track_visits' => ['required', 'boolean'],
            ],
            'mail' => [
                'mailer' => ['required', Rule::in(['log', 'smtp'])],
                'host' => ['nullable', 'required_if:settings.mailer,smtp', 'string', 'max:255'],
                'port' => ['nullable', 'required_if:settings.mailer,smtp', 'integer', 'between:1,65535'],
                'username' => ['nullable', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:255'],
                'encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
                'from_address' => ['nullable', 'required_if:settings.mailer,smtp', 'email', 'max:254'],
                'from_name' => ['nullable', 'string', 'max:100'],
            ],
            'contact' => [
                'form_enabled' => ['required', 'boolean'],
                'recipient_email' => ['nullable', 'email', 'max:254'],
                'success_message' => ['required', 'string', 'max:200'],
                ...collect(SiteSetting::SOCIAL_LABELS)
                    ->map(fn () => ['nullable', 'url:http,https', 'max:255'])
                    ->all(),
            ],
        };

        return [
            'settings' => ['required', 'array'],
            ...collect($rules)->mapWithKeys(fn (array $fieldRules, string $key) => ["settings.{$key}" => $fieldRules])->all(),
        ];
    }
}
