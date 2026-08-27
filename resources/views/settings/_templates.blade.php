@php $s = $settings['templates'] ?? []; @endphp

<form method="POST" action="{{ route('settings.update', ['tab' => 'templates']) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="tab" value="templates">

    <div class="row g-4">
        <div class="col-12">
            <x-ui.input name="pdf_company_header" label="{{ __('ui.pdf_company_header') }}"
                        :value="$s['pdf_company_header'] ?? ''" dusk="input-pdf-header" />
        </div>
        <div class="col-12">
            <x-ui.input name="pdf_receipt_header" label="{{ __('ui.pdf_receipt_header') }}"
                        :value="$s['pdf_receipt_header'] ?? ''" dusk="input-pdf-receipt-header" />
        </div>
        <div class="col-12">
            <label class="form-label">{{ __('ui.pdf_receipt_footer') }}</label>
            <textarea name="pdf_receipt_footer" class="form-control" rows="2" dusk="input-pdf-footer">{{ $s['pdf_receipt_footer'] ?? '' }}</textarea>
        </div>
        <div class="col-md-4">
            <x-ui.select name="pdf_paper_size" label="{{ __('ui.pdf_paper_size') }}"
                         :options="['a4' => __('ui.paper_a4'), 'a5' => __('ui.paper_a5')]"
                         :value="$s['pdf_paper_size'] ?? 'a5'" dusk="select-pdf-paper" />
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('ui.pdf_show_logo') }}</label>
            <div class="mt-2">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="pdf_show_logo" value="1"
                           {{ ($s['pdf_show_logo'] ?? '1') === '1' ? 'checked' : '' }} dusk="toggle-pdf-logo">
                    <span class="form-check-label">{{ __('ui.pdf_show_logo') }}</span>
                </label>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('ui.pdf_show_tax') }}</label>
            <div class="mt-2">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="pdf_show_tax" value="1"
                           {{ ($s['pdf_show_tax'] ?? '1') === '1' ? 'checked' : '' }} dusk="toggle-pdf-tax">
                    <span class="form-check-label">{{ __('ui.pdf_show_tax') }}</span>
                </label>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-templates">{{ __('ui.save_changes') }}</x-ui.button>
    </div>

    <!-- Preview area -->
    <div class="mt-4 p-3 border rounded" id="pdf-preview" style="display:none;">
        <h6 class="small mb-3">{{ __('ui.pdf_receipt_title') }}</h6>
        <div class="head" style="border-bottom: 2px solid #1a1a1a; padding-bottom: 8px; margin-bottom: 8px;">
            <div class="brand" id="preview-brand"></div>
            <div class="meta" id="preview-meta">Wisata Scuba Center</div>
        </div>
        <table class="info" style="width:100%; border-collapse:collapse;">
            <tr><td style="color:#555; width:30%;">#000123</td><td>: 15/08/2026 14:30</td></tr>
            <tr><td style="color:#555">Customer</td><td>: John Doe</td></tr>
        </table>
        <table class="items" style="width:100%; border-collapse:collapse; margin-top:8px;">
            <thead>
            <tr>
                <th style="width:46%;">Item</th>
                <th class="r" style="width:16%">Qty</th>
                <th class="r" style="width:16%">Price</th>
                <th class="r" style="width:22%">Total</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Diving Package</td>
                <td class="r">2</td>
                <td class="r">Rp 500.000</td>
                <td class="r">Rp 1.000.000</td>
            </tr>
            </tbody>
        </table>
        <div class="totals" style="margin-top:8px; width:100%;">
            <table style="width:100%; border-collapse:collapse;">
                <tr><td style="color:#555">Subtotal</td><td class="r">Rp 1.000.000</td></tr>
                <tr style="display:none;"><td>PPN 11%</td><td class="r">Rp 110.000</td></tr>
                <tr><td>TOTAL</td><td class="r">Rp 1.110.000</td></tr>
            </table>
        </div>
        <p style="margin-top:12px; font-size:10px; color:#666; border-top: 1px solid #ddd; padding-top: 6px;">
            Dicetak {{ date('d/m/Y H:i') }} — <span id="preview-footer">Terima kasih telah berkunjung!</span><br>
            <span id="preview-brand-name">Wisata Scuba Center</span>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function updatePreview() {
                var companyHeader = document.querySelector('input[name="pdf_company_header"]').value || 'Wisata Scuba Center';
                var receiptFooter = document.querySelector('textarea[name="pdf_receipt_footer"]').value || 'Terima kasih telah berkunjung!';
                var showTax = document.querySelector('input[name="pdf_show_tax"]').checked;
                var paperSize = document.querySelector('select[name="pdf_paper_size"]').value;

                // Update brand name
                document.getElementById('preview-brand-name').innerText = companyHeader;

                // Update footer
                document.getElementById('preview-footer').innerText = receiptFooter;

                // Toggle tax row visibility
                var taxRows = document.querySelectorAll('#pdf-preview .totals tr');
                taxRows.forEach(function(row, i) {
                    if (i === 0) {
                        row.style.display = showTax ? '' : 'none';
                    }
                });

                // Adjust width based on paper size
                var preview = document.getElementById('pdf-preview');
                preview.style.width = paperSize === 'a4' ? '100%' : '90%';
            }

            // Update preview when form changes
            document.querySelectorAll('input[name="pdf_company_header"], textarea[name="pdf_receipt_footer"], input[name="pdf_show_tax"], select[name="pdf_paper_size"]').forEach(function(el) {
                el.addEventListener('change', updatePreview);
                el.addEventListener('input', updatePreview);
            });

            // Initial preview
            updatePreview();

            // Toggle preview visibility on save click
            var saveBtn = document.querySelector('[dusk="save-templates"]');
            if (saveBtn) {
                saveBtn.addEventListener('click', function(e) {
                    var preview = document.getElementById('pdf-preview');
                    if (preview.style.display === 'none' || preview.style.display === '') {
                        preview.style.display = 'block';
                    } else {
                        preview.style.display = 'none';
                    }
                });
            }
        });
    </script>
</form>
