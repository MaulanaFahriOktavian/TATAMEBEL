<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    /**
     * Submit custom furniture project inquiry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:50',
            'project_type' => 'required|string|max:100',
            'timeline' => 'required|string|max:100',
            'description' => 'required|string|max:5000',
        ]);

        $inquiry = CustomInquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'project_type' => $validated['project_type'],
            'timeline' => $validated['timeline'],
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan proyek Anda telah kami terima. Tim atelier TATAMEBEL akan meninjau denah serta menghubungi Anda.',
            'data' => [
                'id' => $inquiry->id,
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'project_type' => $inquiry->project_type,
                'created_at' => $inquiry->created_at,
            ],
        ], 201);
    }

    /**
     * List inquiries (staff / admin).
     */
    public function index(): JsonResponse
    {
        $inquiries = CustomInquiry::orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Inquiries retrieved.',
            'data' => $inquiries,
        ]);
    }
}
