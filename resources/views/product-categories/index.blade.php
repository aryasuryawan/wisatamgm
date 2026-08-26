<x-layouts.app>
    <x-slot:title>{{ __('ui.page_categories') }}</x-slot>

    <x-slot:page_actions>
        @can('products.create')
            <a href="{{ route('product-categories.create') }}" class="btn btn-primary" dusk="create-category">
                + {{ __('ui.add') }} {{ __('ui.category') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="categories-card" :padded="false">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="categories-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_name') }}</th>
                    <th>{{ __('ui.code') }}</th>
                    <th>{{ __('ui.table_products') }}</th>
                    <th>{{ __('ui.table_status') }}</th>
                    <th>{{ __('ui.table_sort') }}</th>
                    <th class="text-end">{{ __('ui.table_action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($categories as $category)
                    <tr dusk="category-row-{{ $category->id }}">
                        <td class="fw-semibold">{{ $category->name }}</td>
                        <td><code>{{ $category->type_slug }}</code></td>
                        <td>{{ $category->products_count }}</td>
                        <td>
                            <x-ui.badge :color="$category->is_active ? 'success' : 'secondary'">
                                {{ $category->is_active ? __('ui.active') : __('ui.inactive') }}
                            </x-ui.badge>
                        </td>
                        <td>{{ $category->sort_order }}</td>
                        <td class="text-end">
                            @can('products.edit')
                                <a href="{{ route('product-categories.edit', $category) }}"
                                   class="btn btn-outline-primary btn-sm" dusk="edit-category-{{ $category->id }}">
                                    {{ __('ui.edit') }}
                                </a>
                            @endcan
                            @can('products.delete')
                                <form method="POST" action="{{ route('product-categories.destroy', $category) }}"
                                      class="d-inline" onsubmit="return confirm('{{ __('ui.confirm_delete_category') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-category-{{ $category->id }}">
                                        {{ __('ui.delete') }}
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('ui.empty_categories') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">{{ $categories->links() }}</div>
    </x-ui.card>
</x-layouts.app>
