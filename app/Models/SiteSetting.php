<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group', 'data'])]
class SiteSetting extends Model
{
    /**
     * Default values for every settings group, used until the admin saves their own.
     *
     * @var array<string, array<string, mixed>>
     */
    public const DEFAULTS = [
        'seo' => [
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
            'og_image' => '',
            'canonical_url' => '',
            'twitter_handle' => '',
            'allow_indexing' => true,
            'google_site_verification' => '',
            'bing_site_verification' => '',
        ],
        'analytics' => [
            'ga4_measurement_id' => '',
            'gtm_container_id' => '',
            'track_visits' => true,
        ],
        'mail' => [
            'mailer' => 'log',
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'from_address' => '',
            'from_name' => '',
        ],
        'contact' => [
            'form_enabled' => true,
            'recipient_email' => '',
            'success_message' => 'Thank you — your message is on its way. I will reply soon.',
            'instagram' => '',
            'facebook' => '',
            'x' => '',
            'linkedin' => '',
            'youtube' => '',
            'behance' => '',
            'website' => '',
        ],
    ];

    /**
     * Social profile keys shown on the contact page and in structured data.
     *
     * @var array<string, string>
     */
    public const SOCIAL_LABELS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'x' => 'X',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'behance' => 'Behance',
        'website' => 'Website',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function valuesFor(string $group): array
    {
        $stored = static::query()->where('group', $group)->value('data');

        return array_merge(self::DEFAULTS[$group] ?? [], $stored ?? []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function saveGroup(string $group, array $data): array
    {
        $values = array_merge(static::valuesFor($group), $data);

        static::query()->updateOrCreate(['group' => $group], ['data' => $values]);

        return $values;
    }

    /**
     * @return array<int, array{key: string, label: string, url: string}>
     */
    public static function socialLinks(): array
    {
        $contact = static::valuesFor('contact');

        return collect(self::SOCIAL_LABELS)
            ->filter(fn (string $label, string $key) => filled($contact[$key] ?? null))
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label, 'url' => $contact[$key]])
            ->values()
            ->all();
    }
}
