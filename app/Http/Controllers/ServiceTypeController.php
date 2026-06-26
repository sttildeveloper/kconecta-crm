<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use App\Models\ServiceTypeLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceTypeController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request->user());

        return $this->renderIndex($request);
    }

    public function edit(Request $request, string $id): View
    {
        $this->ensureAdmin($request->user());

        $serviceType = ServiceType::query()->findOrFail($id);

        return $this->renderIndex($request, $serviceType);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('service_type', 'name')],
        ]);

        ServiceType::query()->create([
            'name' => trim((string) $validated['name']),
        ]);

        return redirect('/admin/service-types')->with('status', 'Tipo de servicio creado correctamente.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->ensureAdmin($request->user());

        $serviceType = ServiceType::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_type', 'name')->ignore($serviceType->id),
            ],
        ]);

        $serviceType->name = trim((string) $validated['name']);
        $serviceType->save();

        return redirect('/admin/service-types')->with('status', 'Tipo de servicio actualizado correctamente.');
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request->user());

        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $serviceType = ServiceType::query()->findOrFail((int) $validated['id']);

        $linkedServicesCount = ServiceTypeLink::query()
            ->where('service_type_id', (int) $serviceType->id)
            ->count();

        if ($linkedServicesCount > 0) {
            return redirect('/admin/service-types')->with('error', 'No se puede eliminar este tipo porque ya esta vinculado a servicios publicados.');
        }

        $serviceType->delete();

        return redirect('/admin/service-types')->with('status', 'Tipo de servicio eliminado correctamente.');
    }

    private function renderIndex(Request $request, ?ServiceType $editingType = null): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
        ];

        $serviceTypes = ServiceType::query()
            ->select('service_type.*')
            ->selectSub(
                ServiceTypeLink::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('service_types.service_type_id', 'service_type.id'),
                'services_count'
            )
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where('service_type.name', 'like', '%' . $filters['q'] . '%');
            })
            ->orderBy('service_type.name')
            ->paginate(20)
            ->withQueryString();

        $serviceTypes->getCollection()->transform(function (ServiceType $serviceType) {
            return [
                'id' => (int) $serviceType->id,
                'name' => (string) $serviceType->name,
                'services_count' => (int) ($serviceType->services_count ?? 0),
                'created_at' => optional($serviceType->created_at)->format('d/m/Y'),
                'updated_at' => optional($serviceType->updated_at)->format('d/m/Y'),
            ];
        });

        return view('service_types.index', [
            'title' => 'Kconecta - Tipos de servicio',
            'serviceTypes' => $serviceTypes,
            'filters' => $filters,
            'editingType' => $editingType,
            'activeNav' => 'service-types',
        ]);
    }

    private function ensureAdmin(?User $user): void
    {
        abort_unless($user && $user->isAdmin(), 403);
    }
}
