<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\Service;
use App\Models\ServiceAddress;
use App\Models\ServiceType;
use App\Models\ServiceTypeLink;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProviderServiceApiController extends Controller
{
    private const MAX_IMAGE_KB = 5120; // 5 MB
    private const MAX_VIDEO_KB = 51200; // 50 MB

    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const ALLOWED_VIDEO_MIME_TYPES = [
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',
        'video/mpeg',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }

        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $services = Service::query()
            ->where('user_id', (int) $user->id)
            ->orderByDesc('id')
            ->paginate($perPage);

        $items = collect($services->items());
        $serviceIds = $items->pluck('id')->filter()->map(fn ($id) => (int) $id)->values()->all();

        $addresses = empty($serviceIds)
            ? []
            : ServiceAddress::query()->whereIn('service_id', $serviceIds)->get()->keyBy('service_id')->all();
        $covers = empty($serviceIds)
            ? []
            : CoverImage::query()->whereIn('service_id', $serviceIds)->get()->keyBy('service_id')->all();
        $videos = empty($serviceIds)
            ? []
            : Video::query()->whereIn('service_id', $serviceIds)->get()->keyBy('service_id')->all();
        $serviceTypeLinks = empty($serviceIds)
            ? []
            : ServiceTypeLink::query()->whereIn('service_id', $serviceIds)->get()->groupBy('service_id')->all();
        $moreImages = empty($serviceIds)
            ? []
            : MoreImage::query()->whereIn('service_id', $serviceIds)->get()->groupBy('service_id')->all();

        $typeIds = collect($serviceTypeLinks)
            ->flatten(1)
            ->pluck('service_type_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $typeMap = empty($typeIds) ? [] : ServiceType::query()->whereIn('id', $typeIds)->pluck('name', 'id')->all();

        $data = $items->map(function (Service $service) use ($addresses, $covers, $videos, $serviceTypeLinks, $moreImages, $typeMap) {
            return $this->formatService(
                $service,
                $addresses[(int) $service->id] ?? null,
                $covers[(int) $service->id] ?? null,
                $videos[(int) $service->id] ?? null,
                collect($serviceTypeLinks[(int) $service->id] ?? [])->all(),
                collect($moreImages[(int) $service->id] ?? [])->all(),
                $typeMap
            );
        })->values()->all();

        return $this->successResponse($data, [
            'current_page' => $services->currentPage(),
            'total' => $services->total(),
            'per_page' => $services->perPage(),
            'next_page' => $services->nextPageUrl(),
            'prev_page' => $services->previousPageUrl(),
        ]);
    }

    public function serviceTypes(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }

        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        $types = ServiceType::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ServiceType $type) => [
                'id' => (int) $type->id,
                'name' => $type->name,
            ])
            ->values()
            ->all();

        return $this->successResponse($types);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }

        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        $validator = Validator::make($request->all(), [
            'availability' => 'required|string|max:100',
            'description' => 'required|string',
            'title' => 'nullable|string|max:255',
            'page_url' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'service_type' => 'required|array|min:1',
            'service_type.*' => 'integer|exists:service_type,id',
            'cover_image' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_IMAGE_KB,
            'more_images' => 'nullable|array',
            'more_images.*' => 'file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_IMAGE_KB,
            'video' => 'nullable|file|mimes:mp4,mov,avi,mpeg,mpg|max:' . self::MAX_VIDEO_KB,
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'province' => 'nullable|string|max:120',
            'postal_code' => 'nullable|string|max:30',
            'country' => 'nullable|string|max:120',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos invalidos', 422, $validator->errors()->toArray());
        }

        $imagePath = public_path('img/uploads');
        $videoPath = public_path('video/uploads');
        if (! is_dir($imagePath)) {
            @mkdir($imagePath, 0755, true);
        }
        if (! is_dir($videoPath)) {
            @mkdir($videoPath, 0755, true);
        }

        $storedCover = $this->storeUploadedImage($request->file('cover_image'), $imagePath);
        if (! $storedCover['success']) {
            return $this->errorResponse($storedCover['error'], 422);
        }

        $service = Service::query()->create([
            'title' => trim((string) $request->input('title', '')) ?: null,
            'description' => (string) $request->input('description'),
            'availability' => (string) $request->input('availability'),
            'document_number' => trim((string) $request->input('document_number', $user->document_number ?? '')) ?: null,
            'page_url' => trim((string) $request->input('page_url', '')) ?: null,
            'user_id' => (int) $user->id,
        ]);

        CoverImage::query()->create([
            'url' => $storedCover['file_name'],
            'service_id' => (int) $service->id,
        ]);

        foreach ((array) $request->input('service_type', []) as $serviceTypeId) {
            ServiceTypeLink::query()->create([
                'service_id' => (int) $service->id,
                'service_type_id' => (int) $serviceTypeId,
            ]);
        }

        $providerAddress = UserAddress::query()->where('user_id', (int) $user->id)->first();
        $addressPayload = [
            'address' => $this->nullableString($request->input('address', $providerAddress?->address)),
            'city' => $this->nullableString($request->input('city', $providerAddress?->city)),
            'province' => $this->nullableString($request->input('province', $providerAddress?->province)),
            'postal_code' => $this->nullableString($request->input('postal_code', $providerAddress?->postal_code)),
            'country' => $this->nullableString($request->input('country', $providerAddress?->country)),
            'latitude' => $this->nullableString($request->input('latitude', $providerAddress?->latitude)),
            'longitude' => $this->nullableString($request->input('longitude', $providerAddress?->longitude)),
        ];

        if (collect($addressPayload)->contains(fn ($v) => $v !== null && $v !== '')) {
            ServiceAddress::query()->create(array_merge(['service_id' => (int) $service->id], $addressPayload));
        }

        $this->persistMoreImages($request, (int) $service->id, $imagePath);
        $this->persistVideo($request, (int) $service->id, $videoPath);

        return $this->successResponse(
            $this->loadServicePayload((int) $service->id, (int) $user->id),
            null,
            'Servicio creado',
            201
        );
    }

    public function show(Request $request, int $id)
    {
        $service = $this->resolveOwnedService($request, $id);
        if (! $service instanceof Service) {
            return $service;
        }

        return $this->successResponse($this->loadServicePayload((int) $service->id, (int) $service->user_id));
    }

    public function update(Request $request, int $id)
    {
        $service = $this->resolveOwnedService($request, $id);
        if (! $service instanceof Service) {
            return $service;
        }

        $validator = Validator::make($request->all(), [
            'availability' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'title' => 'sometimes|nullable|string|max:255',
            'page_url' => 'sometimes|nullable|string|max:255',
            'document_number' => 'sometimes|nullable|string|max:100',
            'service_type' => 'sometimes|array|min:1',
            'service_type.*' => 'integer|exists:service_type,id',
            'cover_image' => 'sometimes|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_IMAGE_KB,
            'more_images' => 'nullable|array',
            'more_images.*' => 'file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_IMAGE_KB,
            'video' => 'nullable|file|mimes:mp4,mov,avi,mpeg,mpg|max:' . self::MAX_VIDEO_KB,
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'province' => 'nullable|string|max:120',
            'postal_code' => 'nullable|string|max:30',
            'country' => 'nullable|string|max:120',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'delete_more_images' => 'nullable|array',
            'delete_more_images.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos invalidos', 422, $validator->errors()->toArray());
        }

        $payload = [];
        if ($request->exists('title')) {
            $payload['title'] = $this->nullableString($request->input('title'));
        }
        if ($request->exists('description')) {
            $payload['description'] = $this->nullableString($request->input('description'));
        }
        if ($request->exists('availability')) {
            $payload['availability'] = $this->nullableString($request->input('availability'));
        }
        if ($request->exists('document_number')) {
            $payload['document_number'] = $this->nullableString($request->input('document_number'));
        }
        if ($request->exists('page_url')) {
            $payload['page_url'] = $this->nullableString($request->input('page_url'));
        }
        if (! empty($payload)) {
            Service::query()->where('id', (int) $service->id)->update($payload);
        }

        if ($request->exists('service_type')) {
            ServiceTypeLink::query()->where('service_id', (int) $service->id)->delete();
            foreach ((array) $request->input('service_type', []) as $serviceTypeId) {
                ServiceTypeLink::query()->create([
                    'service_id' => (int) $service->id,
                    'service_type_id' => (int) $serviceTypeId,
                ]);
            }
        }

        if ($request->hasAny(['address', 'city', 'province', 'postal_code', 'country', 'latitude', 'longitude'])) {
            ServiceAddress::query()->updateOrCreate(
                ['service_id' => (int) $service->id],
                [
                    'address' => $this->nullableString($request->input('address')) ?? '',
                    'city' => $this->nullableString($request->input('city')) ?? '',
                    'province' => $this->nullableString($request->input('province')) ?? '',
                    'postal_code' => $this->nullableString($request->input('postal_code')) ?? '',
                    'country' => $this->nullableString($request->input('country')) ?? '',
                    'latitude' => $this->nullableString($request->input('latitude')) ?? '',
                    'longitude' => $this->nullableString($request->input('longitude')) ?? '',
                ]
            );
        }

        $imagePath = public_path('img/uploads');
        $videoPath = public_path('video/uploads');
        if (! is_dir($imagePath)) {
            @mkdir($imagePath, 0755, true);
        }
        if (! is_dir($videoPath)) {
            @mkdir($videoPath, 0755, true);
        }

        $coverImage = $request->file('cover_image');
        if ($coverImage) {
            $storedCover = $this->storeUploadedImage($coverImage, $imagePath);
            if (! $storedCover['success']) {
                return $this->errorResponse($storedCover['error'], 422);
            }

            $existingCover = CoverImage::query()->where('service_id', (int) $service->id)->first();
            CoverImage::query()->updateOrCreate(
                ['service_id' => (int) $service->id],
                ['url' => $storedCover['file_name']]
            );
            if ($existingCover && $existingCover->url !== $storedCover['file_name']) {
                $this->deleteStoredFile('img/uploads', (string) $existingCover->url);
            }
        }

        $this->persistMoreImages($request, (int) $service->id, $imagePath);
        $this->persistVideo($request, (int) $service->id, $videoPath);
        $this->deleteOwnedMoreImages((int) $service->id, $request->input('delete_more_images', []));

        return $this->successResponse($this->loadServicePayload((int) $service->id, (int) $service->user_id), null, 'Servicio actualizado');
    }

    public function destroy(Request $request, int $id)
    {
        $service = $this->resolveOwnedService($request, $id);
        if (! $service instanceof Service) {
            return $service;
        }

        CoverImage::query()->where('service_id', (int) $service->id)->delete();
        MoreImage::query()->where('service_id', (int) $service->id)->delete();
        Video::query()->where('service_id', (int) $service->id)->delete();
        ServiceAddress::query()->where('service_id', (int) $service->id)->delete();
        ServiceTypeLink::query()->where('service_id', (int) $service->id)->delete();
        $service->delete();

        return $this->successResponse(null, null, 'Servicio eliminado');
    }

    private function resolveOwnedService(Request $request, int $id): Service|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }

        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        $service = Service::query()
            ->where('id', $id)
            ->where('user_id', (int) $user->id)
            ->first();

        if (! $service) {
            return $this->errorResponse('Servicio no encontrado', 404);
        }

        return $service;
    }

    private function loadServicePayload(int $serviceId, int $ownerId): array
    {
        $service = Service::query()->findOrFail($serviceId);
        $address = ServiceAddress::query()->where('service_id', $serviceId)->first();
        $cover = CoverImage::query()->where('service_id', $serviceId)->first();
        $video = Video::query()->where('service_id', $serviceId)->first();
        $serviceTypeLinks = ServiceTypeLink::query()->where('service_id', $serviceId)->get()->all();
        $moreImages = MoreImage::query()->where('service_id', $serviceId)->get()->all();
        $typeIds = collect($serviceTypeLinks)->pluck('service_type_id')->filter()->map(fn ($v) => (int) $v)->all();
        $typeMap = empty($typeIds) ? [] : ServiceType::query()->whereIn('id', $typeIds)->pluck('name', 'id')->all();
        $owner = User::query()->find($ownerId);

        $payload = $this->formatService($service, $address, $cover, $video, $serviceTypeLinks, $moreImages, $typeMap);
        $payload['owner'] = $owner ? [
            'id' => (int) $owner->id,
            'email' => $owner->email,
            'user_name' => $owner->user_name,
        ] : null;

        return $payload;
    }

    private function formatService(
        Service $service,
        ?ServiceAddress $address,
        ?CoverImage $coverImage,
        ?Video $video,
        array $serviceTypeLinks,
        array $moreImages,
        array $typeMap
    ): array {
        $coverFile = trim((string) ($coverImage?->url ?? ''));
        $videoFile = trim((string) ($video?->url ?? ''));
        $serviceTypes = collect($serviceTypeLinks)->map(function (ServiceTypeLink $link) use ($typeMap) {
            $typeId = (int) ($link->service_type_id ?? 0);

            return [
                'id' => $typeId,
                'name' => $typeMap[$typeId] ?? null,
            ];
        })->values()->all();

        return [
            'id' => (int) $service->id,
            'title' => $service->title,
            'description' => $service->description,
            'availability' => $service->availability,
            'document_number' => $service->document_number,
            'page_url' => $service->page_url,
            'user_id' => (int) $service->user_id,
            'created_at' => $service->created_at,
            'updated_at' => $service->updated_at,
            'service_types' => $serviceTypes,
            'cover_image' => $coverFile !== '' ? $coverFile : null,
            'cover_image_url' => $coverFile !== '' ? asset('img/uploads/' . ltrim($coverFile, '/')) : null,
            'video' => $videoFile !== '' ? $videoFile : null,
            'video_url' => $videoFile !== '' ? asset('video/uploads/' . ltrim($videoFile, '/')) : null,
            'more_images' => collect($moreImages)->map(function (MoreImage $image) {
                $file = trim((string) ($image->url ?? ''));
                return [
                    'id' => (int) $image->id,
                    'file' => $file !== '' ? $file : null,
                    'url' => $file !== '' ? asset('img/uploads/' . ltrim($file, '/')) : null,
                ];
            })->values()->all(),
            'address' => [
                'address' => $address?->address,
                'city' => $address?->city,
                'province' => $address?->province,
                'postal_code' => $address?->postal_code,
                'country' => $address?->country,
                'latitude' => $address?->latitude,
                'longitude' => $address?->longitude,
            ],
        ];
    }

    private function successResponse(mixed $data, ?array $meta = null, ?string $message = null, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => $meta,
            'message' => $message,
            'errors' => null,
        ], $status);
    }

    private function errorResponse(string $message, int $status, ?array $errors = null)
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    private function nullableString(mixed $value): ?string
    {
        $clean = trim((string) ($value ?? ''));
        return $clean !== '' ? $clean : null;
    }

    private function storeUploadedImage($file, string $imagePath): array
    {
        if (! $file || ! $file->isValid()) {
            return ['success' => false, 'error' => 'La imagen no es valida.'];
        }

        $mime = strtolower(trim((string) $file->getMimeType()));
        if (! in_array($mime, self::ALLOWED_IMAGE_MIME_TYPES, true)) {
            return ['success' => false, 'error' => 'Formato de imagen no soportado.'];
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
        }

        $name = Str::random(30) . '.' . $ext;
        if (! $file->move($imagePath, $name)) {
            return ['success' => false, 'error' => 'Error al guardar la imagen.'];
        }

        return ['success' => true, 'file_name' => $name];
    }

    private function storeUploadedVideo($file, string $videoPath): array
    {
        if (! $file || ! $file->isValid()) {
            return ['success' => false, 'error' => 'El video no es valido.'];
        }

        $mime = strtolower(trim((string) $file->getMimeType()));
        if (! in_array($mime, self::ALLOWED_VIDEO_MIME_TYPES, true)) {
            return ['success' => false, 'error' => 'Formato de video no soportado.'];
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = match ($mime) {
                'video/mp4' => 'mp4',
                'video/quicktime' => 'mov',
                'video/x-msvideo' => 'avi',
                'video/mpeg' => 'mpeg',
                default => 'mp4',
            };
        }

        $name = Str::random(30) . '.' . $ext;
        if (! $file->move($videoPath, $name)) {
            return ['success' => false, 'error' => 'Error al guardar el video.'];
        }

        return ['success' => true, 'file_name' => $name];
    }

    private function persistMoreImages(Request $request, int $serviceId, string $imagePath): void
    {
        $moreImages = $request->file('more_images', []);
        if (empty($moreImages)) {
            return;
        }

        foreach ((array) $moreImages as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $stored = $this->storeUploadedImage($file, $imagePath);
            if (! $stored['success']) {
                continue;
            }

            MoreImage::query()->create([
                'url' => $stored['file_name'],
                'service_id' => $serviceId,
            ]);
        }
    }

    private function persistVideo(Request $request, int $serviceId, string $videoPath): void
    {
        $video = $request->file('video');
        if (! $video) {
            return;
        }

        $stored = $this->storeUploadedVideo($video, $videoPath);
        if (! $stored['success']) {
            return;
        }

        $existingVideo = Video::query()->where('service_id', $serviceId)->first();
        Video::query()->updateOrCreate(
            ['service_id' => $serviceId],
            ['url' => $stored['file_name']]
        );

        if ($existingVideo && $existingVideo->url !== $stored['file_name']) {
            $this->deleteStoredFile('video/uploads', (string) $existingVideo->url);
        }
    }

    private function deleteOwnedMoreImages(int $serviceId, array $ids): void
    {
        $imageIds = collect($ids)
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($imageIds)) {
            return;
        }

        $images = MoreImage::query()
            ->where('service_id', $serviceId)
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {
            $this->deleteStoredFile('img/uploads', (string) $image->url);
            $image->delete();
        }
    }

    private function deleteStoredFile(string $relativeDir, string $fileName): void
    {
        $trimmed = trim($fileName);
        if ($trimmed === '') {
            return;
        }

        $safeFile = basename(str_replace('\\', '/', $trimmed));
        $path = public_path(trim($relativeDir, '/\\') . DIRECTORY_SEPARATOR . $safeFile);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
