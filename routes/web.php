<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Discount\DiscountController;
use App\Http\Controllers\Equipment\EquipmentController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\MarketingCampaignController;
use App\Http\Controllers\Finance\PayrollPeriodController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Product\ProductCategoryController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Controllers\Transaction\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/', fn () => redirect()->route('login'));
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/cabang', [\App\Http\Controllers\BranchDashboardController::class, 'index'])
        ->name('dashboard.branches')
        ->middleware('permission:dashboard.view');
    Route::get('dashboard/cabang/{branch}', [\App\Http\Controllers\BranchDashboardController::class, 'show'])
        ->name('dashboard.branch')
        ->middleware('permission:dashboard.view');

    Route::resource('branches', BranchController::class)
        ->except(['show'])
        ->middleware('permission:branches.view');

    Route::resource('customers', CustomerController::class)
        ->middleware('permission:customers.view');

    Route::resource('products', ProductController::class)
        ->except(['show'])
        ->middleware('permission:products.view');

    Route::resource('product-categories', ProductCategoryController::class)
        ->except(['show'])
        ->middleware('permission:products.view');

    Route::resource('equipment', EquipmentController::class)
        ->except(['show'])
        ->middleware('permission:equipment.view');

    Route::post('equipment/{unit}/maintenance', [EquipmentController::class, 'addMaintenance'])
        ->name('equipment.maintenance')
        ->middleware('permission:equipment.edit');

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index')
        ->middleware('permission:inventory.view');
    Route::get('inventory/create', [InventoryController::class, 'create'])->name('inventory.create')
        ->middleware('permission:inventory.edit');
    Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store')
        ->middleware('permission:inventory.edit');

    Route::get('schedules/create', [ScheduleController::class, 'create'])
        ->name('schedules.create')
        ->middleware('permission:schedules.create');
    Route::post('schedules', [ScheduleController::class, 'store'])
        ->name('schedules.store')
        ->middleware('permission:schedules.create');
    Route::get('schedules/{schedule}/edit', [ScheduleController::class, 'edit'])
        ->name('schedules.edit')
        ->middleware('permission:schedules.view');
    Route::put('schedules/{schedule}', [ScheduleController::class, 'update'])
        ->name('schedules.update')
        ->middleware('permission:schedules.edit');

    Route::resource('schedules', ScheduleController::class)
        ->except(['create', 'store', 'edit', 'update'])
        ->middleware('permission:schedules.view');

    Route::patch('schedules/{schedule}/status', [ScheduleController::class, 'changeStatus'])
        ->name('schedules.status')
        ->middleware('permission:schedules.edit');

    Route::resource('discounts', DiscountController::class)
        ->except(['show'])
        ->middleware('permission:discounts.view');

    Route::resource('expenses', ExpenseController::class)
        ->except(['edit', 'update', 'destroy'])
        ->middleware('permission:expenses.view');

    Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])
        ->name('expenses.edit')->middleware('permission:expenses.edit');
    Route::put('expenses/{expense}', [ExpenseController::class, 'update'])
        ->name('expenses.update')->middleware('permission:expenses.edit');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])
        ->name('expenses.destroy')->middleware('permission:expenses.delete');

    Route::resource('marketing-campaigns', MarketingCampaignController::class)
        ->except(['show'])
        ->middleware('permission:expenses.view|discounts.view');

    Route::get('payroll/create', [PayrollPeriodController::class, 'create'])
        ->name('payroll.create')
        ->middleware('permission:payroll.create');
    Route::post('payroll', [PayrollPeriodController::class, 'store'])
        ->name('payroll.store')
        ->middleware('permission:payroll.create');

    Route::get('payroll/{payroll}', [PayrollPeriodController::class, 'show'])
        ->name('payroll.show')
        ->middleware('permission:payroll.view');

    Route::post('payroll/{payroll}/generate', [PayrollPeriodController::class, 'generate'])
        ->name('payroll.generate')
        ->middleware('permission:payroll.edit');
    Route::put('payroll/{payroll}/items/{item}/deduction', [PayrollPeriodController::class, 'updateDeduction'])
        ->name('payroll.deduction')
        ->middleware('permission:payroll.edit');
    Route::post('payroll/{payroll}/approve', [PayrollPeriodController::class, 'approve'])
        ->name('payroll.approve')
        ->middleware('permission:payroll.approve');
    Route::post('payroll/{payroll}/close', [PayrollPeriodController::class, 'close'])
        ->name('payroll.close')
        ->middleware('permission:payroll.approve');

    Route::get('payroll', [PayrollPeriodController::class, 'index'])
        ->name('payroll.index')
        ->middleware('permission:payroll.view');

    Route::get('reports', [ReportController::class, 'index'])
        ->name('reports.index')
        ->middleware('permission:reports.view');
    Route::get('reports/export', [ReportController::class, 'export'])
        ->name('reports.export')
        ->middleware('permission:reports.export');
    Route::get('reports/pdf', [ReportController::class, 'pdf'])
        ->name('reports.pdf')
        ->middleware('permission:reports.view');

    Route::get('notifications', \App\Http\Controllers\Notification\NotificationController::class.'@index')
        ->name('notifications.index')
        ->middleware('permission:notifications.view');

    Route::get('bookings/calendar', [\App\Http\Controllers\Booking\BookingController::class, 'calendar'])
        ->name('bookings.calendar')->middleware('permission:bookings.view');
    Route::post('bookings/{booking}/cancel', [\App\Http\Controllers\Booking\BookingController::class, 'cancel'])
        ->name('bookings.cancel')->middleware('permission:bookings.edit');
    Route::post('bookings/{booking}/check-in', [\App\Http\Controllers\Booking\BookingController::class, 'checkIn'])
        ->name('bookings.check-in')->middleware('permission:bookings.edit');
    Route::post('bookings/{booking}/check-out', [\App\Http\Controllers\Booking\BookingController::class, 'checkOut'])
        ->name('bookings.check-out')->middleware('permission:bookings.edit');
    Route::post('bookings/{booking}/payments', [\App\Http\Controllers\Booking\BookingController::class, 'addPayment'])
        ->name('bookings.payments.store')->middleware('permission:bookings.view');

    Route::resource('bookings', \App\Http\Controllers\Booking\BookingController::class)
        ->middleware('permission:bookings.view');

    Route::post('transactions/discount-preview', [TransactionController::class, 'discountPreview'])
        ->name('transactions.discount.preview')
        ->middleware('permission:transactions.create')
        ->middleware('throttle:30,1');

    Route::post('transactions/{transaction}/payments', [TransactionController::class, 'addPayment'])
        ->name('transactions.payments.store')
        ->middleware('permission:transactions.create');
    Route::get('transactions/{transaction}/pdf', [TransactionController::class, 'pdf'])
        ->name('transactions.pdf')
        ->middleware('permission:transactions.view');
    Route::post('transactions/{transaction}/void', [TransactionController::class, 'void'])
        ->name('transactions.void')
        ->middleware('permission:transactions.void');

    Route::get('transactions/invoices', [TransactionController::class, 'invoices'])
        ->name('transactions.invoices')
        ->middleware('permission:transactions.view');
    Route::post('bookings/{booking}/invoice', [TransactionController::class, 'issueInvoice'])
        ->name('bookings.invoice')
        ->middleware('permission:bookings.edit');
    Route::post('transactions/{transaction}/send-invoice', [TransactionController::class, 'sendInvoiceEmail'])
        ->name('transactions.send-invoice')
        ->middleware('permission:transactions.view');

    Route::resource('transactions', TransactionController::class)
        ->except(['edit', 'update', 'destroy', 'store'])
        ->middleware('permission:transactions.view');
    Route::post('transactions', [TransactionController::class, 'store'])
        ->name('transactions.store')
        ->middleware('permission:transactions.create')
        ->middleware('throttle:30,1');

    Route::post('schedules/{schedule}/participants', [ScheduleController::class, 'addParticipant'])
        ->name('schedules.participants.store')
        ->middleware('permission:schedules.edit');
    Route::delete('schedules/{schedule}/participants/{participant}', [ScheduleController::class, 'removeParticipant'])
        ->name('schedules.participants.destroy')
        ->middleware('permission:schedules.edit');

    Route::post('schedules/{schedule}/staff', [ScheduleController::class, 'addStaff'])
        ->name('schedules.staff.store')
        ->middleware('permission:schedules.edit');
    Route::delete('schedules/{schedule}/staff/{staffMember}', [ScheduleController::class, 'removeStaff'])
        ->name('schedules.staff.destroy')
        ->middleware('permission:schedules.edit');
});
