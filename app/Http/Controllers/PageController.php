<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\ContactOption;
use App\Models\Country;
use App\Models\CoverImage;
use App\Models\EmissionsRating;
use App\Models\EnergyClass;
use App\Models\Equipment;
use App\Models\Equipments;
use App\Models\Facade;
use App\Models\Feature;
use App\Models\Features;
use App\Models\MoreImage;
use App\Models\NearestMunicipalityDistance;
use App\Models\Orientation;
use App\Models\Orientations;
use App\Models\Plant;
use App\Models\PlazaCapacity;
use App\Models\PostVisit;
use App\Models\PowerConsumptionRating;
use App\Models\Property;
use App\Models\PropertyAddress;
use App\Models\Province;
use App\Models\PsViewsDetail;
use App\Models\PsViewsSearch;
use App\Models\ReasonForSale;
use App\Models\RentalType;
use App\Models\Service;
use App\Models\ServiceAddress;
use App\Models\ServiceType;
use App\Models\StateConservation;
use App\Models\TerrainUse;
use App\Models\TerrainQualification;
use App\Models\TerrainQualifications;
use App\Models\Type;
use App\Models\TypeFloor;
use App\Models\TypeHeating;
use App\Models\TypeOfTerrain;
use App\Models\TypesFloors;
use App\Models\Typology;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Video;
use App\Models\VisibilityInPortals;
use App\Models\WheeledAccess;
use App\Services\ProviderServiceTypeService;
use App\Services\ServiceRatingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PageController extends Controller
{
    private function normalizeWebsiteUrl(?string $url): ?string
    {
        $normalized = trim((string) $url);
        if ($normalized === '') {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        return 'https://' . ltrim($normalized, '/');
    }

    public function index()
    {
        Carbon::setLocale('es');

        $city = City::orderBy('name')->get()->toArray();
        $serviceType = ServiceType::orderBy('name')->get()->toArray();

        $properties = Property::query()
            ->where('state_id', 4)
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(function (Property $property) {
                $item = $property->toArray();

                $item['updated_at_text'] = $this->formatUpdatedAt($property->updated_at);
                $item['type_name'] = Type::find($property->type_id)?->name ?? '';
                $item['category_name'] = Category::find($property->category_id)?->name ?? '';

                $coverImage = CoverImage::where('property_id', $property->id)->first();
                $item['cover_image'] = $coverImage ? $coverImage->toArray() : ['url' => ''];

                $user = User::find($property->user_id);
                $item['user_name'] = $user?->user_name ?? '';

                $item['post_visits'] = PostVisit::where('post_id', $property->id)->count();

                $item['state_conservation'] = $this->wrapSingle(StateConservation::find($property->state_conservation_id));
                $item['facade'] = $this->wrapSingle(Facade::find($property->facade_id));
                $item['nearest_municipality_distance'] = $this->wrapSingle(
                    NearestMunicipalityDistance::find($property->nearest_municipality_distance_id)
                );
                $item['type_of_terrain'] = $this->wrapSingle(TypeOfTerrain::find($property->type_of_terrain_id));
                $item['wheeled_access'] = $this->wrapSingle(WheeledAccess::find($property->wheeled_access_id));

                return $item;
            })
            ->values()
            ->all();

        return view('page.index', [
            'property' => $properties,
            'serviceType' => $serviceType,
            'city' => $city,
        ]);
    }

    public function providerLanding()
    {
        $registrationFormStartedAt = now()->timestamp;
        session(['registration_form_started_at' => $registrationFormStartedAt]);

        return view('page.provider_landing', [
            'registrationFormStartedAt' => $registrationFormStartedAt,
        ]);
    }

    public function resultAll()
    {
        Carbon::setLocale('es');

        $request = request();

        $mode = $request->query('mode');
        $address = $request->query('address');
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
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');
        $zoom = $request->query('zoom');

        $propertiesQuery = Property::query()->where('state_id', 4);

        if (! empty($city) || ! empty($province)) {
            $addressQuery = PropertyAddress::query();
            if (! empty($province) && empty($city)) {
                $addressQuery->where('province', trim($province));
            } elseif (! empty($city)) {
                $addressQuery->where('city', trim($city));
            }

            $ids = $addressQuery->pluck('property_id')->map(fn ($id) => (int) $id)->all();
            if (! empty($ids)) {
                $propertiesQuery->whereIn('id', $ids);
            } else {
                $propertiesQuery->where('id', 0);
            }
        } elseif (! empty($address)) {
            $addressParts = explode(',', $address);
            $addressSeed = trim($addressParts[0]);

            $addressQuery = PropertyAddress::query()
                ->where('address', 'like', '%' . trim($address) . '%')
                ->orWhere('address', 'like', '%' . $addressSeed . '%')
                ->orWhere('province', 'like', '%' . $addressSeed . '%')
                ->orWhere('city', 'like', '%' . $addressSeed . '%');

            $ids = $addressQuery->pluck('property_id')->map(fn ($id) => (int) $id)->all();
            if (! empty($ids)) {
                $propertiesQuery->whereIn('id', $ids);
            } else {
                $propertiesQuery->where('id', 0);
            }
        } else {
            $addressQuery = PropertyAddress::query()
                ->where('address', 'like', '%barcelona%')
                ->orWhere('province', 'like', '%barcelona%')
                ->orWhere('city', 'like', '%barcelona%');

            $ids = $addressQuery->pluck('property_id')->map(fn ($id) => (int) $id)->all();
            if (! empty($ids)) {
                $propertiesQuery->whereIn('id', $ids);
            } else {
                $propertiesQuery->where('id', 0);
            }
        }

        if (! empty($categoryId)) {
            $propertiesQuery->where('category_id', $categoryId);
            $priceField = ((int) $categoryId === 1) ? 'rental_price' : 'sale_price';
            if (! empty($pMin)) {
                $propertiesQuery->where($priceField, '>=', $pMin);
            }
            if (! empty($pMax)) {
                $propertiesQuery->where($priceField, '<=', $pMax);
            }
        } else {
            if (! empty($pMin)) {
                $propertiesQuery->where(function ($query) use ($pMin) {
                    $query->where('sale_price', '>=', $pMin)
                        ->orWhere('rental_price', '>=', $pMin);
                });
            }
            if (! empty($pMax)) {
                $propertiesQuery->where(function ($query) use ($pMax) {
                    $query->where('sale_price', '<=', $pMax)
                        ->orWhere('rental_price', '<=', $pMax);
                });
            }
        }

        if (! empty($numMinBathrooms)) {
            $propertiesQuery->where('bathrooms', '>=', $numMinBathrooms);
        }
        if (! empty($numMinBedrooms)) {
            $propertiesQuery->where('bedrooms', '>=', $numMinBedrooms);
        }
        if (! empty($builtMin)) {
            $propertiesQuery->where('meters_built', '>=', $builtMin);
        }
        if (! empty($builtMax)) {
            $propertiesQuery->where('meters_built', '<=', $builtMax);
        }
        if (! empty($typeId)) {
            $propertiesQuery->where('type_id', $typeId);
        }

        $quantityDataView = 15;
        $numberPosition = (int) ($request->query('page') ?: 1);

        $propertiesAll = $propertiesQuery->orderByDesc('id')->get();
        $quantityBlockNav = (int) round($propertiesAll->count() / $quantityDataView);
        $propertiesPage = $propertiesAll
            ->slice(($quantityDataView * $numberPosition) - $quantityDataView, $quantityDataView)
            ->values();

        $quantity = $propertiesPage->count();

        $provinces = PropertyAddress::query()
            ->select('property_address.province', DB::raw('COUNT(*) as total'))
            ->join('property', 'property.id', '=', 'property_address.property_id')
            ->where('property.state_id', 4)
            ->groupBy('property_address.province')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        $cities = PropertyAddress::query()
            ->select('property_address.city', DB::raw('COUNT(*) as total'))
            ->join('property', 'property.id', '=', 'property_address.property_id')
            ->where('property.state_id', 4)
            ->groupBy('property_address.city')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        $provinceCitiesList = PropertyAddress::query()
            ->select('property_address.province', 'property_address.city', DB::raw('COUNT(*) as total'))
            ->join('property', 'property.id', '=', 'property_address.property_id')
            ->where('property.state_id', 4)
            ->groupBy('property_address.province', 'property_address.city')
            ->orderBy('property_address.province')
            ->orderByDesc('total')
            ->get();

        $provinceCities = [];
        foreach ($provinceCitiesList as $row) {
            $provinceKey = $row->province;
            $provinceCities[$provinceKey][] = [
                'city' => $row->city,
                'total' => $row->total,
            ];
        }

        $updatedProperties = [];
        foreach ($propertiesPage as $property) {
            $item = $property->toArray();
            $item['updated_at_text'] = $this->formatUpdatedAt($property->updated_at);
            $item['type_name'] = Type::find($property->type_id)?->name ?? '';
            $item['category_name'] = Category::find($property->category_id)?->name ?? '';
            $coverImage = CoverImage::where('property_id', $property->id)->first();
            $item['cover_image'] = $coverImage ? $coverImage->toArray() : ['url' => ''];

            $user = User::find($property->user_id);
            $item['user_name'] = $user?->user_name ?? '';

            $item['state_conservation'] = $this->wrapSingle(StateConservation::find($property->state_conservation_id));
            $item['facade'] = $this->wrapSingle(Facade::find($property->facade_id));
            $item['nearest_municipality_distance'] = $this->wrapSingle(
                NearestMunicipalityDistance::find($property->nearest_municipality_distance_id)
            );
            $item['type_of_terrain'] = $this->wrapSingle(TypeOfTerrain::find($property->type_of_terrain_id));
            $item['wheeled_access'] = $this->wrapSingle(WheeledAccess::find($property->wheeled_access_id));

            $updatedProperties[] = $item;

            $exists = PsViewsSearch::query()
                ->where('property_id', $property->id)
                ->where('ip_address', $request->ip())
                ->first();

            if (! $exists) {
                PsViewsSearch::create([
                    'property_id' => $property->id,
                    'ip_address' => $request->ip(),
                    'counter' => 1,
                ]);
            }
        }

        return view('page.result_all', [
            'number_position' => $numberPosition,
            'quantity_block_nav' => $quantityBlockNav,
            'properties' => $updatedProperties,
            'quantity' => $quantity,
            'address' => $address,
            'type_id' => $typeId,
            'category_id' => $categoryId,
            'p_max' => $pMax,
            'p_min' => $pMin,
            'built_min' => $builtMin,
            'built_max' => $builtMax,
            'n_ber' => $numMinBedrooms,
            'n_bar' => $numMinBathrooms,
            'mode' => $mode,
            'provinces' => $provinces,
            'cities' => $cities,
            'provinceCities' => $provinceCities,
            'city' => $city,
            'province' => $province,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'zoom' => $zoom,
        ]);
    }

    public function resultAllServices()
    {
        Carbon::setLocale('es');

        $request = request();

        $sti = $request->query('sti');
        $mode = $request->query('mode');
        $address = $request->query('address');

        $city = $request->query('city');
        $province = $request->query('province');
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');
        $zoom = $request->query('zoom');

        $providerIdsForTypes = [];

        if (! empty($sti)) {
            if (is_array($sti)) {
                $sti = array_map('intval', $sti);
            } else {
                $sti = [(int) $sti];
            }

            $providerIdsForTypes = app(ProviderServiceTypeService::class)->providerIdsForTypeIds($sti);
        }

        $providersQuery = User::query()
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER);
        if (! empty($providerIdsForTypes)) {
            $providersQuery->whereIn('id', $providerIdsForTypes);
        }
        if (Schema::hasColumn('user', 'is_active')) {
            $providersQuery->where('is_active', 1);
        }

        if (! empty($city) || ! empty($province)) {
            $addressQuery = UserAddress::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '<>', '')
                ->where('longitude', '<>', '');
            if (! empty($province) && empty($city)) {
                $addressQuery->where('province', trim($province));
            } elseif (! empty($city)) {
                $addressQuery->where('city', trim($city));
            }

            $ids = $addressQuery->pluck('user_id')->map(fn ($id) => (int) $id)->all();
            if (! empty($ids)) {
                $providersQuery->whereIn('id', $ids);
            } else {
                $providersQuery->where('id', 0);
            }
        } elseif (! empty($address)) {
            $addressParts = explode(',', $address);
            $addressSeed = trim($addressParts[0]);

            $ids = UserAddress::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '<>', '')
                ->where('longitude', '<>', '')
                ->where(function ($query) use ($address, $addressSeed) {
                    $query->where('address', 'like', '%' . trim($address) . '%')
                        ->orWhere('address', 'like', '%' . $addressSeed . '%')
                        ->orWhere('province', 'like', '%' . $addressSeed . '%')
                        ->orWhere('city', 'like', '%' . $addressSeed . '%');
                })
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (! empty($ids)) {
                $providersQuery->whereIn('id', $ids);
            } else {
                $providersQuery->where('id', 0);
            }
        } else {
            $ids = UserAddress::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '<>', '')
                ->where('longitude', '<>', '')
                ->where(function ($query) {
                    $query->where('address', 'like', '%barcelona%')
                        ->orWhere('province', 'like', '%barcelona%')
                        ->orWhere('city', 'like', '%barcelona%');
                })
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (! empty($ids)) {
                $providersQuery->whereIn('id', $ids);
            } else {
                $providersQuery->where('id', 0);
            }
        }

        $quantityDataView = 15;
        $numberPosition = (int) ($request->query('page') ?: 1);

        $providersAll = $providersQuery->orderByDesc('id')->get();
        $quantityBlockNav = (int) ceil(max(1, $providersAll->count()) / $quantityDataView);
        $providersPage = $providersAll
            ->slice(($quantityDataView * $numberPosition) - $quantityDataView, $quantityDataView)
            ->values();

        $quantity = $providersPage->count();
        $matchedProviderLocations = [];
        foreach ($providersAll as $provider) {
            $providerUserId = (int) $provider->id;
            $userAddress = UserAddress::query()
                ->where('user_id', $providerUserId)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '<>', '')
                ->where('longitude', '<>', '')
                ->first();

            if (! $userAddress) {
                continue;
            }

            $matchedProviderLocations[$providerUserId] = [
                'province' => trim((string) ($userAddress->province ?? '')),
                'city' => trim((string) ($userAddress->city ?? '')),
            ];
        }

        $provinceTotals = [];
        $cityTotals = [];
        $provinceCities = [];

        foreach ($matchedProviderLocations as $location) {
            $provinceName = $location['province'];
            $cityName = $location['city'];

            if ($provinceName !== '') {
                $provinceTotals[$provinceName] = ($provinceTotals[$provinceName] ?? 0) + 1;
            }

            if ($cityName !== '') {
                $cityTotals[$cityName] = ($cityTotals[$cityName] ?? 0) + 1;
            }

            if ($provinceName !== '' && $cityName !== '') {
                $provinceCities[$provinceName][$cityName] = ($provinceCities[$provinceName][$cityName] ?? 0) + 1;
            }
        }

        arsort($provinceTotals);
        arsort($cityTotals);
        ksort($provinceCities);

        $provinces = collect($provinceTotals)
            ->map(fn ($total, $provinceName) => ['province' => $provinceName, 'total' => $total])
            ->values()
            ->all();

        $cities = collect($cityTotals)
            ->map(fn ($total, $cityName) => ['city' => $cityName, 'total' => $total])
            ->values()
            ->all();

        $provinceCities = collect($provinceCities)
            ->map(function ($citiesByProvince) {
                arsort($citiesByProvince);

                return collect($citiesByProvince)
                    ->map(fn ($total, $cityName) => [
                        'city' => $cityName,
                        'total' => $total,
                    ])
                    ->values()
                    ->all();
            })
            ->all();

        $updatedServices = [];
        foreach ($providersPage as $provider) {
            $item = [
                'id' => (int) $provider->id,
                'updated_at_text' => $this->formatUpdatedAt($provider->updated_at),
                'cover_image' => ['url' => ''],
                'user' => [$provider->toArray()],
                'user_address' => UserAddress::where('user_id', $provider->id)->get()->toArray(),
            ];

            $serviceTypeLinks = app(ProviderServiceTypeService::class)->typeIdsForProvider((int) $provider->id);
            $item['specialties'] = ! empty($serviceTypeLinks)
                ? ServiceType::whereIn('id', $serviceTypeLinks)->get()->toArray()
                : [];

            $updatedServices[] = $item;
        }

        $serviceTypes = ServiceType::orderBy('name')->get()->toArray();

        return view('page.result_all_service', [
            'service_type' => $serviceTypes,
            'number_position' => $numberPosition,
            'quantity_block_nav' => $quantityBlockNav,
            'properties' => $updatedServices,
            'quantity' => $quantity,
            'address' => $address,
            'mode' => $mode,
            'provinces' => $provinces,
            'cities' => $cities,
            'provinceCities' => $provinceCities,
            'city' => $city,
            'province' => $province,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'zoom' => $zoom,
            'sti' => $sti,
        ]);
    }

    public function resultMaps()
    {
        return view('page.placeholder', ['title' => 'Results Map']);
    }

    public function resultProvider(string $id)
    {
        Carbon::setLocale('es');

        $provider = User::query()
            ->where('id', (int) $id)
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
            ->first();

        if (! $provider) {
            return redirect('/');
        }

        $profileAddress = UserAddress::query()->where('user_id', (int) $provider->id)->first();
        $services = Service::query()
            ->where('user_id', (int) $provider->id)
            ->orderByDesc('id')
            ->get();

        $serviceIds = $services->pluck('id')->map(fn ($serviceId) => (int) $serviceId)->all();
        $serviceAddresses = empty($serviceIds)
            ? collect()
            : ServiceAddress::query()->whereIn('service_id', $serviceIds)->get()->keyBy('service_id');
        $coverImages = CoverImage::query()
            ->where('user_id', (int) $provider->id)
            ->orWhere(function ($query) use ($serviceIds) {
                $query->whereNull('user_id')->whereIn('service_id', ! empty($serviceIds) ? $serviceIds : [0]);
            })
            ->get();
        $videos = Video::query()
            ->where('user_id', (int) $provider->id)
            ->orWhere(function ($query) use ($serviceIds) {
                $query->whereNull('user_id')->whereIn('service_id', ! empty($serviceIds) ? $serviceIds : [0]);
            })
            ->get();
        $moreImages = MoreImage::query()
            ->where('user_id', (int) $provider->id)
            ->orWhere(function ($query) use ($serviceIds) {
                $query->whereNull('user_id')->whereIn('service_id', ! empty($serviceIds) ? $serviceIds : [0]);
            })
            ->orderBy('id')
            ->get();
        $providerTypeIds = app(ProviderServiceTypeService::class)->typeIdsForProvider((int) $provider->id);

        $coverByServiceId = $coverImages->whereNull('user_id')->keyBy('service_id');
        $providerCover = $coverImages->firstWhere('user_id', (int) $provider->id);
        $videoByServiceId = $videos->whereNull('user_id')->keyBy('service_id');
        $providerVideo = $videos->firstWhere('user_id', (int) $provider->id);
        $moreImagesByService = $moreImages->whereNull('user_id')->groupBy('service_id');
        $providerMoreImages = $moreImages->where('user_id', (int) $provider->id)->values();
        $typeLinksByService = collect($services)->mapWithKeys(fn ($service) => [(int) $service->id => $providerTypeIds]);
        $providerTypeLinks = collect($providerTypeIds);

        $allServiceTypeIds = collect($providerTypeIds)
            ->map(fn ($typeId) => (int) $typeId)
            ->unique()
            ->values()
            ->all();

        $serviceTypesById = empty($allServiceTypeIds)
            ? collect()
            : ServiceType::query()->whereIn('id', $allServiceTypeIds)->get()->keyBy('id');

        $providerDisplayName = trim(($provider->first_name ?? '') . ' ' . ($provider->last_name ?? ''));
        if ($providerDisplayName === '') {
            $providerDisplayName = trim((string) ($provider->user_name ?? ''));
        }
        if ($providerDisplayName === '') {
            $providerDisplayName = trim((string) ($provider->email ?? ''));
        }
        if ($providerDisplayName === '') {
            $providerDisplayName = 'Proveedor';
        }

        $providerPhone = $provider->phone
            ?? ($provider->mobile_phone ?? null)
            ?? ($provider->landline_phone ?? null);
        $providerWhatsappPhone = preg_replace('/\D+/', '', (string) $providerPhone);
        $providerWhatsappLink = $providerWhatsappPhone !== ''
            ? 'https://wa.me/' . $providerWhatsappPhone . '?text=' . urlencode('Hola, me interesa tu servicio')
            : '';

        $profileAddressParts = array_values(array_filter([
            $profileAddress?->address,
            $profileAddress?->city,
            $profileAddress?->province,
            $profileAddress?->country,
        ], fn ($value) => trim((string) $value) !== ''));
        $profileAddressLabel = ! empty($profileAddressParts)
            ? implode(', ', $profileAddressParts)
            : trim((string) ($provider->address ?? ''));

        $providerProfileTitle = trim((string) ($provider->provider_title ?? ''));
        if ($providerProfileTitle === '') {
            $providerProfileTitle = trim((string) ($provider->provider_description ?? ''));
        }
        if ($providerProfileTitle === '') {
            $providerProfileTitle = $providerDisplayName;
        }

        $serviceCards = [];
        foreach ($services as $service) {
            $serviceAddress = $serviceAddresses->get((int) $service->id);
            $addressParts = array_values(array_filter([
                $serviceAddress?->address,
                $serviceAddress?->city,
                $serviceAddress?->province,
                $serviceAddress?->country,
            ], fn ($value) => trim((string) $value) !== ''));
            $addressLabel = ! empty($addressParts) ? implode(', ', $addressParts) : $profileAddressLabel;

            $typeNames = collect($typeLinksByService->get((int) $service->id, []))
                ->map(fn ($typeId) => $serviceTypesById->get((int) $typeId)?->name)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $cover = $coverByServiceId->get((int) $service->id);
            $video = $videoByServiceId->get((int) $service->id);

            $serviceCards[] = [
                'id' => (int) $service->id,
                'title' => trim((string) ($service->title ?? '')) !== '' ? (string) $service->title : $providerProfileTitle,
                'description' => (string) ($service->description ?? ''),
                'availability' => (string) ($service->availability ?? ''),
                'page_url' => (string) ($service->page_url ?? ''),
                'updated_at_text' => $this->formatUpdatedAt($service->updated_at),
                'address_label' => $addressLabel,
                'cover_image_url' => $cover && ! empty($cover->url)
                    ? asset('img/uploads/' . ltrim((string) $cover->url, '/'))
                    : null,
                'video_url' => $video && ! empty($video->url)
                    ? asset('video/uploads/' . ltrim((string) $video->url, '/'))
                    : null,
                'specialties' => $typeNames,
                'latitude' => $serviceAddress?->latitude ?: ($profileAddress?->latitude ?? ''),
                'longitude' => $serviceAddress?->longitude ?: ($profileAddress?->longitude ?? ''),
            ];
        }

        $primaryService = $serviceCards[0] ?? null;
        $primaryServiceId = $primaryService['id'] ?? null;
        $galleryImages = [];
        if ($providerCover && ! empty($providerCover->url)) {
            $galleryImages[] = asset('img/uploads/' . ltrim((string) $providerCover->url, '/'));
        } elseif ($primaryService && ! empty($primaryService['cover_image_url'])) {
            $galleryImages[] = $primaryService['cover_image_url'];
        }
        if ($providerMoreImages->isNotEmpty()) {
            foreach ($providerMoreImages as $image) {
                $file = trim((string) ($image->url ?? ''));
                if ($file === '') {
                    continue;
                }

                $url = asset('img/uploads/' . ltrim($file, '/'));
                if (! in_array($url, $galleryImages, true)) {
                    $galleryImages[] = $url;
                }
            }
        } elseif ($primaryServiceId) {
            foreach ($moreImagesByService->get((int) $primaryServiceId, collect()) as $image) {
                $file = trim((string) ($image->url ?? ''));
                if ($file === '') {
                    continue;
                }

                $url = asset('img/uploads/' . ltrim($file, '/'));
                if (! in_array($url, $galleryImages, true)) {
                    $galleryImages[] = $url;
                }
            }
        }

        foreach ($serviceCards as $serviceCard) {
            if (empty($serviceCard['cover_image_url'])) {
                continue;
            }
            if (! in_array($serviceCard['cover_image_url'], $galleryImages, true)) {
                $galleryImages[] = $serviceCard['cover_image_url'];
            }
        }

        if (empty($galleryImages)) {
            $galleryImages[] = asset('img/image-icon-1280x960.png');
        }

        $providerTypeNames = $providerTypeLinks
            ->map(fn ($typeId) => $serviceTypesById->get((int) $typeId)?->name)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $aggregatedSpecialties = ! empty($providerTypeNames)
            ? $providerTypeNames
            : collect($serviceCards)->pluck('specialties')->flatten()->filter()->unique()->values()->all();

        $resolvedLatitude = $primaryService['latitude'] ?? ($profileAddress?->latitude ?? '');
        $resolvedLongitude = $primaryService['longitude'] ?? ($profileAddress?->longitude ?? '');
        $resolvedAddressLabel = $primaryService['address_label'] ?? $profileAddressLabel;
        $providerDescription = trim((string) ($provider->provider_description ?? ''));
        if ($providerDescription === '') {
            $providerDescription = trim((string) ($primaryService['description'] ?? ''));
        }
        if ($providerDescription === '') {
            $providerDescription = 'Proveedor de servicios registrado en Kconecta.';
        }

        $providerUpdatedAt = $services->max('updated_at') ?: $provider->updated_at;
        $ratingSummary = app(ServiceRatingService::class)->providerRatingSummary((int) $provider->id);

        return view('page.details_provider', [
            'provider' => [
                'id' => (int) $provider->id,
                'name' => $providerDisplayName,
                'user_name' => (string) ($provider->user_name ?? ''),
                'email' => (string) ($provider->email ?? ''),
                'phone' => (string) ($providerPhone ?? ''),
                'whatsapp_phone' => $providerWhatsappPhone,
                'whatsapp_link' => $providerWhatsappLink,
                'photo_url' => ! empty($provider->photo)
                    ? asset('img/photo_profile/' . ltrim((string) $provider->photo, '/'))
                    : asset('img/default-avatar-profile-icon.webp'),
                'address_label' => $resolvedAddressLabel,
                'latitude' => $resolvedLatitude,
                'longitude' => $resolvedLongitude,
                'description' => $providerDescription,
                'updated_at_text' => $this->formatUpdatedAt($providerUpdatedAt),
                'average_stars' => (float) ($ratingSummary['average_stars'] ?? 0),
                'ratings_count' => (int) ($ratingSummary['ratings_count'] ?? 0),
                'specialties' => $aggregatedSpecialties,
                'service_count' => count($aggregatedSpecialties),
                'gallery_images' => $galleryImages,
                'title' => $providerProfileTitle,
                'availability' => (string) ($provider->provider_availability ?: ($primaryService['availability'] ?? '')),
                'primary_page_url' => $this->normalizeWebsiteUrl((string) ($provider->provider_page_url ?: ($primaryService['page_url'] ?? ''))) ?? '',
                'primary_video_url' => $providerVideo && ! empty($providerVideo->url)
                    ? asset('video/uploads/' . ltrim((string) $providerVideo->url, '/'))
                    : (string) ($primaryService['video_url'] ?? ''),
                'services' => $serviceCards,
            ],
        ]);
    }

    public function result(string $reference)
    {
        Carbon::setLocale('es');

        $request = request();

        $property = Property::query()
            ->where('reference', $reference)
            ->where('state_id', 4)
            ->first();

        if (! $property) {
            return redirect('/');
        }

        $item = $property->toArray();
        $item['updated_at_text'] = $this->formatUpdatedAt($property->updated_at);
        $item['type_name'] = Type::find($property->type_id)?->name ?? '';
        $item['category_name'] = Category::find($property->category_id)?->name ?? '';
        $coverImage = CoverImage::where('property_id', $property->id)->first();
        $item['cover_image'] = $coverImage ? $coverImage->toArray() : [];
        $item['more_images'] = MoreImage::where('property_id', $property->id)->get()->toArray();

        $item['city'] = $this->wrapSingle(City::find($property->city_id));
        $item['province'] = $this->wrapSingle(Province::find($property->province_id));
        $item['country'] = $this->wrapSingle(Country::find($property->country_id));
        $item['typology'] = $this->wrapSingle(Typology::find($property->typology_id));

        $item['type_heating'] = $this->wrapSingle(TypeHeating::find($property->type_heating_id));
        $item['emissions_rating'] = $this->wrapSingle(EmissionsRating::find($property->emissions_rating_id));
        $item['energy_class'] = $this->wrapSingle(EnergyClass::find($property->energy_class_id));
        $item['state_conservation'] = $this->wrapSingle(StateConservation::find($property->state_conservation_id));
        $item['visibility_in_portals'] = $this->wrapSingle(
            VisibilityInPortals::find($property->visibility_in_portals_id)
        );
        $item['rental_type'] = $this->wrapSingle(RentalType::find($property->rental_type_id));
        $item['contact_option'] = $this->wrapSingle(ContactOption::find($property->contact_option_id));
        $item['power_consumption_rating'] = $this->wrapSingle(
            PowerConsumptionRating::find($property->power_consumption_rating_id)
        );
        $item['reason_for_sale'] = $this->wrapSingle(ReasonForSale::find($property->reason_for_sale_id));
        $item['facade'] = $this->wrapSingle(Facade::find($property->facade_id));
        $item['videos'] = Video::where('property_id', $property->id)->get()->toArray();
        $item['plant'] = $this->wrapSingle(Plant::find($property->plant_id));
        $item['plaza_capacity'] = $this->wrapSingle(PlazaCapacity::find($property->plaza_capacity_id));

        $item['nearest_municipality_distance'] = $this->wrapSingle(
            NearestMunicipalityDistance::find($property->nearest_municipality_distance_id)
        );
        $item['wheeled_access'] = $this->wrapSingle(WheeledAccess::find($property->wheeled_access_id));
        $item['type_of_terrain'] = $this->wrapSingle(TypeOfTerrain::find($property->type_of_terrain_id));
        $item['terrain_use'] = $this->wrapSingle(TerrainUse::find($property->terrain_use_id));
        $item['terrain_qualifications'] = [];
        $terrainQualificationLinks = TerrainQualifications::where('property_id', $property->id)->get();
        foreach ($terrainQualificationLinks as $terrainQualificationLink) {
            $terrainQualification = TerrainQualification::find($terrainQualificationLink->terrain_qualification_id);
            if ($terrainQualification) {
                $item['terrain_qualifications'][] = $terrainQualification->toArray();
            }
        }
        $item['property_address'] = PropertyAddress::where('property_id', $property->id)->get()->toArray();
        $item['user'] = User::find($property->user_id)?->toArray() ?? [];

        $item['features'] = [];
        $featureLinks = Features::where('property_id', $property->id)->get();
        foreach ($featureLinks as $featureLink) {
            $feature = Feature::find($featureLink->feature_id);
            if ($feature) {
                $item['features'][] = $feature->toArray();
            }
        }

        $item['equipments'] = [];
        $equipmentLinks = Equipments::where('property_id', $property->id)->get();
        foreach ($equipmentLinks as $equipmentLink) {
            $equipment = Equipment::find($equipmentLink->equipment_id);
            if ($equipment) {
                $item['equipments'][] = $equipment->toArray();
            }
        }

        $item['orientations'] = [];
        $orientationLinks = Orientations::where('property_id', $property->id)->get();
        foreach ($orientationLinks as $orientationLink) {
            $orientation = Orientation::find($orientationLink->orientation_id);
            if ($orientation) {
                $item['orientations'][] = $orientation->toArray();
            }
        }

        $item['types_floors'] = [];
        $typesFloorLinks = TypesFloors::where('property_id', $property->id)->get();
        foreach ($typesFloorLinks as $typesFloorLink) {
            $typeFloor = TypeFloor::find($typesFloorLink->type_floor_id);
            if ($typeFloor) {
                $item['types_floors'][] = $typeFloor->toArray();
            }
        }

        $exists = PsViewsDetail::query()
            ->where('property_id', $property->id)
            ->where('ip_address', $request->ip())
            ->first();

        if (! $exists) {
            PsViewsDetail::create([
                'property_id' => $property->id,
                'ip_address' => $request->ip(),
                'counter' => 1,
            ]);
        }

        return view('page.details', ['property' => $item]);
    }

    public function resultService(string $id)
    {
        Carbon::setLocale('es');

        $service = Service::query()->where('id', $id)->first();
        if (! $service) {
            return redirect('/');
        }

        $user = User::find($service->user_id);
        if ($user && (int) $user->user_level_id === User::LEVEL_SERVICE_PROVIDER) {
            return $this->resultProvider((string) $user->id);
        }

        $item = $service->toArray();
        $item['updated_at_text'] = $this->formatUpdatedAt($service->updated_at);

        $coverImage = CoverImage::where('service_id', $service->id)->first();
        $item['cover_image'] = $coverImage ? $coverImage->toArray() : [];
        $item['more_images'] = MoreImage::where('service_id', $service->id)->get()->toArray();

        $item['videos'] = Video::where('service_id', $service->id)->get()->toArray();

        $serviceAddress = ServiceAddress::query()->where('service_id', $service->id)->first();
        $userAddress = UserAddress::query()->where('user_id', $service->user_id)->first();
        $resolvedAddress = $serviceAddress ?: $userAddress;
        $item['address'] = $resolvedAddress ? [$resolvedAddress->toArray()] : [];

        if (! empty($item['address'][0])) {
            $addressParts = array_values(array_filter([
                $item['address'][0]['address'] ?? null,
                $item['address'][0]['city'] ?? null,
                $item['address'][0]['province'] ?? null,
                $item['address'][0]['country'] ?? null,
            ], fn ($value) => trim((string) $value) !== ''));

            if (! empty($addressParts)) {
                $item['address'][0]['address'] = implode(', ', $addressParts);
            }
        }

        $item['user'] = $user?->toArray() ?? [];

        if ($user) {
            $item['user']['phone'] = $user->phone
                ?? ($user->mobile_phone ?? null)
                ?? ($user->landline_phone ?? null);

            if (empty($item['user']['photo'])) {
                $item['user']['photo'] = 'default-avatar-profile-icon.webp';
            }
        }

        $item['specialties'] = [];
        $serviceTypeLinks = app(ProviderServiceTypeService::class)->typeIdsForProvider((int) $service->user_id);
        foreach ($serviceTypeLinks as $serviceTypeId) {
            $serviceType = ServiceType::find($serviceTypeId);
            if ($serviceType) {
                $item['specialties'][] = $serviceType->toArray();
            }
        }

        return view('page.details_service', ['property' => $item]);
    }

    public function signup(Request $request)
    {
        return redirect()->route('login');
    }

    public function validateAccountPage()
    {
        return view('page.placeholder', ['title' => 'Validate Account']);
    }

    public function validateAccount(Request $request)
    {
        return redirect('/');
    }

    public function legalPrivacy()
    {
        return view('legal.privacy', $this->legalViewData());
    }

    public function legalTerms()
    {
        return view('legal.terms', $this->legalViewData());
    }

    public function legalAccountDeletion()
    {
        return view('legal.account-deletion', $this->legalViewData());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    private function wrapSingle($model): array
    {
        if (! $model) {
            return [];
        }

        return [$model->toArray()];
    }

    private function formatUpdatedAt($value): string
    {
        if (! $value) {
            return '';
        }

        return Carbon::parse($value)->translatedFormat('d \\d\\e F \\d\\e Y');
    }

    public function advancedCalculator()
    {
        return view('page.calculadora_avanzada');
    }

    private function legalViewData(): array
    {
        return [
            'legalConfig' => config('legal'),
            'lastUpdated' => (string) config('legal.last_updated'),
        ];
    }
}
