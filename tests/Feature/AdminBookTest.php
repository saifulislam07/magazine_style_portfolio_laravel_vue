<?php

namespace Tests\Feature;

use App\Models\BookPage;
use App\Models\BookSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_book_data_is_read_from_the_database(): void
    {
        BookSetting::query()->create([
            'id' => 1,
            'data' => ['brand' => 'My Journal'],
        ]);
        BookPage::query()->create([
            'slug' => 'cover',
            'kind' => 'cover',
            'label' => 'Cover',
            'position' => 0,
            'content' => ['image' => 'https://example.com/cover.jpg'],
        ]);

        $this->getJson('/book-data')
            ->assertOk()
            ->assertJsonPath('settings.brand', 'My Journal')
            ->assertJsonPath('pages.0.id', 'cover')
            ->assertJsonPath('pages.0.image', 'https://example.com/cover.jpg');
    }

    public function test_admin_api_requires_an_admin_session(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->getJson('/admin/api/data')->assertUnauthorized();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_non_admin_users_cannot_access_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_create_edit_reorder_and_delete_chapters(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->createPage('cover', 'cover', 'Cover', 0);
        $this->createPage('contents', 'contents', 'Contents', 1);
        $this->createPage('existing', 'chapter', 'Existing', 2);
        $this->createPage('colophon', 'colophon', 'Closing note', 3);
        $this->createPage('back-cover', 'back-cover', 'Back cover', 4);

        $created = $this->postJson('/admin/api/pages', [
            'label' => 'New chapter',
            'content' => [
                'section' => 'NEW STORY',
                'title' => "A quiet day.\nOutside.",
                'intro' => 'A new photo story.',
                'layout' => 'image',
                'items' => [],
            ],
        ])->assertCreated()
            ->assertJsonPath('page.kind', 'chapter')
            ->assertJsonPath('page.content.title', "A quiet day.\nOutside.");

        $slug = $created->json('page.id');

        $this->putJson("/admin/api/pages/{$slug}", [
            'label' => 'Revised chapter',
            'content' => [
                'section' => 'REVISED',
                'title' => 'A different title.',
                'intro' => 'Updated story.',
                'layout' => 'image',
            ],
        ])->assertOk()->assertJsonPath('page.label', 'Revised chapter');

        $this->putJson('/admin/api/pages/reorder', [
            'ids' => [$slug, 'existing'],
        ])->assertOk()
            ->assertJsonPath('pages.2.id', $slug)
            ->assertJsonPath('pages.3.id', 'existing')
            ->assertJsonPath('pages.4.id', 'colophon')
            ->assertJsonPath('pages.5.id', 'back-cover');

        $this->deleteJson('/admin/api/pages/cover')->assertUnprocessable();

        $this->deleteJson("/admin/api/pages/{$slug}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('book_pages', ['slug' => $slug]);
    }

    public function test_admin_can_create_a_gallery_page_with_up_to_sixty_photos(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->createPage('colophon', 'colophon', 'Closing note', 0);

        $photo = ['image' => 'https://example.com/photo.jpg', 'alt' => 'A lake', 'caption' => 'Morning'];

        $this->postJson('/admin/api/pages', [
            'label' => 'Gallery',
            'content' => ['layout' => 'gallery', 'photos' => array_fill(0, 61, $photo)],
        ])->assertUnprocessable()->assertJsonValidationErrors('content.photos');

        $this->postJson('/admin/api/pages', [
            'label' => 'Gallery',
            'content' => ['layout' => 'gallery', 'photos' => [['image' => 'not-a-url']]],
        ])->assertUnprocessable()->assertJsonValidationErrors('content.photos.0.image');

        $this->postJson('/admin/api/pages', [
            'label' => 'Gallery',
            'content' => ['layout' => 'gallery', 'photos' => array_fill(0, 12, $photo)],
        ])->assertCreated()
            ->assertJsonPath('page.content.layout', 'gallery')
            ->assertJsonCount(12, 'page.content.photos');

        $this->getJson('/book-data')
            ->assertOk()
            ->assertJsonPath('pages.0.photos.0.caption', 'Morning');
    }

    public function test_admin_login_only_creates_a_session_for_an_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'reader@example.com',
            'password' => 'correct-horse-battery',
            'is_admin' => false,
        ]);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'correct-horse-battery',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $admin = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'correct-horse-battery',
            'is_admin' => true,
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_update_publication_settings(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $settings = [
            'brand' => 'North Light',
            'author' => 'A. Photographer',
            'photography_label' => 'PHOTOGRAPHY',
            'cover_issue' => 'NO. 02 · 2026',
            'cover_kicker' => 'A PHOTOGRAPHIC JOURNAL',
            'cover_title' => "Light and\nlandscape.",
            'cover_strapline' => 'PEOPLE · PLACES · STORIES',
            'cover_open_text' => 'OPEN THE BOOK →',
            'back_cover_text' => "THANK YOU\nFOR READING.",
            'back_cover_year' => '2026',
            'contact_kicker' => 'SAY HELLO',
            'return_to_cover_text' => 'BACK TO THE COVER',
            'site_description' => 'A collection of photographs and stories.',
        ];

        $this->putJson('/admin/api/settings', ['settings' => $settings])
            ->assertOk()
            ->assertJsonPath('settings.brand', 'North Light');

        $this->getJson('/book-data')
            ->assertOk()
            ->assertJsonPath('settings.cover_title', "Light and\nlandscape.")
            ->assertJsonPath('settings.site_description', 'A collection of photographs and stories.');
    }

    private function createPage(string $slug, string $kind, string $label, int $position): BookPage
    {
        return BookPage::query()->create([
            'slug' => $slug,
            'kind' => $kind,
            'label' => $label,
            'position' => $position,
            'content' => [],
        ]);
    }
}
