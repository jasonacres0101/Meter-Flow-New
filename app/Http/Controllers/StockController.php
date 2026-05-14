<?php

namespace App\Http\Controllers;

use App\Models\MachineModel;
use App\Models\Site;
use App\Models\StockProduct;
use App\Services\StockService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeStock($request);

        $products = Tenant::scope(StockProduct::query(), $request->user())
            ->with(['balances', 'machineModels'])
            ->withCount('movements')
            ->orderBy('name')
            ->paginate(20);

        return view('stock.index', [
            'products' => $products,
            'types' => StockProduct::types(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeStock($request);

        return view('stock.create', array_merge($this->formData($request), [
            'product' => new StockProduct(['is_active' => true]),
        ]));
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $this->authorizeStock($request);
        $data = $this->validatedProduct($request, includeQuantity: true);

        $product = StockProduct::create([
            'company_id' => Tenant::companyId($request->user()),
            'name' => $data['name'],
            'type' => $data['type'],
            'supplier' => $data['supplier'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
        ]);
        $product->machineModels()->sync($this->validMachineModelIds($data['machine_model_ids'] ?? [], $request));

        if (($data['quantity'] ?? 0) > 0) {
            $stock->addCompanyStock($product, (int) $data['quantity'], $request->user(), 'Initial stock');
        }

        return redirect()->route('stock.show', $product)->with('status', 'Stock product created.');
    }

    public function show(Request $request, StockProduct $stockProduct): View
    {
        $this->authorizeStock($request);
        $stockProduct = $this->productForUser($stockProduct->id, $request);
        $stockProduct->load([
            'balances.site.client',
            'machineModels.machines.client',
            'machineModels.machines.site',
            'movements.toSite.client',
            'movements.createdBy',
        ]);

        $sites = Site::query()
            ->with('client')
            ->whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))
            ->orderBy('name')
            ->get();
        $compatibleMachines = $stockProduct->machineModels
            ->flatMap(fn (MachineModel $model) => $model->machines)
            ->filter(fn ($machine) => $machine->client?->company_id === Tenant::companyId($request->user()))
            ->unique('id')
            ->values();

        return view('stock.show', [
            'product' => $stockProduct,
            'sites' => $sites,
            'types' => StockProduct::types(),
            'companyQuantity' => (int) ($stockProduct->balances->firstWhere('site_id', null)?->quantity ?? 0),
            'siteBalances' => $stockProduct->balances->whereNotNull('site_id')->filter(fn ($balance) => $balance->quantity > 0)->values(),
            'compatibleMachines' => $compatibleMachines,
            'movements' => $stockProduct->movements->sortByDesc('created_at')->take(50),
        ]);
    }

    public function edit(Request $request, StockProduct $stockProduct): View
    {
        $this->authorizeStock($request);
        $stockProduct = $this->productForUser($stockProduct->id, $request)->load('machineModels');

        return view('stock.edit', array_merge($this->formData($request), [
            'product' => $stockProduct,
        ]));
    }

    public function update(Request $request, StockProduct $stockProduct): RedirectResponse
    {
        $this->authorizeStock($request);
        $stockProduct = $this->productForUser($stockProduct->id, $request);
        $data = $this->validatedProduct($request);

        $stockProduct->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'supplier' => $data['supplier'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);
        $stockProduct->machineModels()->sync($this->validMachineModelIds($data['machine_model_ids'] ?? [], $request));

        return redirect()->route('stock.show', $stockProduct)->with('status', 'Stock product updated.');
    }

    public function addStock(Request $request, StockService $stock): RedirectResponse
    {
        $this->authorizeStock($request);

        $data = $request->validate([
            'stock_product_id' => ['required', 'exists:stock_products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);
        $product = $this->productForUser((int) $data['stock_product_id'], $request);
        $stock->addCompanyStock($product, (int) $data['quantity'], $request->user(), $data['notes'] ?? null);

        return redirect()->route('stock.show', $product)->with('status', 'Company stock updated.');
    }

    public function transfer(Request $request, StockService $stock): RedirectResponse
    {
        $this->authorizeStock($request);

        $data = $request->validate([
            'stock_product_id' => ['required', 'exists:stock_products,id'],
            'site_id' => ['required', 'exists:sites,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);
        $product = $this->productForUser((int) $data['stock_product_id'], $request);
        Site::query()
            ->whereKey($data['site_id'])
            ->whereHas('client', fn ($query) => Tenant::scope($query, $request->user()))
            ->firstOrFail();

        try {
            $stock->transferCompanyStockToSite($product, (int) $data['site_id'], (int) $data['quantity'], $request->user(), $data['notes'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('stock.show', $product)->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('stock.show', $product)->with('status', 'Stock moved to site.');
    }

    private function productForUser(int $productId, Request $request): StockProduct
    {
        return Tenant::scope(StockProduct::query(), $request->user())->findOrFail($productId);
    }

    private function authorizeStock(Request $request): void
    {
        abort_if($request->user()->isEngineer() || $request->user()->isPlatformAdmin(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request): array
    {
        return [
            'types' => StockProduct::types(),
            'machineModels' => Tenant::scopeWithGlobal(MachineModel::query(), $request->user())
                ->orderBy('manufacturer')
                ->orderBy('model_name')
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProduct(Request $request, bool $includeQuantity = false): array
    {
        return $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:toner,paper,waste_box'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'machine_model_ids' => ['nullable', 'array'],
            'machine_model_ids.*' => ['integer', 'exists:machine_models,id'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], $includeQuantity ? [
            'quantity' => ['nullable', 'integer', 'min:0'],
        ] : []));
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function validMachineModelIds(array $ids, Request $request): array
    {
        if ($ids === []) {
            return [];
        }

        return Tenant::scopeWithGlobal(MachineModel::query(), $request->user())
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
