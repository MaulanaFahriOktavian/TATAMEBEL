<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\UploadMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Models\ProductionUpdate;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    /**
     * Upload photo evidence attached to an order.
     */
    public function store(UploadMediaRequest $request, int $orderId): JsonResponse
    {
        Gate::authorize('upload', Media::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $update = null;
        if ($request->filled('production_update_id')) {
            $update = ProductionUpdate::where('id', $request->input('production_update_id'))
                ->where('workshop_id', $request->user()->workshop_id)
                ->first();
        }

        $media = $this->mediaService->upload(
            $order,
            $request->file('file'),
            $request->validated(),
            $request->user(),
            $update
        );

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully.',
            'data' => new MediaResource($media->load('uploader')),
        ], 201);
    }

    /**
     * List all media evidence for an order.
     */
    public function index(Request $request, int $orderId): JsonResponse
    {
        Gate::authorize('viewAny', Media::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $query = $order->media()->with('uploader')->latest('id');

        if ($request->has('visibility')) {
            $query->where('visibility', $request->query('visibility'));
        }

        $mediaList = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Media retrieved successfully.',
            'data' => MediaResource::collection($mediaList),
        ], 200);
    }

    /**
     * Delete a media file.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $media = Media::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found.',
            ], 404);
        }

        Gate::authorize('delete', $media);

        $this->mediaService->delete($media, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully.',
            'data' => null,
        ], 200);
    }
}
