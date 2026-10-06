<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Support\SiteMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(Request $request, SiteMailer $mailer): JsonResponse
    {
        $contact = SiteSetting::valuesFor('contact');
        abort_unless($contact['form_enabled'], 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'website' => ['prohibited'],
        ], [
            'website.prohibited' => 'Your message could not be sent.',
        ]);

        $message = ContactMessage::query()->create([
            ...collect($validated)->only(['name', 'email', 'subject', 'message'])->all(),
            'ip_address' => $request->ip(),
        ]);

        $recipient = $mailer->recipient();

        if ($recipient) {
            try {
                $mailer->configure();
                Mail::to($recipient)->send(new ContactMessageReceived($message));
            } catch (Throwable $exception) {
                Log::warning('Contact message saved but the notification email failed.', [
                    'contact_message_id' => $message->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json(['message' => $contact['success_message']], 201);
    }
}
