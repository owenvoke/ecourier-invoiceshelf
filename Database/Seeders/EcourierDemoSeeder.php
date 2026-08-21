<?php

declare(strict_types=1);

namespace Modules\Ecourier\Database\Seeders;

use App\Models\Address;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tax;
use App\Models\TaxType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Ecourier\Models\EcourierRecipient;
use RuntimeException;

/**
 * Local test data for the eCourier module.
 *
 * Creates one customer, one invoice, a matching Peppol recipient, and a working
 * set of module settings, so the settings screen and the send path can be
 * exercised without hand-entering everything.
 *
 * The figures are deliberately consistent: the line totals sum to the subtotal
 * and subtotal + tax equals the total, which is what the mapper insists on
 * before it will submit a document.
 *
 *     php artisan db:seed --class="Modules\Ecourier\Database\Seeders\EcourierDemoSeeder"
 *
 * The invoice is left in DRAFT, so you can mark it as sent to exercise
 * auto-send, or POST to the module's send endpoint for a manual submission.
 */
class EcourierDemoSeeder extends Seeder
{
    /** Shaped like a real test key, but not one. Replace before sending. */
    private const DEMO_API_KEY = 'pk_test_000000000000000000000000000000';

    /** Passes the Danish mod-11 CVR check. */
    private const DEMO_SENDER_CVR = '12345674';

    /** Passes the GS1 mod-10 check digit. */
    private const DEMO_RECIPIENT_GLN = '5790000123452';

    private const INVOICE_NUMBER = 'ECOURIER-DEMO-1';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('EcourierDemoSeeder creates test data and must not run in production.');
        }

        /** @var Company|null $company */
        $company = Company::query()->orderBy('id')->first();

        if ($company === null) {
            throw new RuntimeException('No company exists yet - finish the InvoiceShelf installation first.');
        }

        /** @var Currency|null $currency */
        $currency = Currency::query()->where('code', 'DKK')->first();

        if ($currency === null) {
            throw new RuntimeException('The DKK currency is missing; seed the host currencies first.');
        }

        /** @var Country|null $country */
        $country = Country::query()->where('code', 'DK')->first();

        $customer = $this->customer($company, $currency, $country);
        $invoice = $this->invoice($company, $currency, $customer);

        $this->recipient($company, $customer);
        $this->settings($company);

        $this->command->info("Company:  {$company->name} (#{$company->id})");
        $this->command->info("Customer: {$customer->name} (#{$customer->id})");
        $this->command->info("Invoice:  {$invoice->invoice_number} (#{$invoice->id}) - 1250.00 DKK, DRAFT");
        $this->command->info('Recipient: GLN '.self::DEMO_RECIPIENT_GLN);
        $this->command->warn('Settings use a placeholder API key - set a real pk_test_ key before sending.');
    }

    private function customer(Company $company, Currency $currency, Country|null $country): Customer
    {
        /** @var Customer $customer */
        $customer = Customer::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'email' => 'finance@beta-demo.example',
            ],
            [
                'name' => 'Beta ApS',
                'company_name' => 'Beta ApS',
                'contact_name' => 'Ada Lovelace',
                'phone' => '+45 12 34 56 78',
                'enable_portal' => false,
                'currency_id' => $currency->id,
            ],
        );

        Address::query()->firstOrCreate(
            [
                'customer_id' => $customer->id,
                'type' => Address::BILLING_TYPE,
            ],
            [
                'company_id' => $company->id,
                'name' => 'Beta ApS',
                'address_street_1' => 'Havnegade 4',
                'city' => 'Aarhus',
                'zip' => '8000',
                'country_id' => $country?->id,
                'phone' => '+45 12 34 56 78',
            ],
        );

        return $customer;
    }

    private function invoice(Company $company, Currency $currency, Customer $customer): Invoice
    {
        $subTotal = 100_000;   // 1000.00
        $tax = 25_000;         //  250.00 at 25%
        $total = 125_000;      // 1250.00

        /** @var Invoice $invoice */
        $invoice = Invoice::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'invoice_number' => self::INVOICE_NUMBER,
            ],
            [
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'reference_number' => 'PO-DEMO-42',
                'sequence_number' => 9001,
                'customer_sequence_number' => 1,
                'template_name' => 'invoice1',
                'status' => Invoice::STATUS_DRAFT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'tax_per_item' => 'NO',
                'discount_per_item' => 'NO',
                'tax_included' => false,
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'sub_total' => $subTotal,
                'tax' => $tax,
                'total' => $total,
                'due_amount' => $total,
                'base_sub_total' => $subTotal,
                'base_tax' => $tax,
                'base_total' => $total,
                'base_due_amount' => $total,
                'base_discount_val' => 0,
                'exchange_rate' => 1,
                'notes' => 'Demo invoice created by the eCourier module seeder.',
                'unique_hash' => Str::random(60),
                'customer_id' => $customer->id,
                'currency_id' => $currency->id,
            ],
        );

        if ($invoice->items()->count() === 0) {
            $this->lines($company, $invoice);
        }

        if ($invoice->taxes()->count() === 0) {
            $this->tax($company, $invoice, $tax);
        }

        return $invoice;
    }

    private function lines(Company $company, Invoice $invoice): void
    {
        // 2 x 300.00 + 1 x 400.00 = 1000.00, matching the invoice subtotal.
        $lines = [
            ['name' => 'Consulting', 'description' => 'Advisory work', 'quantity' => 2, 'price' => 30_000, 'total' => 60_000, 'unit_name' => 'HUR'],
            ['name' => 'Support', 'description' => 'Support retainer', 'quantity' => 1, 'price' => 40_000, 'total' => 40_000, 'unit_name' => null],
        ];

        foreach ($lines as $line) {
            $invoice->items()->create([
                ...$line,
                'company_id' => $company->id,
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'tax' => 0,
                'base_price' => $line['price'],
                'base_total' => $line['total'],
                'base_discount_val' => 0,
                'base_tax' => 0,
                'exchange_rate' => 1,
            ]);
        }
    }

    private function tax(Company $company, Invoice $invoice, int $amount): void
    {
        /** @var TaxType $taxType */
        $taxType = TaxType::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'VAT 25% (demo)',
            ],
            [
                'percent' => 25,
                'compound_tax' => 0,
                'collective_tax' => 0,
                'description' => 'Created by the eCourier module seeder.',
            ],
        );

        Tax::query()->create([
            'tax_type_id' => $taxType->id,
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => $taxType->name,
            'percent' => 25,
            'amount' => $amount,
            'compound_tax' => 0,
            'base_amount' => $amount,
            'exchange_rate' => 1,
        ]);
    }

    private function recipient(Company $company, Customer $customer): void
    {
        EcourierRecipient::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'customer_id' => $customer->id,
            ],
            [
                'scheme' => 'GLN',
                'identifier' => self::DEMO_RECIPIENT_GLN,
                'name' => 'Beta ApS',
                'vat_id' => 'DK87654321',
                'registration_number' => '87654321',
                'street' => 'Havnegade 4',
                'city' => 'Aarhus',
                'postal_code' => '8000',
                'country' => 'DK',
            ],
        );
    }

    private function settings(Company $company): void
    {
        CompanySetting::setSettings([
            'ecourier_enabled' => '1',
            'ecourier_auto_send' => '0',
            'ecourier_api_key' => self::DEMO_API_KEY,
            'ecourier_channel' => 'Peppol',
            'ecourier_sender_scheme' => 'DK:CVR',
            'ecourier_sender_id' => self::DEMO_SENDER_CVR,
            'ecourier_sender_vat_id' => 'DK'.self::DEMO_SENDER_CVR,
            'ecourier_sender_registration_number' => self::DEMO_SENDER_CVR,
            'ecourier_tax_category' => 'S',
            'ecourier_tax_percent' => '25',
            'ecourier_unit_code' => 'C62',
            'ecourier_payment_means_code' => '42',
            'ecourier_account_scheme' => 'DK:BBAN',
            'ecourier_account_id' => '12340000000000',
            'ecourier_bank_name' => 'Demo Bank',
            'ecourier_payment_terms_note' => 'Net 14 days',
        ], $company->id);
    }
}
