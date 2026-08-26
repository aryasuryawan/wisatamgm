<?php

namespace App\Domain\Customer\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $certIds
     */
    public function createCustomer(array $data, array $certs = []): Customer
    {
        return DB::transaction(function () use ($data, $certs) {
            /** @var Customer $customer */
            $customer = Customer::create($data);

            foreach ($certs as $cert) {
                $customer->certifications()->create($cert);
            }

            AuditLogger::log('customer.created', $customer, null, $customer->toArray());

            return $customer;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCustomer(Customer $customer, array $data, array $certs = []): void
    {
        DB::transaction(function () use ($customer, $data, $certs) {
            $before = $customer->toArray();

            $customer->fill($data)->save();

            if (! empty($certs)) {
                $customer->certifications()->delete();
                foreach ($certs as $cert) {
                    $customer->certifications()->create($cert);
                }
            }

            AuditLogger::log('customer.updated', $customer, $before, $customer->toArray());
        });
    }

    public function deleteCustomer(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            $before = $customer->toArray();
            $customer->certifications()->delete();
            $customer->delete();
            AuditLogger::log('customer.deleted', $customer, $before, null);
        });
    }
}
