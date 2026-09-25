<?php

namespace App\Support;

use App\Models\AcquisitionBid;
use App\Models\AcquisitionOffer;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Builds the sidebar nav-group arrays for the three dashboards. Mirrors the
 * `navGroups`/`navItems` arrays hardcoded in the Forge Admin/Author/Customer
 * mockups, but with real counts pulled from the database.
 */
class Nav
{
    public static function admin(string $active): array
    {
        $reviewCount = ProductVersion::where('status', 'pending')->count();
        $requestCount = ServiceRequest::where('status', 'open')->count();
        $ticketCount = Ticket::whereIn('status', ['open', 'breaching'])->count();
        $acquisitionCount = Product::acquisition()->whereIn('acquisition_status', ['available', 'pending_transfer'])->count();

        return [
            [
                'group' => 'Marketplace',
                'items' => [
                    self::item('overview', 'Overview', 'admin.overview', '◎', $active),
                    self::item('review', 'Review queue', 'admin.review.index', '✓', $active, $reviewCount ?: null),
                    self::item('products', 'Products', 'admin.products.index', '▤', $active),
                    self::item('acquisitions', 'Project sales', 'admin.acquisitions.index', '⬡', $active, $acquisitionCount ?: null),
                    self::item('authors', 'Authors', 'admin.authors.index', '◈', $active),
                    self::item('customers', 'Customers', 'admin.customers.index', '◫', $active),
                ],
            ],
            [
                'group' => 'Commerce',
                'items' => [
                    self::item('orders', 'Orders & refunds', 'admin.orders.index', '▦', $active),
                    self::item('licenses', 'Licenses & support', 'admin.licenses.index', '⌸', $active),
                    self::item('payouts', 'Payouts & finance', 'admin.payouts.index', '◇', $active),
                ],
            ],
            [
                'group' => 'Studio services',
                'items' => [
                    self::item('requests', 'Service pipeline', 'admin.requests.index', '⇄', $active, $requestCount ?: null),
                    self::item('projects', 'Delivery projects', 'admin.projects.index', '▥', $active),
                ],
            ],
            [
                'group' => 'Operations',
                'items' => [
                    self::item('tickets', 'Support', 'admin.tickets.index', '✉', $active, $ticketCount ?: null),
                    self::item('content', 'Content', 'admin.content.edit', '▧', $active),
                    self::item('audit', 'Audit log', 'admin.audit.index', '≡', $active),
                    self::item('settings', 'Settings', 'admin.settings.edit', '⚙', $active),
                ],
            ],
        ];
    }

    public static function author(string $active): array
    {
        /** @var User $user */
        $user = Auth::user();
        $productCount = Product::where('author_id', $user->id)->software()->count();
        $acquisitionCount = Product::where('author_id', $user->id)->acquisition()->count();
        $submissionCount = ProductVersion::whereHas('product', fn ($q) => $q->where('author_id', $user->id))
            ->whereIn('status', ['pending', 'changes_requested'])->count();
        $reviewCount = Review::whereHas('product', fn ($q) => $q->where('author_id', $user->id))
            ->whereNull('reply_body')->count();
        $ticketCount = Ticket::whereHas('product', fn ($q) => $q->where('author_id', $user->id))
            ->whereIn('status', ['open', 'breaching'])->count();

        $items = [
            self::item('overview', 'Overview', 'author.overview', '◎', $active),
            self::item('products', 'Products', 'author.products.index', '▤', $active, $productCount ?: null),
            self::item('acquisitions', 'Project sales', 'author.acquisitions.index', '⬡', $active, $acquisitionCount ?: null),
            self::item('submissions', 'Submissions', 'author.submissions.index', '✓', $active, $submissionCount ?: null),
            self::item('sales', 'Sales', 'author.sales.index', '▦', $active),
            self::item('payouts', 'Earnings', 'author.payouts.index', '◈', $active),
            self::item('reviews', 'Reviews', 'author.reviews.index', '★', $active, $reviewCount ?: null),
            self::item('support', 'Buyer support', 'author.support.index', '✉', $active, $ticketCount ?: null),
            self::item('analytics', 'Analytics', 'author.analytics.index', '▥', $active),
            self::item('settings', 'Settings', 'author.settings.edit', '⚙', $active),
        ];

        return [['group' => null, 'items' => $items]];
    }

    public static function account(string $active): array
    {
        /** @var User $user */
        $user = Auth::user();
        $purchaseCount = $user->orders()->count();
        $licenseCount = $user->licenses()->where('status', 'active')->count();
        $ticketCount = $user->openedTickets()->whereIn('status', ['open', 'waiting', 'breaching'])->count();
        $savedCount = $user->savedItems()->count();
        $serviceCount = $user->serviceProjects()->count();
        $acquisitionCount = AcquisitionOffer::where('buyer_id', $user->id)->count()
            + AcquisitionBid::where('bidder_id', $user->id)->count()
            + Product::where('sold_to_user_id', $user->id)->count();

        $items = [
            self::item('overview', 'Overview', 'account.overview', '◎', $active),
            self::item('purchases', 'Purchases', 'account.purchases.index', '▤', $active, $purchaseCount ?: null),
            self::item('acquisitions', 'Project deals', 'account.acquisitions.index', '⬡', $active, $acquisitionCount ?: null),
            self::item('downloads', 'Downloads', 'account.downloads.index', '↓', $active),
            self::item('licenses', 'Licenses', 'account.licenses.index', '⌸', $active, $licenseCount ?: null),
            self::item('services', 'Service orders', 'account.services.index', '⇄', $active, $serviceCount ?: null),
            self::item('invoices', 'Invoices', 'account.invoices.index', '▦', $active),
            self::item('support', 'Support', 'account.support.index', '✉', $active, $ticketCount ?: null),
            self::item('saved', 'Saved items', 'account.saved.index', '✦', $active, $savedCount ?: null),
            self::item('settings', 'Settings', 'account.settings.edit', '⚙', $active),
        ];

        return [['group' => null, 'items' => $items]];
    }

    private static function item(string $key, string $label, string $route, string $mark, string $active, ?int $badge = null): array
    {
        return [
            'label' => $label,
            'route' => $route,
            'mark' => $mark,
            'badge' => $badge,
            'active' => $active === $key,
        ];
    }
}
