<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\PageVisit;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SiteMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_seo_tags_and_analytics_from_settings(): void
    {
        SiteSetting::saveGroup('seo', [
            'meta_title' => 'Noor Rahman — Photographer',
            'meta_description' => 'Portraits and landscapes.',
            'og_image' => 'https://example.com/share.jpg',
            'allow_indexing' => false,
        ]);
        SiteSetting::saveGroup('analytics', ['ga4_measurement_id' => 'G-ABC1234']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Noor Rahman — Photographer</title>', false)
            ->assertSee('<meta name="description" content="Portraits and landscapes.">', false)
            ->assertSee('<meta property="og:image" content="https://example.com/share.jpg">', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('gtag/js?id=G-ABC1234', false);

        $this->assertSame(1, PageVisit::query()->sum('views'));
    }

    public function test_sitemap_and_robots_follow_the_indexing_setting(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('<loc>'.url('/').'</loc>', false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'));

        SiteSetting::saveGroup('seo', ['allow_indexing' => false]);

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /')->assertDontSee('Sitemap:');
    }

    public function test_visitor_can_send_a_contact_message_that_is_emailed_to_the_owner(): void
    {
        Mail::fake();
        SiteSetting::saveGroup('contact', ['recipient_email' => 'owner@example.com']);

        $this->postJson('/contact', [
            'name' => 'Ada Visitor',
            'email' => 'ada@example.com',
            'subject' => 'Assignment',
            'message' => 'Would you shoot our wedding?',
        ])->assertCreated();

        $this->assertDatabaseHas('contact_messages', ['email' => 'ada@example.com', 'read_at' => null]);
        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('owner@example.com')
            && $mail->hasReplyTo('ada@example.com'));
    }

    public function test_contact_form_rejects_bots_and_can_be_switched_off(): void
    {
        $this->postJson('/contact', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'Buy things now',
            'website' => 'https://spam.example',
        ])->assertUnprocessable()->assertJsonValidationErrors('website');

        SiteSetting::saveGroup('contact', ['form_enabled' => false]);

        $this->postJson('/contact', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'message' => 'Hello there',
        ])->assertNotFound();

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_admin_can_save_each_settings_group_and_the_smtp_password_stays_encrypted(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->putJson('/admin/api/site-settings/seo', ['settings' => [
            'meta_title' => 'My site',
            'twitter_handle' => 'noor',
            'allow_indexing' => true,
        ]])->assertOk()->assertJsonPath('settings.seo.twitter_handle', '@noor');

        $this->putJson('/admin/api/site-settings/analytics', ['settings' => [
            'ga4_measurement_id' => '<script>',
            'track_visits' => true,
        ]])->assertUnprocessable()->assertJsonValidationErrors('settings.ga4_measurement_id');

        $this->putJson('/admin/api/site-settings/mail', ['settings' => [
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'mailer',
            'password' => 'secret-pass',
            'encryption' => 'tls',
            'from_address' => 'studio@example.com',
        ]])->assertOk()
            ->assertJsonPath('settings.mail.password', '')
            ->assertJsonPath('settings.mail.has_password', true);

        $stored = SiteSetting::valuesFor('mail')['password'];
        $this->assertNotSame('secret-pass', $stored);
        $this->assertSame('secret-pass', SiteMailer::decryptPassword($stored));

        $this->putJson('/admin/api/site-settings/mail', ['settings' => [
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 465,
            'password' => '',
            'encryption' => 'ssl',
            'from_address' => 'studio@example.com',
        ]])->assertOk();

        $this->assertSame('secret-pass', SiteMailer::decryptPassword(SiteSetting::valuesFor('mail')['password']));

        $this->putJson('/admin/api/site-settings/unknown', ['settings' => []])->assertNotFound();
    }

    public function test_admin_can_read_mark_and_delete_contact_messages(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $message = ContactMessage::factory()->create();
        ContactMessage::factory()->read()->create();

        $this->getJson('/admin/api/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.messages', 2)
            ->assertJsonPath('stats.unreadMessages', 1)
            ->assertJsonCount(30, 'visits');

        $this->getJson('/admin/api/messages')->assertOk()->assertJsonCount(2, 'messages');

        $this->patchJson("/admin/api/messages/{$message->id}", ['isRead' => true])
            ->assertOk()
            ->assertJsonPath('message.isRead', true);

        $this->deleteJson("/admin/api/messages/{$message->id}")->assertOk();
        $this->assertModelMissing($message);
    }

    public function test_admin_can_upload_an_image_but_not_other_files(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $url = $this->post('/admin/api/media', [
            'image' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
        ], ['Accept' => 'application/json'])->assertCreated()->json('url');

        $this->assertStringContainsString('/storage/uploads/', $url);
        $this->assertCount(1, Storage::disk('public')->allFiles('uploads'));

        $this->post('/admin/api/media', [
            'image' => UploadedFile::fake()->create('notes.php', 10, 'text/plain'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_site_admin_endpoints_require_an_admin(): void
    {
        $this->getJson('/admin/api/dashboard')->assertUnauthorized();
        $this->getJson('/admin/api/messages')->assertUnauthorized();
        $this->putJson('/admin/api/site-settings/seo', ['settings' => []])->assertUnauthorized();

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->getJson('/admin/api/site-settings')
            ->assertForbidden();
    }
}
