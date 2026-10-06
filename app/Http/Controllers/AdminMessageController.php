<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMessageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'messages' => ContactMessage::query()
                ->latest()
                ->limit(200)
                ->get()
                ->map(fn (ContactMessage $message) => $message->toAdminData()),
        ]);
    }

    public function update(Request $request, ContactMessage $message): JsonResponse
    {
        $validated = $request->validate([
            'isRead' => ['required', 'boolean'],
        ]);

        $message->update(['read_at' => $validated['isRead'] ? ($message->read_at ?? now()) : null]);

        return response()->json(['message' => $message->fresh()->toAdminData()]);
    }

    public function destroy(ContactMessage $message): JsonResponse
    {
        $message->delete();

        return response()->json(['deleted' => true]);
    }
}
