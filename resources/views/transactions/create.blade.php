@php
$taxRatePercent = (int) round(config('transactions.ppn.rate') * 100);
@endphp

<x-layouts.app>
    <x-slot:title>{{ __('ui.page_pos') }}</x-slot>

    <div x-data="posCart" class="row g-3" dusk="pos-screen">
        {{-- KIRI: katalog produk --}}
        <div class="col-lg-7">
            <x-ui.card dusk="pos-catalog" :padded="false">
                <div class="card-header">
                    <div class="row g-2 w-100 align-items-center">
                        <div class="col-md-5">
                            <x-ui.select name="quick_product" :label="__('ui.add_to_cart')"
                                         :options="$quickProducts"
                                         placeholder="{{ __('ui.select_product') }}"
                                         dusk="select-quick-product"
                                         data-searchable data-placeholder="{{ __('ui.select_product') }}"
                                         x-on:change="quickAdd($event.target.value)" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('ui.filter') }}</label>
                            <input type="search" class="form-control" x-model="searchTerm"
                                   placeholder="{{ __('ui.search_product') }}" dusk="pos-search-input">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">{{ __('ui.qty') }} / hal</label>
                            <input type="number" class="form-control" x-model.number="perPage" min="4" max="48" step="4"
                                   dusk="pos-per-page" title="Items per halaman">
                        </div>
                    </div>
                </div>

                <div class="card-header border-bottom-0 pb-0">
                    <div class="d-flex gap-2 flex-wrap w-100" dusk="category-tabs">
                        <button type="button" class="btn btn-sm"
                                :class="activeCategory === null ? 'btn-primary' : 'btn-outline-primary'"
                                @click="activeCategory = null; page = 1" dusk="tab-all">
                            {{ __('ui.all_products') }}
                        </button>
                        @foreach ($categories as $category)
                            <button type="button" class="btn btn-sm"
                                    :class="activeCategory === {{ $category->id }} ? 'btn-primary' : 'btn-outline-primary'"
                                    @click="activeCategory = {{ $category->id }}; page = 1" dusk="tab-{{ $category->type_slug }}">
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="card-body">
                    <div class="row row-cards">
                        <template x-for="p in pagedProducts" :key="p.id">
                            <div class="col-6 col-md-4">
                                <div class="card pos-product-card h-100 mb-0" role="button" tabindex="0"
                                     @click="add(p)" :class="p.stockable && p.stock < 1 ? 'disabled' : ''"
                                     :dusk="'product-' + p.id">
                                    <div class="card-body p-3">
                                        <div class="fw-semibold small" x-text="p.name"></div>
                                        <div class="text-muted small mt-1" x-text="formatMoney(p.price)"></div>
                                        <div class="small text-secondary" x-show="p.stockable"
                                             x-text="'{{ __('ui.stock') }}: ' + p.stock"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <p class="text-muted small" x-show="!filteredProducts.length" dusk="pos-empty-products">
                        {{ __('ui.empty_products') }}
                    </p>

                    <nav class="d-flex align-items-center justify-content-between mt-3"
                         x-show="totalPages > 1" dusk="pos-pagination">
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                :disabled="page <= 1" @click="page--" dusk="pos-prev">
                            &laquo;
                        </button>
                        <span class="small text-secondary" dusk="pos-page-info">
                            <span x-text="clampedPage"></span> / <span x-text="totalPages"></span>
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                :disabled="page >= totalPages" @click="page++" dusk="pos-next">
                            &raquo;
                        </button>
                    </nav>
                </div>
            </x-ui.card>
        </div>

        {{-- KANAN: ringkasan pesanan --}}
        <div class="col-lg-5">
            <x-ui.card dusk="pos-cart" :padded="false">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ __('ui.cart') }}</h3>
                </div>

                <form method="POST" action="{{ route('transactions.store') }}" dusk="pos-form">
                    <div class="card-body p-4">
                    @csrf

                    <x-ui.select name="customer_id" :label="__('ui.customers')"
                                 :options="$customers->pluck('name', 'id')->all()"
                                 placeholder="{{ __('ui.select_customer_optional') }}"
                                 x-model.number="customerId" dusk="select-customer_id"
                                 data-searchable data-placeholder="{{ __('ui.select_customer_optional') }}"
                                 x-on:change="refreshPreview()" />

                    <x-ui.select name="schedule_id" :label="__('ui.assign_schedule')"
                                 :options="$schedules->pluck('label', 'id')->all()"
                                 placeholder="{{ __('ui.select_schedule') }}"
                                 x-model.number="scheduleId" dusk="select-schedule_id"
                                 data-searchable data-placeholder="{{ __('ui.select_schedule') }}" />

                    <x-ui.input name="discount_code" :label="__('ui.discount_code')" :value="null"
                                placeholder="HEMAT10" dusk="input-discount_code" x-model="discountCode"
                                x-on:input.debounce.400ms="refreshPreview()" />

                    <div class="form-text" x-show="!discountPreview" dusk="discount-code-hint">{{ __('ui.discount_code_hint') }}</div>
                    <div class="form-text text-success fw-semibold" x-show="discountPreview && discountPreview.valid"
                         dusk="discount-preview-ok">
                        <span x-text="discountPreview?.name"></span>:
                        <span x-text="discountPreview?.formatted"></span>
                    </div>
                    <div class="form-text text-danger" x-show="discountPreview && !discountPreview.valid"
                         dusk="discount-preview-err" x-text="discountPreview?.message"></div>
                    <div class="mb-2"></div>

                    <h2 class="h6 fw-semibold mt-3">{{ __('ui.cart') }}</h2>
                    <table class="table table-sm align-middle" dusk="cart-table" x-show="cart.length">
                        <thead>
                        <tr>
                            <th>{{ __('ui.product') }}</th>
                            <th style="width:110px">{{ __('ui.qty') }}</th>
                            <th class="text-end">{{ __('ui.price') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <template x-for="line in cart" :key="line.id">
                            <tr :dusk="'cart-row-' + line.id">
                                <td class="small" x-text="line.name"></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" @click="dec(line)"
                                                :dusk="'qty-minus-' + line.id" aria-label="Kurangi">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                 stroke-linejoin="round" class="icon icon-2">
                                                <path d="M5 12l14 0" />
                                            </svg>
                                        </button>
                                        <span class="form-control text-center" x-text="line.qty"
                                              :dusk="'qty-value-' + line.id"></span>
                                        <button type="button" class="btn btn-outline-secondary" @click="inc(line)"
                                                :dusk="'qty-plus-' + line.id" aria-label="Tambah">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                 stroke-linejoin="round" class="icon icon-2">
                                                <path d="M12 5l0 14" />
                                                <path d="M5 12l14 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-end small" x-text="formatMoney(line.price * line.qty)"></td>
                                <td>
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            @click="remove(line)" :dusk="'cart-remove-' + line.id" aria-label="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                             stroke-linejoin="round" class="icon icon-2">
                                            <path d="M18 6l-12 12" />
                                            <path d="M6 6l12 12" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                    <p class="text-muted small" x-show="!cart.length" dusk="empty-cart">{{ __('ui.empty_cart') }}</p>

                    <div x-data="moneyField">
                        <x-ui.input name="_discount_display" :label="__('ui.discount')" dusk="input-discount_total"
                                    x-ref="display" x-model="display" @input="onInput"
                                    x-on:money-input="discount = Number($event.detail || 0)"
                                    x-bind:disabled="discountPreviewValid" />
                        <input type="hidden" name="discount_total" :value="discount" x-ref="hidden">
                    </div>

                    <dl class="row mb-3 small" dusk="pos-totals">
                        <dt class="col-7">{{ __('ui.subtotal') }}</dt>
                        <dd class="col-5 text-end" x-text="formatMoney(subtotal)" dusk="pos-subtotal"></dd>
                        <dt class="col-7" x-show="discountValue > 0">{{ __('ui.discount') }}</dt>
                        <dd class="col-5 text-end" x-show="discountValue > 0" x-text="'- ' + formatMoney(discountValue)"
                            dusk="pos-discount"></dd>
                        <dt class="col-7">{{ __('ui.tax') }} ({{ $taxRatePercent }}%)</dt>
                        <dd class="col-5 text-end" x-text="formatMoney(tax)" dusk="pos-tax"></dd>
                        <dt class="col-7 fw-bold">{{ __('ui.grand_total') }}</dt>
                        <dd class="col-5 text-end fw-bold" x-text="formatMoney(total)" dusk="pos-total"></dd>
                    </dl>

                    <h2 class="h6 fw-semibold">{{ __('ui.payment_method') }}</h2>
                    <template x-for="(payment, index) in payments" :key="index">
                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <select class="form-select form-select-sm" x-model="payment.method"
                                        :dusk="'payment-method-' + index">
                                    <template x-for="m in methods" :key="m">
                                        <option :value="m" x-text="methodLabel(m)"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-7">
                                <div x-data="moneyField(payment.amount)">
                                    <input type="hidden" x-ref="hidden" :value="payment.amount">
                                    <input type="text" inputmode="numeric" autocomplete="off"
                                           class="form-control form-control-sm" placeholder="0"
                                           x-ref="display" x-model="display" @input="onInput"
                                           x-on:money-input="payment.amount = Number($event.detail || 0)"
                                           :dusk="'payment-amount-' + index">
                                </div>
                            </div>
                        </div>
                    </template>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3"
                            @click="addPaymentRow()" dusk="add-payment-row">{{ __('ui.add_payment') }}</button>

                    <input type="hidden" name="items_json" :value="itemsJson" dusk="input-items-json">
                    <input type="hidden" name="payments_json" :value="paymentsJson">
                    <input type="hidden" name="idempotency_key" :value="idempotencyKey">
                    <input type="hidden" name="branch_id" :value="branchId">
                    </div>

                    <div class="card-footer">
                        <x-ui.button type="submit" variant="primary" class="w-100" dusk="pos-submit"
                                     x-bind:disabled="!cart.length">
                            {{ __('ui.checkout') }}
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('posCart', () => ({
            products: @json($products),
            methods: @json($paymentMethods),
            categories: @json($categories->pluck('name', 'id')),
            branchId: @js(auth()->user()->branches->first()->id ?? 1),
            activeCategory: null,
            customerId: null,
            scheduleId: null,
            discount: 0,
            discountCode: '',
            discountPreview: null,
            searchTerm: '',
            page: 1,
            perPage: 12,
            cart: [],
            payments: [{ method: 'cash', amount: 0 }],
            idempotencyKey: crypto.randomUUID(),

            get filteredProducts() {
                const term = this.searchTerm.trim().toLowerCase();
                return this.products.filter(p => {
                    if (this.activeCategory !== null && p.category_id !== this.activeCategory) {
                        return false;
                    }
                    if (term !== '' && ! p.name.toLowerCase().includes(term)) {
                        return false;
                    }
                    return true;
                });
            },

            get totalPages() {
                return Math.max(1, Math.ceil(this.filteredProducts.length / this.perPage));
            },

            get clampedPage() {
                return Math.min(this.page, this.totalPages);
            },

            get pagedProducts() {
                const p = this.clampedPage;
                return this.filteredProducts.slice((p - 1) * this.perPage, p * this.perPage);
            },

            get subtotal() {
                return this.cart.reduce((sum, l) => sum + l.price * l.qty, 0);
            },

            get discountValue() {
                if (this.discountPreviewValid) {
                    return Math.min(Number(this.discountPreview.amount) || 0, this.subtotal);
                }
                return Math.min(Math.max(0, Number(this.discount) || 0), this.subtotal);
            },

            get discountPreviewValid() {
                return !!(this.discountPreview && this.discountPreview.valid);
            },

            async refreshPreview() {
                const code = this.discountCode.trim();
                if (code === '' || this.cart.length === 0) {
                    this.discountPreview = null;
                    return;
                }

                try {
                    const res = await fetch('{{ route('transactions.discount.preview') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            code: code,
                            customer_id: this.customerId,
                            branch_id: this.branchId,
                            items: this.cart.map(l => ({ product_id: l.id, qty: l.qty })),
                        }),
                    });
                    this.discountPreview = await res.json();
                } catch (e) {
                    this.discountPreview = null;
                }
            },

            get tax() {
                const rate = {{ config('transactions.ppn.rate') }};
                return Math.round((this.subtotal - this.discountValue) * rate);
            },

            get total() {
                return this.subtotal - this.discountValue + this.tax;
            },

            get itemsJson() {
                return JSON.stringify(this.cart.map(l => ({
                    product_id: l.id,
                    qty: l.qty,
                    schedule_id: this.scheduleId || null,
                })));
            },

            get paymentsJson() {
                const rows = this.payments
                    .map(p => ({ method: p.method, amount: Number(p.amount) || 0 }))
                    .filter(p => p.amount > 0);

                // Dengan kode diskon, client tidak tahu jumlah final (server-side).
                // Tanpa baris eksplisit → kirim kosong; kasir catat pembayaran di receipt.
                if (rows.length === 0) {
                    if (this.discountCode.trim() !== '') {
                        return '[]';
                    }
                    return JSON.stringify([{ method: 'cash', amount: this.total }]);
                }

                const others = rows.slice(0, -1).reduce((s, p) => s + p.amount, 0);
                rows[rows.length - 1].amount = Math.max(0, this.total - others);

                return JSON.stringify(rows.filter(p => p.amount > 0));
            },

            add(product) {
                const line = this.cart.find(l => l.id === product.id);
                if (line) {
                    this.inc(line);
                    return;
                }
                this.cart.push({ ...product, qty: 1 });
                this.syncFirstPayment();
                this.refreshPreview();
            },

            quickAdd(value) {
                const id = Number(value);
                if (! id) return;
                const product = this.products.find(p => p.id === id);
                if (product) {
                    this.add(product);
                }
                const sel = document.querySelector('[name=quick_product]');
                if (sel) {
                    sel.value = '';
                    if (sel.tomselect) sel.tomselect.clear(true);
                }
            },

            inc(line) {
                if (line.stockable && line.qty >= line.stock) return;
                line.qty++;
                this.syncFirstPayment();
                this.refreshPreview();
            },

            dec(line) {
                line.qty = Math.max(1, line.qty - 1);
                this.syncFirstPayment();
                this.refreshPreview();
            },

            remove(line) {
                this.cart = this.cart.filter(l => l.id !== line.id);
                this.syncFirstPayment();
                this.refreshPreview();
            },

            addPaymentRow() {
                this.payments.push({ method: 'transfer', amount: 0 });
            },

            syncFirstPayment() {
                if (this.payments.length === 1) {
                    this.payments[0].amount = this.total;
                }
            },

            methodLabel(method) {
                const map = {
                    cash: '{{ __('ui.method_cash') }}',
                    transfer: '{{ __('ui.method_transfer') }}',
                    qris: '{{ __('ui.method_qris') }}',
                    card: '{{ __('ui.method_card') }}',
                };
                return map[method] ?? method;
            },

            formatMoney(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
            },
        }));
    });
    </script>
    @endpush
</x-layouts.app>
