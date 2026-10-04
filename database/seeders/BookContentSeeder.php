<?php

namespace Database\Seeders;

use App\Models\BookPage;
use App\Models\BookSetting;
use Illuminate\Database\Seeder;

class BookContentSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSettings = [
            'brand' => 'FIELDNOTES',
            'author' => 'NOOR RAHMAN',
            'photography_label' => 'PHOTOGRAPHY',
            'cover_issue' => 'NO. 01 · 2025',
            'cover_kicker' => 'A PHOTOGRAPHIC JOURNAL',
            'cover_title' => "Stories in\nstillness.",
            'cover_strapline' => 'PEOPLE · PLACES · THE IN-BETWEEN',
            'cover_open_text' => 'OPEN THE COVER →',
            'back_cover_text' => "THANK YOU FOR\nTURNING THE PAGES.",
            'back_cover_year' => '2025',
            'contact_kicker' => 'GOOD THINGS START WITH A HELLO',
            'return_to_cover_text' => 'RETURN TO THE COVER',
            'site_description' => 'A photographic journal by Noor Rahman. Stories of people, place, and the in-between.',
        ];
        $setting = BookSetting::query()->find(1);

        if (! $setting) {
            BookSetting::query()->create(['id' => 1, 'data' => $defaultSettings]);
        } else {
            $setting->data = array_merge($defaultSettings, $setting->data ?? []);
            $setting->save();
        }

        $pages = [
            [
                'slug' => 'cover',
                'kind' => 'cover',
                'label' => 'Cover',
                'position' => 0,
                'content' => [
                    'image' => 'https://images.unsplash.com/photo-1470770841072-f978cf4d019e?auto=format&fit=crop&w=1500&q=85',
                    'imageAlt' => 'A quiet alpine lake and mountains on the Fieldnotes book cover',
                ],
            ],
            [
                'slug' => 'contents',
                'kind' => 'contents',
                'label' => 'Contents',
                'position' => 1,
                'content' => [
                    'section' => 'THE FIRST PAGES',
                    'title' => 'Contents.',
                    'intro' => 'A field guide to the stories, milestones, and people found along the way.',
                ],
            ],
            [
                'slug' => 'about',
                'kind' => 'chapter',
                'label' => 'About',
                'position' => 2,
                'content' => [
                    'section' => 'ABOUT THE PHOTOGRAPHER',
                    'title' => "A life seen\nthrough light.",
                    'intro' => 'I’m Noor Rahman, a photographer drawn to quiet places, honest portraits, and the stories hidden in ordinary days.',
                    'image' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=1200&q=85',
                    'imageAlt' => 'Portrait in soft natural light',
                    'caption' => 'Behind the camera, somewhere new',
                    'note' => 'A LITTLE ABOUT ME',
                    'number' => '01',
                    'layout' => 'image',
                ],
            ],
            [
                'slug' => 'story-01',
                'kind' => 'chapter',
                'label' => 'Story 01',
                'position' => 3,
                'content' => [
                    'section' => 'STORY 01 / THE QUIET WILD',
                    'title' => "Where the\nworld grows quiet.",
                    'intro' => 'We walked until the trail disappeared. The mountains did what they always do: made everything else feel beautifully small.',
                    'image' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1500&q=85',
                    'imageAlt' => 'Mountain peaks disappearing into a hazy sky',
                    'caption' => 'Alpine air, and nowhere else to be',
                    'note' => 'DOLOMITES, ITALY',
                    'number' => '02',
                    'layout' => 'image',
                ],
            ],
            [
                'slug' => 'story-02',
                'kind' => 'chapter',
                'label' => 'Story 02',
                'position' => 4,
                'content' => [
                    'section' => 'STORY 02 / ORDINARY MAGIC',
                    'title' => "A softer kind\nof morning.",
                    'intro' => 'The kettle clicks. The curtains breathe. For a little while, the whole day is still only a possibility.',
                    'image' => 'https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=1500&q=85',
                    'imageAlt' => 'Sunlight falling across a quiet neighbourhood cafe',
                    'caption' => 'A table by the window, Copenhagen',
                    'note' => 'COPENHAGEN, DENMARK',
                    'number' => '03',
                    'layout' => 'image',
                ],
            ],
            [
                'slug' => 'story-03',
                'kind' => 'chapter',
                'label' => 'Story 03',
                'position' => 5,
                'content' => [
                    'section' => 'STORY 03 / THE PEOPLE WE FIND',
                    'title' => "The warmth\nbetween us.",
                    'intro' => 'Some strangers make a place familiar. We shared tea, stories, and an afternoon that refused to hurry.',
                    'image' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=1500&q=85',
                    'imageAlt' => 'Portrait of a woman in warm afternoon light',
                    'caption' => 'A face I will remember, Marrakesh',
                    'note' => 'MARRAKESH, MOROCCO',
                    'number' => '04',
                    'layout' => 'image',
                ],
            ],
            [
                'slug' => 'story-04',
                'kind' => 'chapter',
                'label' => 'Story 04',
                'position' => 6,
                'content' => [
                    'section' => 'STORY 04 / A LITTLE FURTHER',
                    'title' => "Take the long\nway home.",
                    'intro' => 'There is always another road, another blue hour, another reason to keep the camera close.',
                    'image' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1500&q=85',
                    'imageAlt' => 'Golden sunlight falling across a winding country road',
                    'caption' => 'The scenic route, always',
                    'note' => 'FIELD NOTES, NO. 05',
                    'number' => '05',
                    'layout' => 'image',
                ],
            ],
            [
                'slug' => 'achievements',
                'kind' => 'chapter',
                'label' => 'Achievements',
                'position' => 7,
                'content' => [
                    'section' => 'MILESTONES & RECOGNITION',
                    'title' => 'A few milestones.',
                    'intro' => 'A place to celebrate the exhibitions, awards, and moments that have shaped your practice.',
                    'note' => 'SELECTED HIGHLIGHTS',
                    'number' => '06',
                    'layout' => 'list',
                    'items' => [
                        ['kicker' => 'AWARD / YEAR', 'title' => 'Add your award', 'description' => 'Award name · Category · Year'],
                        ['kicker' => 'EXHIBITION / YEAR', 'title' => 'Add an exhibition', 'description' => 'Exhibition title · Gallery · City'],
                        ['kicker' => 'RECOGNITION / YEAR', 'title' => 'Add a feature or shortlist', 'description' => 'Organization · Project · Year'],
                    ],
                ],
            ],
            [
                'slug' => 'publications',
                'kind' => 'chapter',
                'label' => 'Publications',
                'position' => 8,
                'content' => [
                    'section' => 'IN PRINT & ONLINE',
                    'title' => "Stories that\ntravel.",
                    'intro' => 'Selected features, essays, and photo stories published in print and online.',
                    'note' => 'SELECTED PUBLICATIONS',
                    'number' => '07',
                    'layout' => 'list',
                    'items' => [
                        ['kicker' => 'MAGAZINE / YEAR', 'title' => 'Add a magazine feature', 'description' => 'Publication · Story title · Issue'],
                        ['kicker' => 'JOURNAL / YEAR', 'title' => 'Add a photo essay', 'description' => 'Journal or platform · Essay title'],
                        ['kicker' => 'BOOK / YEAR', 'title' => 'Add a book or catalogue', 'description' => 'Book title · Publisher · Role'],
                    ],
                ],
            ],
            [
                'slug' => 'contact',
                'kind' => 'chapter',
                'label' => 'Contact',
                'position' => 9,
                'content' => [
                    'section' => 'START A CONVERSATION',
                    'title' => "Have a story\nin mind?",
                    'intro' => 'For assignments, collaborations, print enquiries, or simply to say hello.',
                    'note' => 'MY INBOX IS OPEN',
                    'number' => '08',
                    'layout' => 'contact',
                    'email' => 'hello@yourdomain.com',
                    'location' => 'Available for projects worldwide',
                ],
            ],
            [
                'slug' => 'colophon',
                'kind' => 'colophon',
                'label' => 'A note before you go',
                'position' => 10,
                'content' => [
                    'section' => 'A NOTE BEFORE YOU GO',
                    'title' => "Keep looking\nclosely.",
                    'intro' => 'The best stories are often the ones we almost walk past. Thank you for taking the time to see them with me.',
                    'signature' => 'Noor Rahman',
                ],
            ],
            [
                'slug' => 'back-cover',
                'kind' => 'back-cover',
                'label' => 'Back cover',
                'position' => 11,
                'content' => [],
            ],
        ];

        foreach ($pages as $page) {
            BookPage::query()->firstOrCreate(
                ['slug' => $page['slug']],
                $page,
            );
        }
    }
}
