<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CoverImage;
use App\Models\Property;
use App\Models\PropertyAddress;
use App\Models\PsEmailOwner;
use App\Models\PsMessagesReceived;
use App\Models\PsOwnerCalls;
use App\Models\PsViewsDetail;
use App\Models\PsViewsSearch;
use App\Models\PsWhatsappClicks;
use App\Models\Service;
use App\Models\Type;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserLevel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user && (int) $user->user_level_id === 1;
        $userLevelName = $user ? (UserLevel::find($user->user_level_id)?->name ?? 'Usuario') : 'Usuario';
        $canManageProperties = $user ? $user->canManageProperties() : false;
        $canManageServices = $user ? $user->canManageServices() : false;
        $isFinalClient = $user && (int) $user->user_level_id === User::LEVEL_FINAL_CLIENT;

        $propertyBase = Property::query()->where('state_id', 4);
        $serviceBase = Service::query();

        if (! $isAdmin && $user) {
            if ($canManageProperties) {
                $propertyBase->where('user_id', $user->id);
            } else {
                $propertyBase->whereRaw('1=0');
            }

            if ($canManageServices) {
                $serviceBase->where('user_id', $user->id);
            } else {
                $serviceBase->whereRaw('1=0');
            }
        }

        $propertyCount = (clone $propertyBase)->count();
        $serviceCount = (clone $serviceBase)->count();
        $userCount = $isAdmin ? User::count() : 1;

        $propertyIdsForStats = (clone $propertyBase)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $viewsCount = empty($propertyIdsForStats)
            ? 0
            : PsViewsDetail::whereIn('property_id', $propertyIdsForStats)->sum('counter');
        $searchViewsCount = empty($propertyIdsForStats)
            ? 0
            : PsViewsSearch::whereIn('property_id', $propertyIdsForStats)->sum('counter');
        $uniqueViewersCount = empty($propertyIdsForStats)
            ? 0
            : PsViewsDetail::whereIn('property_id', $propertyIdsForStats)
                ->select('ip_address')
                ->distinct()
                ->count();
        $emailClicks = empty($propertyIdsForStats)
            ? 0
            : PsEmailOwner::whereIn('property_id', $propertyIdsForStats)->sum('counter');
        $callClicks = empty($propertyIdsForStats)
            ? 0
            : PsOwnerCalls::whereIn('property_id', $propertyIdsForStats)->sum('counter');
        $whatsappClicks = empty($propertyIdsForStats)
            ? 0
            : PsWhatsappClicks::whereIn('property_id', $propertyIdsForStats)->sum('counter');
        $messagesCount = empty($propertyIdsForStats)
            ? 0
            : PsMessagesReceived::whereIn('property_id', $propertyIdsForStats)->count();
        $contactClicks = $emailClicks + $callClicks + $whatsappClicks + $messagesCount;

        $userTypeMetrics = [];
        if ($isAdmin) {
            $levelCounts = User::query()
                ->selectRaw('user_level_id, COUNT(*) as total')
                ->groupBy('user_level_id')
                ->pluck('total', 'user_level_id');
            $palette = ['#00d1b2', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
            $index = 0;
            foreach (UserLevel::orderBy('id')->get(['id', 'name']) as $level) {
                $userTypeMetrics[] = [
                    'label' => $level->name,
                    'count' => (int) ($levelCounts[$level->id] ?? 0),
                    'color' => $palette[$index % count($palette)],
                ];
                $index++;
            }
        }

        $propertyTypeStats = [];
        $chartGradient = 'conic-gradient(#e2e7ef 0 100%)';
        if (! empty($propertyIdsForStats)) {
            $typeRows = PsViewsDetail::query()
                ->selectRaw('property.type_id as type_id, SUM(ps_views_detail.counter) as total')
                ->join('property', 'ps_views_detail.property_id', '=', 'property.id')
                ->whereIn('property.id', $propertyIdsForStats)
                ->groupBy('property.type_id')
                ->orderByDesc('total')
                ->get();

            $typeMap = Type::pluck('name', 'id')->all();
            $maxSegments = 4;
            $topRows = $typeRows->take($maxSegments);
            $otherTotal = $typeRows->slice($maxSegments)->sum('total');

            $palette = ['#00d1b2', '#3b82f6', '#f59e0b', '#ef4444', '#94a3b8'];
            $index = 0;
            $totalViewsByType = 0;

            foreach ($topRows as $row) {
                $value = (int) $row->total;
                if ($value <= 0) {
                    continue;
                }
                $label = $typeMap[$row->type_id] ?? 'Sin tipo';
                $propertyTypeStats[] = [
                    'label' => $label,
                    'value' => $value,
                    'color' => $palette[$index % count($palette)],
                ];
                $totalViewsByType += $value;
                $index++;
            }

            if ($otherTotal > 0) {
                $propertyTypeStats[] = [
                    'label' => 'Otros',
                    'value' => (int) $otherTotal,
                    'color' => $palette[$index % count($palette)],
                ];
                $totalViewsByType += (int) $otherTotal;
            }

            if ($totalViewsByType > 0) {
                $start = 0;
                $segments = [];
                foreach ($propertyTypeStats as $stat) {
                    $percentage = round(($stat['value'] / $totalViewsByType) * 100, 2);
                    $end = $start + $percentage;
                    $segments[] = $stat['color'] . ' ' . $start . '% ' . $end . '%';
                    $start = $end;
                }
                $chartGradient = 'conic-gradient(' . implode(', ', $segments) . ')';
            }
        }

        $recentProperties = (clone $propertyBase)->orderByDesc('id')->limit(5)->get();
        $recentPropertyIds = $recentProperties->pluck('id')->map(fn ($id) => (int) $id)->all();
        $coverImages = empty($recentPropertyIds)
            ? collect()
            : CoverImage::whereIn('property_id', $recentPropertyIds)->get()->keyBy('property_id');
        $addressRows = empty($recentPropertyIds)
            ? collect()
            : PropertyAddress::whereIn('property_id', $recentPropertyIds)->get()->groupBy('property_id');
        $categoryMap = Category::pluck('name', 'id');
        $typeMap = Type::pluck('name', 'id');

        $recentPropertiesData = $recentProperties->map(function (Property $property) use ($coverImages, $addressRows, $categoryMap, $typeMap) {
            $address = $addressRows->get($property->id)?->first();
            $price = $property->sale_price ?: $property->rental_price;

            return [
                'reference' => $property->reference,
                'title' => $property->title ?: 'Sin titulo',
                'category' => $categoryMap[$property->category_id] ?? 'Sin categoria',
                'type' => $typeMap[$property->type_id] ?? 'Sin tipo',
                'address' => $address?->address ?? '',
                'city' => $address?->city ?? '',
                'price' => $price,
                'image' => $coverImages->get($property->id)?->url ?? null,
            ];
        })->all();

        $recentServices = (clone $serviceBase)->orderByDesc('id')->limit(5)->get();
        $serviceUserIds = $recentServices->pluck('user_id')->filter()->unique()->values()->all();
        $serviceUsers = empty($serviceUserIds)
            ? collect()
            : User::whereIn('id', $serviceUserIds)->get()->keyBy('id');
        $serviceAddresses = empty($serviceUserIds)
            ? collect()
            : UserAddress::whereIn('user_id', $serviceUserIds)->get()->groupBy('user_id');

        $recentServicesData = $recentServices->map(function (Service $service) use ($serviceUsers, $serviceAddresses) {
            $user = $serviceUsers->get($service->user_id);
            $address = $serviceAddresses->get($service->user_id)?->first();
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            return [
                'name' => $name !== '' ? $name : ($user->user_name ?? 'Servicio'),
                'address' => $address?->address ?? '',
                'city' => $address?->city ?? '',
                'phone' => $user->phone ?? '',
            ];
        })->all();

        $alerts = [];
        if ($canManageProperties && $propertyCount === 0) {
            $alerts[] = $isAdmin
                ? 'No hay propiedades publicadas en el sistema.'
                : 'Aun no tienes propiedades publicadas.';
        }
        if ($canManageServices && $serviceCount === 0) {
            $alerts[] = $isAdmin
                ? 'No hay servicios publicados en el sistema.'
                : 'Aun no tienes servicios publicados.';
        }

        $finalClientStats = [
            'ratingsCount' => 0,
            'providersRatedCount' => 0,
            'averageStars' => 0.0,
            'recentRatings' => [],
        ];

        if ($isFinalClient && Schema::hasTable('service_provider_ratings')) {
            $ratingsQuery = DB::table('service_provider_ratings')
                ->where('client_user_id', (int) $user->id);

            $finalClientStats['ratingsCount'] = (int) (clone $ratingsQuery)->count();
            $finalClientStats['providersRatedCount'] = (int) (clone $ratingsQuery)
                ->distinct('provider_user_id')
                ->count('provider_user_id');

            $avg = (clone $ratingsQuery)->avg('stars');
            $finalClientStats['averageStars'] = $avg !== null ? round((float) $avg, 2) : 0.0;

            $recentRatings = DB::table('service_provider_ratings as r')
                ->join('user as u', 'u.id', '=', 'r.provider_user_id')
                ->where('r.client_user_id', (int) $user->id)
                ->orderByDesc('r.updated_at')
                ->limit(5)
                ->get([
                    'r.stars',
                    'r.updated_at',
                    'u.user_name',
                    'u.first_name',
                    'u.last_name',
                ]);

            $finalClientStats['recentRatings'] = $recentRatings->map(function ($row) {
                $fullName = trim((string) ($row->first_name ?? '') . ' ' . (string) ($row->last_name ?? ''));
                $providerName = trim((string) ($row->user_name ?? '')) !== ''
                    ? (string) $row->user_name
                    : ($fullName !== '' ? $fullName : 'Proveedor');

                return [
                    'provider' => $providerName,
                    'stars' => (int) $row->stars,
                    'updated_at' => $row->updated_at ? date('d/m/Y H:i', strtotime((string) $row->updated_at)) : null,
                ];
            })->all();
        }

        return view('dashboard', [
            'user' => $user,
            'userLevelName' => $userLevelName,
            'isAdmin' => $isAdmin,
            'isFinalClient' => $isFinalClient,
            'canManageProperties' => $canManageProperties,
            'canManageServices' => $canManageServices,
            'activeNav' => 'dashboard',
            'propertyCount' => $propertyCount,
            'serviceCount' => $serviceCount,
            'userCount' => $userCount,
            'viewsCount' => $viewsCount,
            'searchViewsCount' => $searchViewsCount,
            'messagesCount' => $messagesCount,
            'uniqueViewersCount' => $uniqueViewersCount,
            'contactClicks' => $contactClicks,
            'userTypeMetrics' => $userTypeMetrics,
            'propertyTypeStats' => $propertyTypeStats,
            'propertyTypeGradient' => $chartGradient,
            'recentProperties' => $recentPropertiesData,
            'recentServices' => $recentServicesData,
            'alerts' => $alerts,
            'finalClientStats' => $finalClientStats,
        ]);
    }
}
