<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\AuditService;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()->when($request->filled('q'), fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', '%' . $request->q . '%')->orWhere('phone', 'like', '%' . $request->q . '%')->orWhere('member_code', 'like', '%' . $request->q . '%')))->latest()->paginate(20)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function create() { return view('customers.form', ['customer' => new Customer(['is_active' => true])]); }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['member_code'] = $data['member_code'] ?? null;
        $data['member_code'] = $data['member_code'] ?: 'MBR-' . now()->format('ymdHis') . '-' . random_int(10, 99);
        $data['is_active'] = $request->boolean('is_active');
        Customer::create($data);
        AuditService::log('customer.created', null, 'Data pelanggan ditambahkan', ['name' => $data['name']]);
        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil ditambahkan.');
    }

    public function edit(Customer $customer) { return view('customers.form', compact('customer')); }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validated($request, $customer);
        $data['is_active'] = $request->boolean('is_active');
        $customer->update($data);
        AuditService::log('customer.updated', $customer, 'Data pelanggan diperbarui');
        return redirect()->route('customers.index')->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate(['member_code' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'member_code')->ignore($customer?->id)], 'name' => 'required|string|max:150', 'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:1000', 'points' => 'nullable|integer|min:0', 'is_active' => 'nullable|boolean']);
    }
}
