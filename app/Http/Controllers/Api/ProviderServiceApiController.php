<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\Service;
use App\Models\ServiceAddress;
use App\Models\ServiceType;
use App\Models\ServiceWorkCode;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Video;
use App\Services\ProviderServiceTypeService;
use App\Services\ServiceRatingService;
use App\Support\ProviderGalleryRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        return $this->profile($request);

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
        $serviceTypeLinks = app(ProviderServiceTypeService::class)
            ->linkRowsForProvider((int) $user->id)
            ->all();
        $moreImages = empty($serviceIds)
            ? []
            : MoreImage::query()->whereIn('service_id', $serviceIds)->get()->groupBy('service_id')->all();

        $typeIds = collect($serviceTypeLinks)
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
                $serviceTypeLinks,
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

    public function profile(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }

        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        $userAddress = UserAddress::query()->where('user_id', (int) $user->id)->first();
        $cover = CoverImage::query()->where('provider_user_id', (int) $user->id)->latest('id')->first();
        $video = Video::query()->where('provider_user_id', (int) $user->id)->latest('id')->first();
        $gallery = MoreImage::query()
            ->where('provider_user_id', (int) $user->id)
            ->orderByRaw('CASE WHEN position IS NULL THEN 1 ELSE 0 END')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $typeIds = app(ProviderServiceTypeService::class)->typeIdsForProvider((int) $user->id);

        $types = empty($typeIds)
            ? []
            : ServiceType::query()
                ->whereIn('id', $typeIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (ServiceType $type) => ['id' => (int) $type->id, 'name' => $type->name])
                ->values()
                ->all();
        $ratingSummary = app(ServiceRatingService::class)->providerRatingSummary((int) $user->id);
        $providerKpis = $this->buildProviderKpis((int) $user->id);

        $galleryPayload = $gallery->values()->map(
            fn (MoreImage $image, int $position) => $this->galleryImagePayload($image, $position)
        )->all();
        $coverPath = $this->mediaPath('img/uploads', $cover?->url);
        $videoFilePath = $this->mediaPath('video/uploads', $video?->url);

        $payload = [
            'company_name' => $user->user_name,
            'title' => $user->provider_title,
            'description' => $user->provider_description,
            'phone' => $user->provider_phone,
            'landline_phone' => $user->provider_landline_phone,
            'availability' => $user->provider_availability,
            'page_url' => $user->provider_page_url,
            'updated_at' => optional($user->updated_at)?->toISOString(),
            'cover_image_path' => $coverPath,
            'cover_image_url' => $coverPath ? asset($coverPath) : null,
            'video_path' => $videoFilePath,
            'video_url' => $videoFilePath ? asset($videoFilePath) : null,
            'gallery' => $galleryPayload,
            'more_images' => $galleryPayload,
            'gallery_max_images' => ProviderGalleryRules::maximum(),
            'address' => $userAddress?->address,
            'city' => $userAddress?->city,
            'province' => $userAddress?->province,
            'postal_code' => $userAddress?->postal_code,
            'country' => $userAddress?->country,
            'latitude' => $userAddress?->latitude,
            'longitude' => $userAddress?->longitude,
            'services' => $types,
            'specialties' => $types,
            'specialty_ids' => collect($types)->pluck('id')->all(),
            'service_type_ids' => collect($types)->pluck('id')->all(),
            'services_count' => count($types),
            'provider_logo_path' => $this->providerLogoPath($user),
            'provider_logo_url' => $this->providerLogoUrl($user),
            'rating_avg' => isset($ratingSummary['average_stars']) ? (float) $ratingSummary['average_stars'] : 0.0,
            'reviews_count' => isset($ratingSummary['ratings_count']) ? (int) $ratingSummary['ratings_count'] : 0,
            // Canonical service KPIs for mobile dashboard.
            'profile_visits' => $providerKpis['profile_visits'],
            'profile_visits_change_pct' => $providerKpis['profile_visits_change_pct'],
            'contact_clicks' => $providerKpis['contact_clicks'],
            'contact_clicks_change_pct' => $providerKpis['contact_clicks_change_pct'],
            'service_tickets' => $providerKpis['service_tickets'],
            'service_tickets_change_pct' => $providerKpis['service_tickets_change_pct'],
            // Backward-compatible aliases expected by some mobile clients.
            'visits_count' => $providerKpis['profile_visits'],
            'contact_clicks_count' => $providerKpis['contact_clicks'],
            'service_tickets_count' => $providerKpis['service_tickets'],
        ];

        return $this->successResponse($payload);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }

        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        $input = $request->all();

        $specialtyField = $this->firstPresentField($request, ['specialty_ids', 'service_type_ids', 'service_type']);
        if ($specialtyField !== null) {
            $input['specialty_ids'] = $this->decodeMultipartArray($request->input($specialtyField));
        }

        $deleteField = $this->firstPresentField($request, ['gallery_delete_ids', 'delete_more_images']);
        if ($deleteField !== null) {
            $input['gallery_delete_ids'] = $this->decodeMultipartArray($request->input($deleteField));
        }

        if ($request->exists('gallery_order')) {
            $input['gallery_order'] = $this->decodeMultipartArray($request->input('gallery_order'));
        }

        $galleryFiles = $request->hasFile('gallery_images')
            ? (array) $request->file('gallery_images', [])
            : (array) $request->file('more_images', []);
        if ($galleryFiles !== []) {
            $input['gallery_images'] = $galleryFiles;
        }

        $validator = Validator::make($input, [
            'title' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'phone' => 'sometimes|nullable|string|max:40',
            'landline_phone' => 'sometimes|nullable|string|max:40',
            'availability' => 'sometimes|nullable|string|max:100',
            'page_url' => 'sometimes|nullable|string|max:255',
            'specialty_ids' => 'sometimes|array',
            'specialty_ids.*' => 'integer|exists:service_type,id',
            'cover_image' => 'sometimes|file|mimes:jpg,jpeg,png,webp|max:'.self::MAX_IMAGE_KB,
            'gallery_images' => 'sometimes|array|max:'.ProviderGalleryRules::maximum(),
            'gallery_images.*' => 'file|mimes:jpg,jpeg,png,webp|max:'.self::MAX_IMAGE_KB,
            'video' => 'sometimes|file|mimes:mp4,mov,avi,mpeg,mpg|max:'.self::MAX_VIDEO_KB,
            'gallery_delete_ids' => 'sometimes|array',
            'gallery_delete_ids.*' => 'integer|distinct|min:1',
            'gallery_order' => 'sometimes|array',
            'gallery_order.*' => 'integer|distinct|min:1',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:120',
            'province' => 'sometimes|nullable|string|max:120',
            'postal_code' => 'sometimes|nullable|string|max:30',
            'country' => 'sometimes|nullable|string|max:120',
            'latitude' => 'sometimes|nullable|string|max:50',
            'longitude' => 'sometimes|nullable|string|max:50',
        ], [
            'gallery_images.max' => ProviderGalleryRules::limitMessage(),
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            if (isset($errors['gallery_images']) && $request->hasFile('more_images')) {
                $errors['more_images'] = $errors['gallery_images'];
            }

            return $this->errorResponse('Datos invalidos', 422, $errors);
        }

        $newGalleryCount = collect($galleryFiles)
            ->filter(fn ($file) => $file && $file->isValid())
            ->count();
        $existingGallery = MoreImage::query()
            ->where('provider_user_id', (int) $user->id)
            ->get();
        $projectedGalleryCount = ProviderGalleryRules::projectedCount(
            $existingGallery,
            (array) ($input['gallery_delete_ids'] ?? []),
            $newGalleryCount,
            $newGalleryCount > 0
        );

        $hasGalleryMutation = $newGalleryCount > 0 || $deleteField !== null;
        if ($hasGalleryMutation && $projectedGalleryCount > ProviderGalleryRules::maximum()) {
            $errors = [
                'gallery_images' => [ProviderGalleryRules::limitMessage()],
            ];
            if ($request->hasFile('more_images')) {
                $errors['more_images'] = $errors['gallery_images'];
            }

            return $this->errorResponse('Datos invalidos', 422, $errors);
        }

        $deleteIds = $this->normalizedIds((array) ($input['gallery_delete_ids'] ?? []));
        $orderIds = $this->normalizedIds((array) ($input['gallery_order'] ?? []));
        $ownedGalleryIds = $existingGallery->pluck('id')->map(fn ($id) => (int) $id)->all();
        $unknownDeleteIds = array_values(array_diff($deleteIds, $ownedGalleryIds));
        if ($unknownDeleteIds !== []) {
            return $this->errorResponse('Datos invalidos', 422, [
                'gallery_delete_ids' => ['La galeria contiene imagenes inexistentes o que no pertenecen al proveedor.'],
            ]);
        }

        if ($orderIds !== [] && $newGalleryCount > 0) {
            return $this->errorResponse('Datos invalidos', 422, [
                'gallery_order' => ['Ordena la galeria en una peticion separada de la subida de imagenes.'],
            ]);
        }

        if ($request->exists('gallery_order')) {
            $expectedOrderIds = array_values(array_diff($ownedGalleryIds, $deleteIds));
            sort($expectedOrderIds);
            $comparableOrderIds = $orderIds;
            sort($comparableOrderIds);
            if ($expectedOrderIds !== $comparableOrderIds) {
                return $this->errorResponse('Datos invalidos', 422, [
                    'gallery_order' => ['El orden debe incluir exactamente todas las imagenes finales de la galeria.'],
                ]);
            }
        }

        $userUpdates = [];
        foreach ([
            'title' => 'provider_title',
            'description' => 'provider_description',
            'phone' => 'provider_phone',
            'landline_phone' => 'provider_landline_phone',
            'availability' => 'provider_availability',
            'page_url' => 'provider_page_url',
        ] as $inputField => $userField) {
            if ($request->exists($inputField)) {
                $userUpdates[$userField] = $this->nullableString($request->input($inputField));
            }
        }

        if ($userUpdates !== []) {
            $user->fill($userUpdates);
            $user->save();
        }

        if ($specialtyField !== null) {
            app(ProviderServiceTypeService::class)->syncForProvider(
                (int) $user->id,
                (array) $input['specialty_ids']
            );
        }

        $addressFields = ['address', 'city', 'province', 'postal_code', 'country', 'latitude', 'longitude'];
        if ($request->hasAny($addressFields)) {
            $addressPayload = [];
            foreach ($addressFields as $field) {
                if ($request->exists($field)) {
                    $addressPayload[$field] = $this->nullableString($request->input($field));
                }
            }

            UserAddress::query()->updateOrCreate(['user_id' => (int) $user->id], $addressPayload);
        }

        $imagePath = public_path('img/uploads');
        $videoPath = public_path('video/uploads');
        if (! is_dir($imagePath)) {
            @mkdir($imagePath, 0755, true);
        }
        if (! is_dir($videoPath)) {
            @mkdir($videoPath, 0755, true);
        }

        if ($request->hasFile('cover_image')) {
            $storedCover = $this->storeUploadedImage($request->file('cover_image'), $imagePath);
            if (! $storedCover['success']) {
                return $this->errorResponse($storedCover['error'], 422, ['cover_image' => [$storedCover['error']]]);
            }

            $existingCover = CoverImage::query()->where('provider_user_id', (int) $user->id)->first();
            try {
                CoverImage::query()->updateOrCreate(
                    ['provider_user_id' => (int) $user->id],
                    [
                        'service_id' => null,
                        'property_id' => null,
                        'is_provider_default' => false,
                        'source_provider_user_id' => null,
                        'url' => $storedCover['file_name'],
                    ]
                );
            } catch (\Throwable $exception) {
                $this->deleteStoredFile('img/uploads', $storedCover['file_name']);
                report($exception);

                return $this->errorResponse(
                    'No se pudo actualizar la portada en este momento.',
                    422,
                    ['cover_image' => ['No se pudo guardar la portada.']]
                );
            }
            if ($existingCover && $existingCover->url !== $storedCover['file_name']) {
                $this->deleteStoredFile('img/uploads', (string) $existingCover->url);
            }
        }

        $galleryError = $this->applyProviderGalleryChanges(
            (int) $user->id,
            $galleryFiles,
            $deleteIds,
            $request->exists('gallery_order') ? $orderIds : null,
            $imagePath
        );
        if ($galleryError !== null) {
            return $this->errorResponse($galleryError, 422, ['gallery_images' => [$galleryError]]);
        }

        $videoError = $this->persistProviderVideo($request, (int) $user->id, $videoPath);
        if ($videoError !== null) {
            return $this->errorResponse($videoError, 422, ['video' => [$videoError]]);
        }

        $response = $this->profile($request);
        $response->setData(array_merge((array) $response->getData(true), [
            'message' => 'Ficha comercial actualizada correctamente.',
        ]));

        return $response;
    }

    public function workCodes(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }

        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        $codes = ServiceWorkCode::query()
            ->where('provider_user_id', (int) $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (ServiceWorkCode $code) => [
                'id' => (int) $code->id,
                'code' => $code->code,
                'status' => $code->is_used ? 'Usado' : 'Activo',
                'is_used' => (bool) $code->is_used,
                'created_at' => optional($code->created_at)?->toISOString(),
                'used_at' => optional($code->used_at)?->toISOString(),
            ])
            ->values()
            ->all();

        return $this->successResponse(['codes' => $codes]);
    }

    public function createWorkCode(Request $request, ServiceRatingService $serviceRatingService)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }

        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        $code = $serviceRatingService->createWorkCode((int) $user->id);

        return $this->successResponse(['code' => $code], null, 'Codigo generado correctamente.', 201);
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

        return $this->updateProfile($request);

        $validator = Validator::make($request->all(), [
            'availability' => 'required|string|max:100',
            'description' => 'required|string',
            'title' => 'nullable|string|max:255',
            'page_url' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'service_type' => 'required|array|min:1',
            'service_type.*' => 'integer|exists:service_type,id',
            'cover_image' => 'required|file|mimes:jpg,jpeg,png,webp|max:'.self::MAX_IMAGE_KB,
            'more_images' => 'nullable|array|max:'.ProviderGalleryRules::maximum(),
            'more_images.*' => 'file|mimes:jpg,jpeg,png,webp|max:'.self::MAX_IMAGE_KB,
            'video' => 'nullable|file|mimes:mp4,mov,avi,mpeg,mpg|max:'.self::MAX_VIDEO_KB,
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

        app(ProviderServiceTypeService::class)->syncForProvider((int) $user->id, (array) $request->input('service_type', []));

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
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }
        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        return $this->profile($request);

        $service = $this->resolveOwnedService($request, $id);
        if (! $service instanceof Service) {
            return $service;
        }

        return $this->successResponse($this->loadServicePayload((int) $service->id, (int) $service->user_id));
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }
        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        return $this->updateProfile($request);

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
            'cover_image' => 'sometimes|file|mimes:jpg,jpeg,png,webp|max:'.self::MAX_IMAGE_KB,
            'more_images' => 'nullable|array|max:'.ProviderGalleryRules::maximum(),
            'more_images.*' => 'file|mimes:jpg,jpeg,png,webp|max:'.self::MAX_IMAGE_KB,
            'video' => 'nullable|file|mimes:mp4,mov,avi,mpeg,mpg|max:'.self::MAX_VIDEO_KB,
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
            app(ProviderServiceTypeService::class)->syncForProvider((int) $service->user_id, (array) $request->input('service_type', []));
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
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('No autenticado', 401);
        }
        if (! $user->isServiceProvider()) {
            return $this->errorResponse('No autorizado', 403);
        }

        return $this->errorResponse(
            'La ficha del proveedor no se puede eliminar desde el antiguo CRUD de servicios.',
            410
        );

        $service = $this->resolveOwnedService($request, $id);
        if (! $service instanceof Service) {
            return $service;
        }

        CoverImage::query()->where('service_id', (int) $service->id)->delete();
        MoreImage::query()->where('service_id', (int) $service->id)->delete();
        Video::query()->where('service_id', (int) $service->id)->delete();
        ServiceAddress::query()->where('service_id', (int) $service->id)->delete();
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
        $serviceTypeLinks = app(ProviderServiceTypeService::class)->linkRowsForProvider($ownerId)->all();
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
        $specialties = collect($serviceTypeLinks)->map(function ($link) use ($typeMap) {
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
            'specialties' => $specialties,
            'service_types' => $specialties,
            'cover_image' => $coverFile !== '' ? $coverFile : null,
            'cover_image_url' => $coverFile !== '' ? asset('img/uploads/'.ltrim($coverFile, '/')) : null,
            'video' => $videoFile !== '' ? $videoFile : null,
            'video_url' => $videoFile !== '' ? asset('video/uploads/'.ltrim($videoFile, '/')) : null,
            'more_images' => collect($moreImages)->map(function (MoreImage $image) {
                $file = trim((string) ($image->url ?? ''));

                return [
                    'id' => (int) $image->id,
                    'file' => $file !== '' ? $file : null,
                    'url' => $file !== '' ? asset('img/uploads/'.ltrim($file, '/')) : null,
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

        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return ['success' => false, 'error' => 'El servidor no puede convertir imagenes a WEBP.'];
        }

        $source = $this->createImageResourceFromUpload($file);
        if (! $source) {
            return ['success' => false, 'error' => 'No se pudo procesar la imagen.'];
        }

        if (! is_dir($imagePath) && ! @mkdir($imagePath, 0755, true) && ! is_dir($imagePath)) {
            imagedestroy($source);

            return ['success' => false, 'error' => 'No se pudo preparar el almacenamiento de imagenes.'];
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, 1920 / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $name = Str::random(30).'.webp';
        $saved = imagewebp($canvas, $imagePath.DIRECTORY_SEPARATOR.$name, 82);
        imagedestroy($canvas);
        imagedestroy($source);

        if (! $saved) {
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

        $name = Str::random(30).'.'.$ext;
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

    private function applyProviderGalleryChanges(
        int $providerId,
        array $files,
        array $deleteIds,
        ?array $orderIds,
        string $imagePath
    ): ?string {
        $files = collect($files)
            ->filter(fn ($file) => $file && $file->isValid())
            ->values();
        if ($files->isEmpty() && $deleteIds === [] && $orderIds === null) {
            return null;
        }

        $storedNames = [];
        foreach ($files as $file) {
            $stored = $this->storeUploadedImage($file, $imagePath);
            if (! $stored['success']) {
                foreach ($storedNames as $storedName) {
                    $this->deleteStoredFile('img/uploads', $storedName);
                }

                return $stored['error'];
            }
            $storedNames[] = $stored['file_name'];
        }

        $filesToDeleteAfterCommit = [];
        try {
            DB::transaction(function () use (
                $providerId,
                $deleteIds,
                $orderIds,
                $storedNames,
                &$filesToDeleteAfterCommit
            ): void {
                $imagesToDelete = collect();
                if ($deleteIds !== [] || $storedNames !== []) {
                    $imagesToDelete = MoreImage::query()
                        ->where('provider_user_id', $providerId)
                        ->where(function ($query) use ($deleteIds, $storedNames): void {
                            if ($deleteIds !== []) {
                                $query->whereIn('id', $deleteIds);
                            }
                            if ($storedNames !== []) {
                                $method = $deleteIds !== [] ? 'orWhere' : 'where';
                                $query->{$method}('is_provider_default', true);
                            }
                        })
                        ->get();
                }

                $filesToDeleteAfterCommit = $imagesToDelete
                    ->pluck('url')
                    ->map(fn ($path) => (string) $path)
                    ->all();
                if ($imagesToDelete->isNotEmpty()) {
                    MoreImage::query()->whereIn('id', $imagesToDelete->pluck('id'))->delete();
                }

                $nextPosition = (int) MoreImage::query()
                    ->where('provider_user_id', $providerId)
                    ->max('position');
                if (MoreImage::query()->where('provider_user_id', $providerId)->exists()) {
                    $nextPosition++;
                }

                foreach ($storedNames as $storedName) {
                    MoreImage::query()->create([
                        'url' => $storedName,
                        'position' => $nextPosition++,
                        'provider_user_id' => $providerId,
                        'is_provider_default' => false,
                        'source_provider_user_id' => null,
                        'service_id' => null,
                        'property_id' => null,
                    ]);
                }

                if ($orderIds !== null) {
                    foreach ($orderIds as $position => $imageId) {
                        MoreImage::query()
                            ->where('provider_user_id', $providerId)
                            ->where('id', $imageId)
                            ->update(['position' => $position]);
                    }
                } else {
                    $this->normalizeProviderGalleryPositions($providerId);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedNames as $storedName) {
                $this->deleteStoredFile('img/uploads', $storedName);
            }
            report($exception);

            return 'No se pudo actualizar la galeria en este momento.';
        }

        foreach ($filesToDeleteAfterCommit as $fileName) {
            $this->deleteStoredFile('img/uploads', $fileName);
        }

        return null;
    }

    private function persistProviderVideo(Request $request, int $providerId, string $videoPath): ?string
    {
        $video = $request->file('video');
        if (! $video) {
            return null;
        }

        $stored = $this->storeUploadedVideo($video, $videoPath);
        if (! $stored['success']) {
            return $stored['error'];
        }

        $existingVideo = Video::query()->where('provider_user_id', $providerId)->first();
        try {
            Video::query()->updateOrCreate(
                ['provider_user_id' => $providerId],
                [
                    'service_id' => null,
                    'property_id' => null,
                    'is_provider_default' => false,
                    'source_provider_user_id' => null,
                    'url' => $stored['file_name'],
                ]
            );
        } catch (\Throwable $exception) {
            $this->deleteStoredFile('video/uploads', $stored['file_name']);
            report($exception);

            return 'No se pudo actualizar el video en este momento.';
        }

        if ($existingVideo && $existingVideo->url !== $stored['file_name']) {
            $this->deleteStoredFile('video/uploads', (string) $existingVideo->url);
        }

        return null;
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

    private function firstPresentField(Request $request, array $fields): ?string
    {
        foreach ($fields as $field) {
            if ($request->exists($field)) {
                return $field;
            }
        }

        return null;
    }

    private function decodeMultipartArray(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function normalizedIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeProviderGalleryPositions(int $providerId): void
    {
        $ids = MoreImage::query()
            ->where('provider_user_id', $providerId)
            ->orderByRaw('CASE WHEN position IS NULL THEN 1 ELSE 0 END')
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $position => $id) {
            MoreImage::query()->where('id', $id)->update(['position' => $position]);
        }
    }

    private function galleryImagePayload(MoreImage $image, int $position): array
    {
        $path = $this->mediaPath('img/uploads', $image->url);

        return [
            'id' => (int) $image->id,
            'path' => $path,
            'file' => $image->url,
            'url' => $path ? asset($path) : null,
            'position' => $position,
        ];
    }

    private function mediaPath(string $directory, mixed $fileName): ?string
    {
        $normalized = str_replace('\\', '/', trim((string) $fileName));
        if (
            $normalized === ''
            || str_starts_with($normalized, '/')
            || preg_match('#(^|/)\.\.(/|$)#', $normalized)
        ) {
            return null;
        }

        return trim($directory, '/').'/'.ltrim($normalized, '/');
    }

    private function deleteStoredFile(string $relativeDir, string $fileName): void
    {
        $normalized = str_replace('\\', '/', trim($fileName));
        if (
            $normalized === ''
            || str_starts_with($normalized, '/')
            || preg_match('#(^|/)\.\.(/|$)#', $normalized)
        ) {
            return;
        }

        $path = public_path(trim($relativeDir, '/\\').'/'.ltrim($normalized, '/'));
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function providerLogoPath(User $user): ?string
    {
        $photo = trim((string) ($user->photo ?? ''));

        return $photo !== '' ? $photo : null;
    }

    private function providerLogoUrl(User $user): ?string
    {
        $path = $this->providerLogoPath($user);

        return $path !== null ? asset('img/photo_profile/'.ltrim($path, '/')) : null;
    }

    private function createImageResourceFromUpload(\Illuminate\Http\UploadedFile $file): \GdImage|false
    {
        $mime = (string) $file->getMimeType();
        $path = $file->getRealPath();

        if (! $path) {
            return false;
        }

        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function buildProviderKpis(int $providerUserId): array
    {
        $windowDays = 30;
        $currentStart = now()->subDays($windowDays);
        $previousStart = now()->subDays($windowDays * 2);
        $previousEnd = $currentStart;

        $viewsCurrent = Schema::hasTable('service_profile_visits')
            ? (int) DB::table('service_profile_visits')
                ->where('provider_user_id', $providerUserId)
                ->where('created_at', '>=', $currentStart)
                ->count()
            : 0;
        $viewsPrevious = Schema::hasTable('service_profile_visits')
            ? (int) DB::table('service_profile_visits')
                ->where('provider_user_id', $providerUserId)
                ->where('created_at', '>=', $previousStart)
                ->where('created_at', '<', $previousEnd)
                ->count()
            : 0;

        $contactCurrent = Schema::hasTable('service_contact_clicks')
            ? (int) DB::table('service_contact_clicks')
                ->where('provider_user_id', $providerUserId)
                ->where('created_at', '>=', $currentStart)
                ->count()
            : 0;
        $contactPrevious = Schema::hasTable('service_contact_clicks')
            ? (int) DB::table('service_contact_clicks')
                ->where('provider_user_id', $providerUserId)
                ->where('created_at', '>=', $previousStart)
                ->where('created_at', '<', $previousEnd)
                ->count()
            : 0;

        $ticketsCurrent = Schema::hasTable('service_work_codes')
            ? (int) DB::table('service_work_codes')
                ->where('provider_user_id', $providerUserId)
                ->where('is_used', 1)
                ->whereNotNull('used_at')
                ->where('used_at', '>=', $currentStart)
                ->count()
            : 0;
        $ticketsPrevious = Schema::hasTable('service_work_codes')
            ? (int) DB::table('service_work_codes')
                ->where('provider_user_id', $providerUserId)
                ->where('is_used', 1)
                ->whereNotNull('used_at')
                ->where('used_at', '>=', $previousStart)
                ->where('used_at', '<', $previousEnd)
                ->count()
            : 0;

        return [
            'profile_visits' => $viewsCurrent,
            'profile_visits_change_pct' => $this->percentageChange($viewsCurrent, $viewsPrevious),
            'contact_clicks' => $contactCurrent,
            'contact_clicks_change_pct' => $this->percentageChange($contactCurrent, $contactPrevious),
            'service_tickets' => $ticketsCurrent,
            'service_tickets_change_pct' => $this->percentageChange($ticketsCurrent, $ticketsPrevious),
        ];
    }

    private function percentageChange(int $current, int $previous): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}
