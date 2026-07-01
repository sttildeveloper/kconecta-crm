<?php

namespace App\Http\Controllers;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\PostVisit;
use App\Models\Property;
use App\Models\PropertyAddress;
use App\Models\Video;
use App\Models\PsEmailOwner;
use App\Models\PsLinkCopied;
use App\Models\PsMessagesReceived;
use App\Models\PsOwnerCalls;
use App\Models\PsSavedFavorite;
use App\Models\PsSharedFacebook;
use App\Models\PsSharedFriends;
use App\Models\PsViewsDetail;
use App\Models\PsViewsSearch;
use App\Models\PsWhatsappClicks;
use App\Models\Service;
use App\Models\ServiceAddress;
use App\Models\ServiceType;
use App\Models\ServiceTypeLink;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserFree;
use App\Http\Requests\StoreServiceRatingByCodeRequest;
use App\Http\Requests\StoreServiceRatingRequest;
use App\Http\Requests\StoreServiceWorkCodeRequest;
use App\Services\EmailService;
use App\Services\ServiceRatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    private function successResponse(mixed $data = null, ?array $meta = null, ?string $message = null, int $status = 200, array $legacy = [])
    {
        return response()->json(array_merge([
            'success' => true,
            'data' => $data,
            'meta' => $meta,
            'message' => $message,
            'errors' => null,
        ], $legacy), $status);
    }

    private function errorResponse(string $message, int $status, ?array $errors = null, array $legacy = [])
    {
        return response()->json(array_merge([
            'success' => false,
            'data' => null,
            'meta' => null,
            'message' => $message,
            'errors' => $errors,
        ], $legacy), $status);
    }

    public function createServiceWorkCode(StoreServiceWorkCodeRequest $request, ServiceRatingService $serviceRatingService)
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('Debes iniciar sesion.', 401, ['code' => 'UNAUTHENTICATED']);
        }

        if ((int) $user->user_level_id !== User::LEVEL_SERVICE_PROVIDER) {
            return $this->errorResponse('Solo un proveedor puede generar codigos de trabajo.', 403, ['code' => 'ROLE_NOT_ALLOWED']);
        }

        $code = $serviceRatingService->createWorkCode((int) $user->id);

        return $this->successResponse([
            'code' => $code,
        ], null, 'Codigo generado correctamente.', 201);
    }

    public function storeServiceRating(StoreServiceRatingRequest $request, ServiceRatingService $serviceRatingService)
    {
        $client = $request->user();

        if (! $client) {
            return $this->errorResponse('Debes iniciar sesion.', 401, ['code' => 'UNAUTHENTICATED']);
        }

        if (! $serviceRatingService->isFinalClient($client)) {
            return $this->errorResponse('Solo el perfil Cliente final puede valorar proveedores.', 403, ['code' => 'ROLE_NOT_ALLOWED']);
        }

        if (! $client->hasVerifiedEmail()) {
            return $this->errorResponse('Debes verificar tu email para valorar.', 403, ['code' => 'EMAIL_NOT_VERIFIED']);
        }

        $providerUserId = (int) $request->integer('provider_user_id');
        $stars = (int) $request->integer('stars');
        $workCode = trim((string) $request->input('work_code'));

        try {
            $serviceRatingService->submitRating($client, $providerUserId, $workCode, $stars);
        } catch (\DomainException $e) {
            $errorCode = $e->getMessage();
            $status = 422;
            $message = 'No se pudo registrar la valoracion.';

            if ($errorCode === 'PROVIDER_NOT_ALLOWED') {
                $message = 'El proveedor indicado no es valido.';
            } elseif ($errorCode === 'SELF_RATING_NOT_ALLOWED') {
                $message = 'No puedes valorarte a ti mismo.';
            } elseif ($errorCode === 'WORK_CODE_INVALID') {
                $message = 'El codigo de trabajo es invalido para este proveedor.';
            } elseif ($errorCode === 'WORK_CODE_USED') {
                $message = 'El codigo de trabajo ya fue usado.';
            }

            return $this->errorResponse($message, $status, ['code' => $errorCode]);
        }

        return $this->successResponse(
            $serviceRatingService->providerRatingSummary($providerUserId, $client),
            null,
            'Valoracion registrada correctamente.'
        );
    }

    public function storeServiceRatingByCode(StoreServiceRatingByCodeRequest $request, ServiceRatingService $serviceRatingService)
    {
        $client = $request->user();

        if (! $client) {
            return $this->errorResponse('Debes iniciar sesion.', 401, ['code' => 'UNAUTHENTICATED']);
        }

        if (! $serviceRatingService->isFinalClient($client)) {
            return $this->errorResponse('Solo el perfil Cliente final puede valorar proveedores.', 403, ['code' => 'ROLE_NOT_ALLOWED']);
        }

        if (! $client->hasVerifiedEmail()) {
            return $this->errorResponse('Debes verificar tu email para valorar.', 403, ['code' => 'EMAIL_NOT_VERIFIED']);
        }

        $stars = (int) $request->integer('stars');
        $workCode = trim((string) $request->input('work_code'));

        try {
            $providerUserId = $serviceRatingService->submitRatingByWorkCode($client, $workCode, $stars);
        } catch (\DomainException $e) {
            $errorCode = $e->getMessage();
            $message = 'No se pudo registrar la valoracion.';

            if ($errorCode === 'PROVIDER_NOT_ALLOWED') {
                $message = 'El proveedor del codigo no es valido.';
            } elseif ($errorCode === 'SELF_RATING_NOT_ALLOWED') {
                $message = 'No puedes valorarte a ti mismo.';
            } elseif ($errorCode === 'WORK_CODE_INVALID') {
                $message = 'El codigo de trabajo es invalido.';
            } elseif ($errorCode === 'WORK_CODE_USED') {
                $message = 'El codigo de trabajo ya fue usado.';
            }

            return $this->errorResponse($message, 422, ['code' => $errorCode]);
        }

        return $this->successResponse(
            $serviceRatingService->providerRatingSummary($providerUserId, $client),
            null,
            'Valoracion registrada correctamente.'
        );
    }

    public function providerServiceRatingSummary(Request $request, int $providerUserId, ServiceRatingService $serviceRatingService)
    {
        $provider = DB::table('user')
            ->where('id', $providerUserId)
            ->first(['id', 'user_level_id']);

        if (! $provider || (int) $provider->user_level_id !== User::LEVEL_SERVICE_PROVIDER) {
            return $this->errorResponse('El proveedor indicado no es valido.', 404, ['code' => 'PROVIDER_NOT_ALLOWED']);
        }

        $authUser = $request->user() ?: $request->user('sanctum');

        return $this->successResponse($serviceRatingService->providerRatingSummary($providerUserId, $authUser));
    }

    public function myServiceRatingsDashboard(Request $request, ServiceRatingService $serviceRatingService)
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('Debes iniciar sesion.', 401, ['code' => 'UNAUTHENTICATED']);
        }

        if (! $serviceRatingService->isFinalClient($user)) {
            return $this->errorResponse('Solo el perfil Cliente final puede consultar sus valoraciones.', 403, ['code' => 'ROLE_NOT_ALLOWED']);
        }

        $ratingsQuery = DB::table('service_provider_ratings as r')
            ->where('r.client_user_id', (int) $user->id);

        $aggregate = (clone $ratingsQuery)
            ->selectRaw('COUNT(*) as ratings_count, COUNT(DISTINCT r.provider_user_id) as providers_rated_count, AVG(r.stars) as average_stars')
            ->first();

        $averageStars = $aggregate && $aggregate->average_stars !== null
            ? round((float) $aggregate->average_stars, 2)
            : 0.0;

        $recentRatings = (clone $ratingsQuery)
            ->leftJoin('user as u', 'u.id', '=', 'r.provider_user_id')
            ->orderByDesc('r.updated_at')
            ->limit(10)
            ->get([
                'r.provider_user_id',
                'r.stars',
                'r.updated_at',
                'u.first_name',
                'u.last_name',
                'u.user_name',
                'u.email',
            ])
            ->map(function ($row) use ($user) {
                $fullName = trim(((string) ($row->first_name ?? '')) . ' ' . ((string) ($row->last_name ?? '')));
                $providerName = $fullName !== ''
                    ? $fullName
                    : (((string) ($row->user_name ?? '')) !== ''
                        ? (string) $row->user_name
                        : (((string) ($row->email ?? '')) !== '' ? (string) $row->email : ('Proveedor #' . (int) $row->provider_user_id)));
                $workCode = (string) (DB::table('service_work_codes')
                    ->where('provider_user_id', (int) $row->provider_user_id)
                    ->where('used_by_user_id', (int) $user->id)
                    ->where('is_used', 1)
                    ->orderByDesc('used_at')
                    ->orderByDesc('updated_at')
                    ->value('code') ?? '');

                return [
                    'provider_user_id' => (int) $row->provider_user_id,
                    'provider_name' => $providerName,
                    'stars' => (int) $row->stars,
                    'updated_at' => $row->updated_at ? date('Y-m-d H:i:s', strtotime((string) $row->updated_at)) : null,
                    'work_code' => $workCode,
                ];
            })
            ->values()
            ->all();

        return $this->successResponse([
            'ratingsCount' => (int) ($aggregate->ratings_count ?? 0),
            'providersRatedCount' => (int) ($aggregate->providers_rated_count ?? 0),
            'averageStars' => $averageStars,
            'recentRatings' => $recentRatings,
        ]);
    }

    public function searchProperties(Request $request)
    {
        $text = trim((string) $request->query('text', ''));
        $typeId = $request->query('type');
        $categoryId = $request->query('category');

        $provinceCounter = [];

        if (mb_strlen($text) >= 3) {
            $provinceRows = PropertyAddress::query()
                ->where('province', 'like', '%' . $text . '%')
                ->get(['province']);

            foreach ($provinceRows as $row) {
                $name = $row->province;
                if (! isset($provinceCounter[$name])) {
                    $provinceCounter[$name] = 0;
                }
                $provinceCounter[$name]++;
            }
        }

        if (count($provinceCounter) > 2) {
            $provinceCounter = array_slice($provinceCounter, 0, 2, true);
        }

        $addressRows = PropertyAddress::query()
            ->where('address', 'like', '%' . $text . '%')
            ->get();

        $addressCounter = [];

        foreach ($addressRows as $row) {
            $propertyQuery = Property::query()
                ->where('id', $row->property_id)
                ->where('state_id', 4);

            if (! empty($typeId)) {
                $propertyQuery->where('type_id', $typeId);
            }

            if (! empty($categoryId)) {
                $propertyQuery->where('category_id', $categoryId);
            }

            if (! $propertyQuery->exists()) {
                continue;
            }

            $address = $row->address;
            if (! isset($addressCounter[$address])) {
                $addressCounter[$address] = 0;
            }
            $addressCounter[$address]++;
        }

        $addressCounter = array_slice($addressCounter, 0, 7, true);

        return response()->json([
            'success' => true,
            'data' => $addressCounter,
            'meta' => [
                'province' => $provinceCounter,
            ],
            'message' => null,
            'errors' => null,
            // Backward compatibility
            'status' => 200,
            'province' => $provinceCounter,
        ]);
    }

    public function searchServices(Request $request)
    {
        $text = trim((string) $request->query('text', ''));
        $serviceTypeId = $request->query('service_type');

        $provinceCounter = [];

        if (mb_strlen($text) >= 3) {
            $provinceRows = UserAddress::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '<>', '')
                ->where('longitude', '<>', '')
                ->where('province', 'like', '%' . $text . '%')
                ->get(['province']);

            foreach ($provinceRows as $row) {
                $name = $row->province;
                if (! isset($provinceCounter[$name])) {
                    $provinceCounter[$name] = 0;
                }
                $provinceCounter[$name]++;
            }
        }

        if (count($provinceCounter) > 2) {
            $provinceCounter = array_slice($provinceCounter, 0, 2, true);
        }

        $addressRows = UserAddress::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '<>', '')
            ->where('longitude', '<>', '')
            ->where('address', 'like', '%' . $text . '%')
            ->get();

        $addressCounter = [];

        foreach ($addressRows as $row) {
            $userId = $row->user_id;
            $stateServiceType = true;

            if (! empty($serviceTypeId)) {
                $service = Service::query()
                    ->where('user_id', $userId)
                    ->first();

                if (! $service) {
                    continue;
                }

                $stateServiceType = ServiceTypeLink::query()
                    ->where('service_type_id', $serviceTypeId)
                    ->where('service_id', $service->id)
                    ->exists();
            }

            if (! $stateServiceType) {
                continue;
            }

            $address = $row->address;
            if (! isset($addressCounter[$address])) {
                $addressCounter[$address] = 0;
            }
            $addressCounter[$address]++;
        }

        $addressCounter = array_slice($addressCounter, 0, 7, true);

        return response()->json([
            'success' => true,
            'data' => $addressCounter,
            'meta' => [
                'province' => $provinceCounter,
            ],
            'message' => null,
            'errors' => null,
            // Backward compatibility
            'status' => 200,
            'province' => $provinceCounter,
        ]);
    }

    public function publicServiceTypes()
    {
        $types = ServiceType::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ServiceType $type) => [
                'id' => (int) $type->id,
                'name' => $type->name,
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $types,
            'message' => null,
            'errors' => null,
            'status' => 200,
        ]);
    }

    public function dataPropertiesForMap(Request $request)
    {
        $address = (string) $request->query('address');
        $categoryId = $request->query('ca');
        $typeId = $request->query('ty');
        $pMin = $request->query('p_min');
        $pMax = $request->query('p_max');
        $builtMin = $request->query('built_min');
        $builtMax = $request->query('built_max');
        $numMinBathrooms = $request->query('n_bar');
        $numMinBedrooms = $request->query('n_ber');
        $city = $request->query('city');
        $province = $request->query('province');

        $addressQuery = PropertyAddress::query();
        if (! empty($city) || ! empty($province)) {
            if (! empty($province) && empty($city)) {
                $addressQuery->where('province', trim($province));
            } elseif (! empty($city)) {
                $addressQuery->where('city', trim($city));
            }
            $addresses = $addressQuery->get();
        } elseif (! empty($address)) {
            $addressParts = explode(',', $address);
            $addressSeed = trim($addressParts[0]);
            $addressQuery->where(function ($query) use ($address, $addressSeed) {
                $query->where('address', 'like', '%' . trim($address) . '%')
                    ->orWhere('address', 'like', '%' . $addressSeed . '%')
                    ->orWhere('province', 'like', '%' . $addressSeed . '%')
                    ->orWhere('city', 'like', '%' . $addressSeed . '%');
            });
            $addresses = $addressQuery->get();
        } else {
            $addresses = collect();
        }

        $dataProperties = [];

        foreach ($addresses as $row) {
            $propertyQuery = Property::query()
                ->where('id', $row->property_id)
                ->where('state_id', 4);

            if (! empty($typeId)) {
                $propertyQuery->where('type_id', $typeId);
            }
            if (! empty($categoryId)) {
                $propertyQuery->where('category_id', $categoryId);
            }
            if (! empty($categoryId)) {
                $priceField = ((int) $categoryId === 1) ? 'rental_price' : 'sale_price';
                if (! empty($pMin)) {
                    $propertyQuery->where($priceField, '>=', $pMin);
                }
                if (! empty($pMax)) {
                    $propertyQuery->where($priceField, '<=', $pMax);
                }
            } else {
                if (! empty($pMin)) {
                    $propertyQuery->where(function ($query) use ($pMin) {
                        $query->where('sale_price', '>=', $pMin)
                            ->orWhere('rental_price', '>=', $pMin);
                    });
                }
                if (! empty($pMax)) {
                    $propertyQuery->where(function ($query) use ($pMax) {
                        $query->where('sale_price', '<=', $pMax)
                            ->orWhere('rental_price', '<=', $pMax);
                    });
                }
            }
            if (! empty($builtMin)) {
                $propertyQuery->where('meters_built', '>=', $builtMin);
            }
            if (! empty($builtMax)) {
                $propertyQuery->where('meters_built', '<=', $builtMax);
            }
            if (! empty($numMinBathrooms)) {
                $propertyQuery->where('bathrooms', '>=', $numMinBathrooms);
            }
            if (! empty($numMinBedrooms)) {
                $propertyQuery->where('bedrooms', '>=', $numMinBedrooms);
            }

            $property = $propertyQuery->first();
            if ($property) {
                $dataProperties[] = [
                    'id' => $property->reference,
                    'title' => $property->title,
                    'price' => $property->sale_price ?: $property->rental_price,
                    'lat' => $row->latitude,
                    'lng' => $row->longitude,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $dataProperties,
            'meta' => null,
            'message' => null,
            'errors' => null,
            // Backward compatibility
            'status' => 200,
        ]);
    }

    public function dataServicesForMap(Request $request)
    {
        $sti = $request->query('sti');
        $address = (string) $request->query('address');
        $city = $request->query('city');
        $province = $request->query('province');

        $serviceTypeIds = [];
        if (! empty($sti)) {
            if (is_array($sti)) {
                $sti = array_map('intval', $sti);
            } else {
                $sti = [(int) $sti];
            }

            $serviceTypeIds = ServiceTypeLink::query()
                ->whereIn('service_type_id', $sti)
                ->pluck('service_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (empty($serviceTypeIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => null,
                    'message' => null,
                    'errors' => null,
                    // Backward compatibility
                    'status' => 200,
                ]);
            }
        }

        $servicesQuery = Service::query()->orderBy('id');
        if (! empty($serviceTypeIds)) {
            $servicesQuery->whereIn('id', $serviceTypeIds);
        }
        $services = $servicesQuery->get(['id', 'user_id', 'title']);

        if ($services->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => null,
                'message' => null,
                'errors' => null,
                // Backward compatibility
                'status' => 200,
            ]);
        }

        $serviceIds = $services->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $providerIds = $services->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $ratingsSummaryByProvider = [];
        if (! empty($providerIds)) {
            $ratingsSummaryByProvider = DB::table('service_provider_ratings')
                ->selectRaw('provider_user_id, AVG(stars) as average_stars, COUNT(*) as ratings_count')
                ->whereIn('provider_user_id', $providerIds)
                ->groupBy('provider_user_id')
                ->get()
                ->keyBy('provider_user_id')
                ->map(function ($row) {
                    return [
                        'average_stars' => round((float) ($row->average_stars ?? 0), 2),
                        'ratings_count' => (int) ($row->ratings_count ?? 0),
                    ];
                })
                ->all();
        }

        $serviceAddresses = ServiceAddress::query()
            ->whereIn('service_id', $serviceIds)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '<>', '')
            ->where('longitude', '<>', '')
            ->get()
            ->keyBy(fn ($row) => (int) $row->service_id);

        $userAddresses = UserAddress::query()
            ->whereIn('user_id', $providerIds)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '<>', '')
            ->where('longitude', '<>', '')
            ->get()
            ->keyBy(fn ($row) => (int) $row->user_id);

        $usersById = User::query()
            ->whereIn('id', $providerIds)
            ->get(['id', 'user_name', 'first_name', 'last_name', 'photo', 'phone', 'mobile_phone', 'landline_phone'])
            ->keyBy(fn ($row) => (int) $row->id);

        $serviceTypeIdsByService = ServiceTypeLink::query()
            ->whereIn('service_id', $serviceIds)
            ->get(['service_id', 'service_type_id'])
            ->groupBy('service_id')
            ->map(function ($rows) {
                return $rows
                    ->pluck('service_type_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            });

        $allResolvedServiceTypeIds = $serviceTypeIdsByService
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $serviceTypesById = ServiceType::query()
            ->whereIn('id', $allResolvedServiceTypeIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy(fn ($row) => (int) $row->id);

        $coversByServiceId = CoverImage::query()
            ->whereIn('service_id', $serviceIds)
            ->get(['service_id', 'url'])
            ->keyBy(fn ($row) => (int) $row->service_id);

        $addressFilter = trim($address);
        $addressSeed = '';
        if ($addressFilter !== '') {
            $parts = explode(',', $addressFilter);
            $addressSeed = trim($parts[0] ?? '');
        }

        $dataProviders = [];
        foreach ($services as $service) {
            $serviceAddress = $serviceAddresses->get((int) $service->id);
            $userAddress = $userAddresses->get((int) $service->user_id);

            $resolvedCoordinates = $serviceAddress ?: $userAddress;
            if (! $resolvedCoordinates) {
                continue;
            }

            $resolvedCity = trim((string) (($serviceAddress->city ?? null) ?: ($userAddress->city ?? '')));
            $resolvedProvince = trim((string) (($serviceAddress->province ?? null) ?: ($userAddress->province ?? '')));
            $resolvedAddress = trim((string) (($serviceAddress->address ?? null) ?: ($userAddress->address ?? '')));

            if (! empty($city) && strcasecmp($resolvedCity, trim((string) $city)) !== 0) {
                continue;
            }
            if (! empty($province) && strcasecmp($resolvedProvince, trim((string) $province)) !== 0) {
                continue;
            }
            if ($addressFilter !== '') {
                $haystack = mb_strtolower($resolvedAddress . ' ' . $resolvedCity . ' ' . $resolvedProvince);
                $needleA = mb_strtolower($addressFilter);
                $needleB = mb_strtolower($addressSeed);
                if (mb_stripos($haystack, $needleA) === false && ($needleB === '' || mb_stripos($haystack, $needleB) === false)) {
                    continue;
                }
            }

            $providerUserId = (int) $service->user_id;
            $serviceId = (int) $service->id;
            $user = $usersById->get($providerUserId);

            $userName = '';
            $userLogoUrl = '';
            if ($user) {
                $userName = $user->user_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                if (! empty($user->photo)) {
                    $userLogoUrl = asset('img/photo_profile/' . ltrim((string) $user->photo, '/'));
                }
            }

            $serviceTypeLinks = $serviceTypeIdsByService->get($serviceId, []);
            $serviceTypes = collect($serviceTypeLinks)
                ->map(fn ($typeId) => $serviceTypesById->get((int) $typeId))
                ->filter()
                ->map(fn ($type) => ['id' => (int) $type->id, 'name' => $type->name])
                ->values()
                ->all();

            $cover = $coversByServiceId->get($serviceId);

            $phone = $user?->phone ?: ($user?->mobile_phone ?: $user?->landline_phone);
            $cleanPhone = preg_replace('/[^0-9+]/', '', (string) $phone);
            $whatsappPhone = ltrim((string) $cleanPhone, '+');
            $whatsappUrl = ! empty($whatsappPhone) ? 'https://wa.me/' . $whatsappPhone . '?text=' . urlencode('Hola, me interesa tu servicio') : null;

            if (! isset($dataProviders[$providerUserId])) {
                $dataProviders[$providerUserId] = [
                    // Keep `id` as a representative public service id for detail navigation compatibility.
                    'id' => $serviceId,
                    'service_id' => $serviceId,
                    'provider_user_id' => $providerUserId,
                    'title' => $userName ?: ($service->title ?: 'Servicio'),
                    'logo_url' => $userLogoUrl ?: null,
                    'cover_image_url' => $cover && ! empty($cover->url) ? asset('img/uploads/' . ltrim((string) $cover->url, '/')) : null,
                    'average_stars' => (float) ($ratingsSummaryByProvider[$providerUserId]['average_stars'] ?? 0.0),
                    'ratings_count' => (int) ($ratingsSummaryByProvider[$providerUserId]['ratings_count'] ?? 0),
                    'lat' => $resolvedCoordinates->latitude,
                    'lng' => $resolvedCoordinates->longitude,
                    'latitude' => $resolvedCoordinates->latitude,
                    'longitude' => $resolvedCoordinates->longitude,
                    'address' => $resolvedAddress,
                    'city' => $resolvedCity,
                    'province' => $resolvedProvince,
                    'phone' => $phone,
                    'whatsapp_phone' => $whatsappPhone ?: null,
                    'whatsapp_url' => $whatsappUrl,
                    'service_type_ids' => $serviceTypeLinks,
                    'service_types' => $serviceTypes,
                ];

                continue;
            }

            $mergedTypeIds = collect(array_merge(
                $dataProviders[$providerUserId]['service_type_ids'],
                $serviceTypeLinks
            ))->map(fn ($id) => (int) $id)->unique()->values()->all();

            $dataProviders[$providerUserId]['service_type_ids'] = $mergedTypeIds;
            $dataProviders[$providerUserId]['service_types'] = collect($mergedTypeIds)
                ->map(fn ($typeId) => $serviceTypesById->get((int) $typeId))
                ->filter()
                ->map(fn ($type) => ['id' => (int) $type->id, 'name' => $type->name])
                ->values()
                ->all();

            if (empty($dataProviders[$providerUserId]['cover_image_url']) && $cover && ! empty($cover->url)) {
                $dataProviders[$providerUserId]['cover_image_url'] = asset('img/uploads/' . ltrim((string) $cover->url, '/'));
            }
        }

        $dataProperties = array_values($dataProviders);

        return response()->json([
            'success' => true,
            'data' => $dataProperties,
            'meta' => null,
            'message' => null,
            'errors' => null,
            // Backward compatibility
            'status' => 200,
        ]);
    }

    public function publicServiceDetail(Request $request, string $id)
    {
        $service = Service::query()->where('id', (int) $id)->first();
        if (! $service) {
            return $this->errorResponse('Servicio no encontrado', 404);
        }

        $user = User::find((int) $service->user_id);
        $serviceAddress = ServiceAddress::query()->where('service_id', (int) $service->id)->first();
        $userAddress = UserAddress::query()->where('user_id', (int) $service->user_id)->first();
        $address = $serviceAddress ?: $userAddress;

        $cover = CoverImage::query()->where('service_id', (int) $service->id)->first();
        $video = Video::query()->where('service_id', (int) $service->id)->first();
        $moreImages = MoreImage::query()->where('service_id', (int) $service->id)->get();

        $serviceTypeLinks = ServiceTypeLink::query()->where('service_id', (int) $service->id)->pluck('service_type_id')->map(fn ($typeId) => (int) $typeId)->all();
        $serviceTypes = empty($serviceTypeLinks) ? [] : ServiceType::query()->whereIn('id', $serviceTypeLinks)->orderBy('name')->get(['id', 'name'])->map(fn ($t) => ['id' => (int) $t->id, 'name' => $t->name])->values()->all();

        $ratingSummary = app(ServiceRatingService::class)->providerRatingSummary((int) $service->user_id);

        $phone = $user?->phone ?: ($user?->mobile_phone ?: $user?->landline_phone);
        $cleanPhone = preg_replace('/[^0-9+]/', '', (string) $phone);
        $whatsappPhone = ltrim((string) $cleanPhone, '+');
        $whatsappUrl = ! empty($whatsappPhone) ? 'https://wa.me/' . $whatsappPhone . '?text=' . urlencode('Hola, me interesa tu servicio') : null;

        $gallery = $moreImages->map(function ($img) {
            return [
                'id' => (int) $img->id,
                'url' => ! empty($img->url) ? asset('img/uploads/' . ltrim((string) $img->url, '/')) : null,
            ];
        })->filter(fn ($item) => ! empty($item['url']))->values()->all();

        $title = $service->title;
        if (empty($title) && $user) {
            $title = $user->user_name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        }

        $payload = [
            'id' => (int) $service->id,
            'provider_user_id' => (int) $service->user_id,
            'title' => $title ?: 'Servicio',
            'description' => $service->description,
            'availability' => $service->availability,
            'page_url' => $service->page_url,
            'updated_at' => optional($service->updated_at)?->toISOString(),
            'logo_url' => $user && ! empty($user->photo) ? asset('img/photo_profile/' . ltrim((string) $user->photo, '/')) : null,
            'cover_image_url' => $cover && ! empty($cover->url) ? asset('img/uploads/' . ltrim((string) $cover->url, '/')) : null,
            'video_url' => $video && ! empty($video->url) ? asset('video/uploads/' . ltrim((string) $video->url, '/')) : null,
            'address' => $address?->address,
            'city' => $address?->city,
            'province' => $address?->province,
            'postal_code' => $address?->postal_code,
            'country' => $address?->country,
            'lat' => $address?->latitude,
            'lng' => $address?->longitude,
            'latitude' => $address?->latitude,
            'longitude' => $address?->longitude,
            'average_stars' => (float) ($ratingSummary['average_stars'] ?? 0.0),
            'ratings_count' => (int) ($ratingSummary['ratings_count'] ?? 0),
            'phone' => $phone,
            'whatsapp_phone' => $whatsappPhone ?: null,
            'whatsapp_url' => $whatsappUrl,
            'email' => $user?->email,
            'service_types' => $serviceTypes,
            'gallery' => $gallery,
        ];

        return $this->successResponse($payload, null, null, 200, ['status' => 200]);
    }

    public function deleteMoreImage(Request $request)
    {
        if (! Auth::check()) {
            return $this->errorResponse('No autenticado', 403, null, ['status' => 403]);
        }

        $id = $request->query('id');
        if (! empty($id)) {
            MoreImage::where('id', $id)->delete();
        }

        return $this->successResponse(null, null, 'Imagen eliminada', 200, ['status' => 200]);
    }

    public function visitorRegister(Request $request)
    {
        $postId = $request->post('post_id');
        $ipAddress = $request->ip();
        $referer = $request->headers->get('referer');

        $postVisit = PostVisit::create([
            'post_id' => $postId,
            'ip_address' => $ipAddress,
            'referer' => $referer,
            'contacted' => null,
        ]);

        return $this->successResponse(['id' => $postVisit->id], null, 'Visita registrada', 200, ['status' => 200, 'id' => $postVisit->id]);
    }

    public function visitorContactedUpdate(Request $request)
    {
        $rowId = $request->post('row_id');
        if (! empty($rowId)) {
            PostVisit::where('id', $rowId)->update(['contacted' => 1]);
            return $this->successResponse(['post_id' => $rowId], null, 'Visita marcada como contactada', 200, ['status' => 200, 'post_id' => $rowId]);
        }

        return $this->errorResponse('No se pudo actualizar la visita', 503, null, ['status' => 503, 'post_id' => $rowId]);
    }

    public function verifyTokenGoogleFloat(Request $request)
    {
        $token = $request->post('credential');
        if (empty($token)) {
            return $this->errorResponse('Token missing', 400, null, ['error' => 'Token missing']);
        }

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $token,
        ]);

        if (! $response->ok()) {
            return $this->errorResponse('Token invalido', 400, null, ['error' => 'Token invalido']);
        }

        $payload = $response->json();
        $clientId = config('services.google.client_id');
        if (! empty($clientId) && isset($payload['aud']) && $payload['aud'] !== $clientId) {
            return $this->errorResponse('Token invalido', 400, null, ['error' => 'Token invalido']);
        }

        $userData = [
            'id' => $payload['sub'] ?? null,
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? 'Usuario Google',
            'picture' => $payload['picture'] ?? '',
            'logged_in' => true,
        ];

        if (! empty($userData['email'])) {
            UserFree::firstOrCreate(
                ['email' => $userData['email']],
                ['name' => $userData['name'], 'photo' => $userData['picture']]
            );
        }

        return $this->successResponse(['user' => $userData], null, null, 200, ['user' => $userData]);
    }

    public function sendEmailContactUser(Request $request)
    {
        $userEmail = $request->post('user_email');
        $userName = $request->post('user_name');
        $providerEmail = $request->post('provider_email');
        $message = $request->post('message');
        $propertyLink = $request->post('property_link');
        $propertyId = $request->post('property_id');

        $safeUserName = htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8');
        $safeUserEmail = htmlspecialchars((string) $userEmail, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars((string) $propertyLink, ENT_QUOTES, 'UTF-8');

        $template = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>Correo Electronico de Consulta de Propiedad</title>'
            . '<style>a{color:blue;}body{font-family: Arial, sans-serif;margin:0;padding:0;background-color:#f4f4f4;color:#333;line-height:1.6;}'
            . '.container{max-width:600px;margin:20px auto;padding:20px;background-color:#fff;border-radius:8px;box-shadow:0 4px 8px rgba(0,0,0,0.1);}p{margin-bottom:16px;}'
            . '.datos-contacto{margin-bottom:20px;padding:15px;border:1px solid #ddd;border-radius:8px;background-color:#f9f9f9;}'
            . '.datos-contacto strong{color:#0078d7;}</style></head><body><div class="container"><div class="datos-contacto">'
            . '<p><strong>Usuario:</strong> ' . $safeUserName . '</p>'
            . '<p><strong>Correo:</strong> ' . $safeUserEmail . '</p>'
            . '<p><strong>Mensaje:</strong> ' . $safeMessage . '</p>'
            . '<a href="' . $safeLink . '" target="_blank">' . $safeLink . '</a>'
            . '</div></div></body></html>';

        $emailService = app(EmailService::class);
        $sent = $emailService->send((string) $providerEmail, 'Un usuario se ha contactado contigo', $template);

        if (! empty($userEmail)) {
            $userFree = UserFree::where('email', $userEmail)->first();
            if ($userFree) {
                PsMessagesReceived::create([
                    'property_id' => $propertyId,
                    'user_free_id' => $userFree->id,
                    'message' => $message,
                ]);
            }
        }

        if (! $sent) {
            return $this->errorResponse('No se pudo enviar el correo', 500, null, ['status' => 500]);
        }

        return $this->successResponse(null, null, 'Correo enviado', 200, ['status' => 200]);
    }

    public function sendEmailShare(Request $request)
    {
        $userEmails = (string) $request->query('user_emails');
        $propertyLink = (string) $request->query('property_link');

        $safeLink = htmlspecialchars($propertyLink, ENT_QUOTES, 'UTF-8');
        $template = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>Mira este inmueble</title>'
            . '<style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;background-color:#f4f4f4;margin:0;padding:0;}'
            . '.container{max-width:600px;margin:20px auto;padding:20px;background-color:#ffffff;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.1);}'
            . '.header{text-align:center;padding-bottom:20px;border-bottom:1px solid #eee;}'
            . '.header h1{color:#0056b3;font-size:24px;margin:0;}.content{padding:20px 0;}'
            . '.content p{margin-bottom:15px;}.button-container{text-align:center;padding:20px 0;}'
            . '.button{display:inline-block;padding:12px 25px;background-color:#63c4ca;color:#ffffff;text-decoration:none;border-radius:5px;font-size:16px;}'
            . '.footer{text-align:center;padding-top:20px;border-top:1px solid #eee;font-size:12px;color:#777;}</style></head><body>'
            . '<div class="container"><div class="header"><h1>Mira este inmueble</h1></div><div class="content">'
            . '<p>Hola,</p><p>Queria compartir contigo un inmueble que podria interesarte.</p>'
            . '<p>Puedes ver todos los detalles en el siguiente enlace:</p>'
            . '<div class="button-container"><a href="' . $safeLink . '" class="button" style="color:white;">Ver Inmueble</a></div>'
            . '<p>Saludos,</p></div><div class="footer"><p>Este correo ha sido enviado porque un usuario compartio un inmueble.</p>'
            . '<p>&copy; ' . date('Y') . ' Kconecta.</p></div></div></body></html>';

        $emailService = app(EmailService::class);
        $emails = array_filter(array_map('trim', explode(',', $userEmails)));
        foreach ($emails as $email) {
            $emailService->send($email, 'Mira este inmueble', $template);
        }

        return $this->successResponse(null, null, 'Emails sent successfully', 200, ['status' => 'success']);
    }

    public function propertyStatsConfig(Request $request)
    {
        $propertyId = $request->post('_i');
        $ipAddress = $request->ip();

        $fieldModelMap = [
            'views_detail' => PsViewsDetail::class,
            'whatsapp_clicks' => PsWhatsappClicks::class,
            'views_search' => PsViewsSearch::class,
            'owner_calls' => PsOwnerCalls::class,
            'shared_facebook' => PsSharedFacebook::class,
            'link_copied' => PsLinkCopied::class,
            'shared_friends' => PsSharedFriends::class,
            'email_owner' => PsEmailOwner::class,
            'saved_favorite' => PsSavedFavorite::class,
        ];

        foreach ($fieldModelMap as $field => $modelClass) {
            if ($request->post($field)) {
                $existing = $modelClass::query()
                    ->where('property_id', $propertyId)
                    ->where('ip_address', $ipAddress)
                    ->first();

                if (! $existing) {
                    $modelClass::create([
                        'property_id' => $propertyId,
                        'ip_address' => $ipAddress,
                        'counter' => 1,
                    ]);
                }

                break;
            }
        }

        return $this->successResponse(null, null, 'Estadistica registrada', 200, ['status' => 200]);
    }

    public function serviceStatsRegisterVisit(Request $request)
    {
        $providerUserId = (int) $request->post('provider_user_id');
        $serviceId = (int) $request->post('service_id');

        if ($providerUserId <= 0) {
            return $this->errorResponse('provider_user_id invalido', 422, ['provider_user_id' => ['El proveedor es obligatorio.']]);
        }

        DB::table('service_profile_visits')->insert([
            'provider_user_id' => $providerUserId,
            'service_id' => $serviceId > 0 ? $serviceId : null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) ($request->userAgent() ?? ''), 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->successResponse(null, null, 'Visita registrada', 200, ['status' => 200]);
    }

    public function serviceStatsRegisterContactClick(Request $request)
    {
        $providerUserId = (int) $request->post('provider_user_id');
        $serviceId = (int) $request->post('service_id');
        $channel = trim((string) $request->post('channel', 'whatsapp'));

        if ($providerUserId <= 0) {
            return $this->errorResponse('provider_user_id invalido', 422, ['provider_user_id' => ['El proveedor es obligatorio.']]);
        }

        if ($channel === '') {
            $channel = 'whatsapp';
        }

        DB::table('service_contact_clicks')->insert([
            'provider_user_id' => $providerUserId,
            'service_id' => $serviceId > 0 ? $serviceId : null,
            'channel' => substr($channel, 0, 40),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) ($request->userAgent() ?? ''), 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->successResponse(null, null, 'Click registrado', 200, ['status' => 200]);
    }
}
