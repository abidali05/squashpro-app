<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\{
    DashboardController,
    ClientController,
    ContractController,
    ProductController,
    OrderController,
    SettingsController,
    SeasonController,
    VetsController,
};
use App\Http\Controllers\Admin_vet\ContractController as Admin_vetContractController;
use App\Http\Controllers\Admin_vet\DashboardController as Admin_vetDashboardController;
use App\Http\Controllers\Admin_vet\OrderController as Admin_vetOrderController;
use App\Http\Controllers\Admin_vet\ProductController as Admin_vetProductController;
use App\Http\Controllers\Admin_vet\SeasonController as Admin_vetSeasonController;
use App\Http\Controllers\Client\ContractController as ClientContractController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\OrderController as ClientOrderController;
use App\Http\Controllers\Client\ProductController as ClientProductController;
use App\Http\Controllers\Client\SeasonController as ClientSeasonController;
use App\Http\Controllers\Client\VetsController as ClientVetsController;
use App\Http\Controllers\Client_vet\ClientController as Client_vetClientController;
use App\Http\Controllers\Client_vet\DashboardController as Client_vetDashboardController;
use App\Http\Controllers\Client_vet\OrderController as Client_vetOrderController;
use App\Http\Controllers\Client_vet\SeasonController as Client_vetSeasonController;

Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('home');
Route::middleware(['is_role:admin', 'verified'])->prefix("admin")->as("admin.")->group(function () {
    Route::controller(DashboardController::class)
    ->prefix('dashboard')
    ->as('dashboard.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(ClientController::class)
    ->prefix('clients')
    ->as('clients.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(VetsController::class)
    ->prefix('vets')
    ->as('vets.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(SettingsController::class)
    ->prefix('settings')
    ->as('settings.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(ContractController::class)
    ->prefix('contracts')
    ->as('contracts.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(ProductController::class)
    ->prefix('products')
    ->as('products.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(OrderController::class)
    ->prefix('orders')
    ->as('orders.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(SeasonController::class)
    ->prefix('seasons')
    ->as('seasons.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });
});

Route::middleware(['is_role:admin_vet', 'verified'])->prefix("adminVet")->as("adminVet.")->group(function () {
    Route::controller(Admin_vetDashboardController::class)
    ->prefix('dashboard')
    ->as('dashboard.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(Admin_vetContractController::class)
    ->prefix('contracts')
    ->as('contracts.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(Admin_vetSeasonController::class)
    ->prefix('seasons')
    ->as('seasons.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(Admin_vetProductController::class)
    ->prefix('products')
    ->as('products.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(Admin_vetOrderController::class)
    ->prefix('orders')
    ->as('orders.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });
});

Route::middleware(['is_role:client', 'verified'])->prefix("client")->as("client.")->group(function () {
    Route::controller(ClientDashboardController::class)
    ->prefix('dashboard')
    ->as('dashboard.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });


    Route::controller(ClientContractController::class)
    ->prefix('contracts')
    ->as('contracts.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(ClientSeasonController::class)
    ->prefix('seasons')
    ->as('seasons.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(ClientVetsController::class)
    ->prefix('vets')
    ->as('vets.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(ClientProductController::class)
    ->prefix('products')
    ->as('products.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(ClientOrderController::class)
    ->prefix('orders')
    ->as('orders.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });
});

Route::middleware(['is_role:client_vet', 'verified'])->prefix("clientVet")->as("clientVet.")->group(function () {
    Route::controller(Client_vetDashboardController::class)
    ->prefix('dashboard')
    ->as('dashboard.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(Client_vetClientController::class)
    ->prefix('clients')
    ->as('clients.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(Client_vetSeasonController::class)
    ->prefix('seasons')
    ->as('seasons.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });

    Route::controller(Client_vetOrderController::class)
    ->prefix('orders')
    ->as('orders.')
    ->group(function () {
        Route::get('', 'index')->name('index');
    });
});


// // layout
// Route::get('/layouts/without-menu', [WithoutMenu::class, 'index'])->name('layouts-without-menu');
// Route::get('/layouts/without-navbar', [WithoutNavbar::class, 'index'])->name('layouts-without-navbar');
// Route::get('/layouts/fluid', [Fluid::class, 'index'])->name('layouts-fluid');
// Route::get('/layouts/container', [Container::class, 'index'])->name('layouts-container');
// Route::get('/layouts/blank', [Blank::class, 'index'])->name('layouts-blank');

// // pages
// Route::get('/pages/account-settings-account', [AccountSettingsAccount::class, 'index'])->name('pages-account-settings-account');
// Route::get('/pages/account-settings-notifications', [AccountSettingsNotifications::class, 'index'])->name('pages-account-settings-notifications');
// Route::get('/pages/account-settings-connections', [AccountSettingsConnections::class, 'index'])->name('pages-account-settings-connections');
// Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');
// Route::get('/pages/misc-under-maintenance', [MiscUnderMaintenance::class, 'index'])->name('pages-misc-under-maintenance');

// // cards
// Route::get('/cards/basic', [CardBasic::class, 'index'])->name('cards-basic');

// // User Interface
// Route::get('/ui/accordion', [Accordion::class, 'index'])->name('ui-accordion');
// Route::get('/ui/alerts', [Alerts::class, 'index'])->name('ui-alerts');
// Route::get('/ui/badges', [Badges::class, 'index'])->name('ui-badges');
// Route::get('/ui/buttons', [Buttons::class, 'index'])->name('ui-buttons');
// Route::get('/ui/carousel', [Carousel::class, 'index'])->name('ui-carousel');
// Route::get('/ui/collapse', [Collapse::class, 'index'])->name('ui-collapse');
// Route::get('/ui/dropdowns', [Dropdowns::class, 'index'])->name('ui-dropdowns');
// Route::get('/ui/footer', [Footer::class, 'index'])->name('ui-footer');
// Route::get('/ui/list-groups', [ListGroups::class, 'index'])->name('ui-list-groups');
// Route::get('/ui/modals', [Modals::class, 'index'])->name('ui-modals');
// Route::get('/ui/navbar', [Navbar::class, 'index'])->name('ui-navbar');
// Route::get('/ui/offcanvas', [Offcanvas::class, 'index'])->name('ui-offcanvas');
// Route::get('/ui/pagination-breadcrumbs', [PaginationBreadcrumbs::class, 'index'])->name('ui-pagination-breadcrumbs');
// Route::get('/ui/progress', [Progress::class, 'index'])->name('ui-progress');
// Route::get('/ui/spinners', [Spinners::class, 'index'])->name('ui-spinners');
// Route::get('/ui/tabs-pills', [TabsPills::class, 'index'])->name('ui-tabs-pills');
// Route::get('/ui/toasts', [Toasts::class, 'index'])->name('ui-toasts');
// Route::get('/ui/tooltips-popovers', [TooltipsPopovers::class, 'index'])->name('ui-tooltips-popovers');
// Route::get('/ui/typography', [Typography::class, 'index'])->name('ui-typography');

// // extended ui
// Route::get('/extended/ui-perfect-scrollbar', [PerfectScrollbar::class, 'index'])->name('extended-ui-perfect-scrollbar');
// Route::get('/extended/ui-text-divider', [TextDivider::class, 'index'])->name('extended-ui-text-divider');

// // icons
// Route::get('/icons/icons-mdi', [MdiIcons::class, 'index'])->name('icons-mdi');

// // form elements
// Route::get('/forms/basic-inputs', [BasicInput::class, 'index'])->name('forms-basic-inputs');
// Route::get('/forms/input-groups', [InputGroups::class, 'index'])->name('forms-input-groups');

// // form layouts
// Route::get('/form/layouts-vertical', [VerticalForm::class, 'index'])->name('form-layouts-vertical');
// Route::get('/form/layouts-horizontal', [HorizontalForm::class, 'index'])->name('form-layouts-horizontal');

// // tables
// Route::get('/tables/basic', [TablesBasic::class, 'index'])->name('tables-basic');
// Route::get('/tables/users', [UsersTable::class, 'index'])->name('tables-users');
// Route::get('/tables/client', [ClientTable::class, 'index'])->name('tables-users');
// Route::get('/tables/vets', [VetsTable::class, 'index'])->name('tables-users');
// Route::get('/tables/products', [Products::class, 'index'])->name('tables-products');
// Route::get('/tables/orders', [OrdersTable::class, 'index'])->name('tables-orders');
// Route::get('/tables/contracts', [ContractsTable::class, 'index'])->name('tables-contracts');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });


require __DIR__.'/auth.php';
