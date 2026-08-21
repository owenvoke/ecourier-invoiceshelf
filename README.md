# InvoiceShelf - eCourier Module

[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Static Analysis][ico-static-analysis]][link-static-analysis]

An InvoiceShelf module that sends electronic invoices through [eCourier](https://ecourier.io).

## Install

Clone the module into the host application's `Modules` directory and build it:

```shell
cd Modules
git clone https://github.com/utecca/ecourier-invoiceshelf.git Ecourier
cd Ecourier
npm install && npm run build
```

Install the SDK in the **host** application, then run the module's migrations:

```shell
composer require ecourier/ecourier   # in the InvoiceShelf root
php artisan module:migrate Ecourier
```

InvoiceShelf 2.x does not merge a module's own Composer requirements into the
host autoloader, so the SDK has to be installed at the host root. The frontend
bundle is built at install time and is not committed.

Enable the module under **Settings → Modules**.

## Usage

Configure the module per company under **Settings → eCourier**:

- **Connection** — API key (a `pk_test_` key targets the test network, `pk_live_`
  production), network, and whether invoices are sent automatically when marked
  as sent.
- **Recipients** — each customer's network identifier, which the host
  has no field for. Address fields are optional overrides; left empty, the
  customer's billing address is used.
- **Submissions** — every delivery attempt, with the accepted document ID or the
  reason it failed.

### Sending an invoice

Open an invoice and choose **Send via eCourier** from its actions menu. An
invoice that has already been sent asks for confirmation first, so a second copy
is never delivered by accident.

Invoices can also be sent through the module's API:

```http
POST /api/m/ecourier/invoices/{invoiceId}/send
```

Either way, sending is authorized with the host's own `send-invoice` policy. An
invoice that has already been sent returns `409`; pass `force` to send it
again.

### Deliberate failures

The mapper refuses to send rather than submit a document the network would
reject, recording the reason against the submission:

- Totals that do not balance, which is what a document-level discount produces —
  eCourier's JSON totals have no allowance field to represent it.
- Line totals that do not sum to the subtotal, which Peppol requires.
- A currency eCourier does not support, or a missing currency, customer, or line
  items.
- Missing sender configuration, or a customer with no registered recipient.

### Limitations

- Credit notes are sent as invoices; 2.x has no credit-note document type.
- VAT rates are read per line, but the category code is one company-wide setting.
- Amounts are sent in the invoice's own currency; exchange rates are ignored.

## Change log

Please see [GitHub Releases][link-github-releases] for more information on what
has changed recently.

## Testing

```shell
composer test
```

This runs Pint, PHPStan, and Pest. The suite covers the logic that runs without
a host application; anything touching `App\Models` needs a real InvoiceShelf
install, and those classes are declared under `stubs/` for static analysis only.

## Security

If you discover any security related issues, please use GitHub's private
vulnerability reporting instead of the issue tracker.

## Credits

- [Owen Voke][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-github-actions]: https://img.shields.io/github/actions/workflow/status/utecca/ecourier-invoiceshelf/tests.yml?branch=1.x&style=flat-square&label=Tests
[ico-static-analysis]: https://img.shields.io/github/actions/workflow/status/utecca/ecourier-invoiceshelf/static.yml?branch=1.x&style=flat-square&label=Static%20Analysis

[link-github-actions]: https://github.com/utecca/ecourier-invoiceshelf/actions
[link-static-analysis]: https://github.com/utecca/ecourier-invoiceshelf/actions/workflows/static.yml
[link-github-releases]: https://github.com/utecca/ecourier-invoiceshelf/releases
[link-author]: https://github.com/owenvoke
[link-contributors]: ../../contributors
