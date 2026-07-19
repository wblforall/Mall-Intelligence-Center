<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Mobile API
$routes->group('api', function ($routes) {
    // CORS preflight
    $routes->options('(:any)', 'Api\AuthController::options');

    // Auth
    $routes->post('auth/login',  'Api\AuthController::login');
    $routes->post('auth/logout', 'Api\AuthController::logout');
    $routes->get('auth/me',      'Api\AuthController::me');

    // Dashboard
    $routes->get('dashboard/summary', 'Api\DashboardController::summary');

    // Events
    $routes->get('events',     'Api\EventsController::index');
    $routes->get('events/(:num)', 'Api\EventsController::show/$1');

    // Media Promo Approval
    $routes->get('media-promo/approvals',        'Api\PromoMediaController::approvals');
    $routes->post('media-promo/(:num)/approve',  'Api\PromoMediaController::approve/$1');
    $routes->post('media-promo/(:num)/reject',   'Api\PromoMediaController::reject/$1');

    // IDP Approval
    $routes->get('idp/approvals',        'Api\IdpController::approvals');
    $routes->get('idp/(:num)',           'Api\IdpController::show/$1');
    $routes->post('idp/(:num)/approve',  'Api\IdpController::approve/$1');
    $routes->post('idp/(:num)/reject',   'Api\IdpController::reject/$1');

    // PIP Approval
    $routes->get('pip/approvals',        'Api\PipController::approvals');
    $routes->get('pip/(:num)',           'Api\PipController::show/$1');
    $routes->post('pip/(:num)/approve',  'Api\PipController::approve/$1');
    $routes->post('pip/(:num)/reject',   'Api\PipController::reject/$1');

    // Push token
    $routes->post('auth/push-token', 'Api\AuthController::savePushToken');
});

// Auth
$routes->get('login', 'Auth::index');
$routes->post('login', 'Auth::login');
$routes->get('logout', 'Auth::logout');
$routes->get('change-password', 'Auth::changePassword');
$routes->post('change-password', 'Auth::savePassword');
$routes->get('forgot-password', 'Auth::forgotPassword');
$routes->post('forgot-password', 'Auth::sendResetLink');
$routes->get('reset-password/(:segment)', 'Auth::resetPassword/$1');
$routes->post('reset-password/(:segment)', 'Auth::processReset/$1');

// Public EEI Survey (no login required)
$routes->get('eei/(:segment)', 'PeopleEei::publicSurvey/$1');
$routes->post('eei/(:segment)/submit', 'PeopleEei::publicSubmit/$1');

// Dashboard
$routes->get('/', 'Dashboard::index', ['filter' => 'auth']);
$routes->post('dashboard/update-bbm',     'Dashboard::updateBbm',     ['filter' => 'auth']);
$routes->post('dashboard/update-macro',   'Dashboard::updateMacro',   ['filter' => 'auth']);
$routes->get ('dashboard/auto-fetch-bbm', 'Dashboard::autoFetchBbm',  ['filter' => 'auth']);
$routes->get ('dashboard/news-feed',      'Dashboard::newsFeed',       ['filter' => 'auth']);
$routes->get ('dashboard/economic',       'Dashboard::economicLive',   ['filter' => 'auth']);
$routes->get ('dashboard/economic-debug', 'Dashboard::economicDebug',  ['filter' => 'auth:admin']);
$routes->get ('dashboard/ihsg',           'Dashboard::ihsgLive',        ['filter' => 'auth']);
$routes->get ('dashboard/weather',        'Dashboard::weatherForecast', ['filter' => 'auth']);

// Events
$routes->get('events', 'Events::index', ['filter' => 'auth']);
$routes->get('events/compare', 'EventCompare::index', ['filter' => 'auth']);
$routes->get('events/create', 'Events::create', ['filter' => 'auth']);
$routes->post('events/create', 'Events::store', ['filter' => 'auth']);
$routes->get('events/(:num)', 'Events::show/$1', ['filter' => 'auth']);
$routes->get('events/(:num)/edit', 'Events::edit/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/edit', 'Events::update/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/delete',  'Events::delete/$1',  ['filter' => 'auth']);
$routes->post('events/(:num)/approve', 'Events::approve/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/reject',  'Events::reject/$1',  ['filter' => 'auth']);

// Event Completion
$routes->post('events/(:num)/complete/(:alpha)', 'EventCompletion::mark/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/uncomplete/(:alpha)', 'EventCompletion::unmark/$1/$2', ['filter' => 'auth']);

// Event Gallery
$routes->get('events/(:num)/gallery', 'EventGallery::index/$1', ['filter' => 'auth']);

// Event Monthly Summary
$routes->get('events/monthly-summary', 'EventSummary::monthly', ['filter' => 'auth']);

// Event Summary
$routes->get('events/(:num)/summary', 'EventSummary::index/$1', ['filter' => 'auth']);
$routes->get('events/(:num)/summary/technical-meeting', 'EventSummary::technicalMeeting/$1', ['filter' => 'auth']);
$routes->get('events/(:num)/summary/post-event', 'EventSummary::postEvent/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/summary/evaluation', 'EventSummary::saveEvaluation/$1', ['filter' => 'auth']);
$routes->get('events/(:num)/budget', 'EventSummary::budget/$1', ['filter' => 'auth']);

// Other Cost
$routes->get('events/(:num)/other-cost', 'EventOtherCost::index/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/other-cost/add', 'EventOtherCost::store/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/other-cost/(:num)/edit', 'EventOtherCost::update/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/other-cost/(:num)/delete', 'EventOtherCost::delete/$1/$2', ['filter' => 'auth']);

// Content Event
$routes->get('events/(:num)/content', 'EventContent::index/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/content/save-content', 'EventContent::saveContent/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/content/add-item', 'EventContent::addItem/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/content/(:num)/edit-item', 'EventContent::editItem/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/content/(:num)/delete-item', 'EventContent::deleteItem/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/content/(:num)/realisasi/add', 'EventContent::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/content/(:num)/realisasi/(:num)/delete', 'EventContent::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);

// Rundown
$routes->get('events/(:num)/rundown', 'EventContent::rundown/$1', ['filter' => 'auth']);
$routes->get('events/(:num)/rundown/print', 'EventContent::printRundown/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/rundown/save', 'EventContent::saveRundown/$1', ['filter' => 'auth']);

// Loyalty (Loyalty dept)
$routes->get('events/(:num)/loyalty', 'EventLoyaltyCtrl::index/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/add', 'EventLoyaltyCtrl::storeProgram/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/edit', 'EventLoyaltyCtrl::updateProgram/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/delete', 'EventLoyaltyCtrl::deleteProgram/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/realisasi/add', 'EventLoyaltyCtrl::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/realisasi/(:num)/delete', 'EventLoyaltyCtrl::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/hadiah/add', 'EventLoyaltyCtrl::storeHadiahItem/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/hadiah/(:num)/update', 'EventLoyaltyCtrl::updateHadiahItem/$1/$2/$3', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/hadiah/(:num)/delete', 'EventLoyaltyCtrl::deleteHadiahItem/$1/$2/$3', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/hadiah/(:num)/realisasi/add', 'EventLoyaltyCtrl::storeHadiahRealisasi/$1/$2/$3', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/hadiah/(:num)/realisasi/(:num)/delete', 'EventLoyaltyCtrl::deleteHadiahRealisasi/$1/$2/$3/$4', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/voucher/add', 'EventLoyaltyCtrl::storeVoucherItem/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/voucher/(:num)/delete', 'EventLoyaltyCtrl::deleteVoucherItem/$1/$2/$3', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/voucher/(:num)/realisasi/add', 'EventLoyaltyCtrl::storeVoucherRealisasi/$1/$2/$3', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/voucher/(:num)/realisasi/(:num)/delete', 'EventLoyaltyCtrl::deleteVoucherRealisasi/$1/$2/$3/$4', ['filter' => 'auth']);

// VM / Dekorasi (Visual Merchandiser)
$routes->get('events/(:num)/vm', 'EventVM::index/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/vm/add', 'EventVM::store/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/vm/(:num)/edit', 'EventVM::update/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/vm/(:num)/delete', 'EventVM::delete/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/vm/(:num)/realisasi/add', 'EventVM::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/vm/(:num)/realisasi/(:num)/delete', 'EventVM::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);

// Creative, Concept & Design
$routes->get('events/(:num)/creative', 'EventCreativeCtrl::index/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/add', 'EventCreativeCtrl::store/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/(:num)/edit', 'EventCreativeCtrl::update/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/(:num)/delete', 'EventCreativeCtrl::delete/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/(:num)/upload', 'EventCreativeCtrl::uploadFile/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/(:num)/file/(:num)/delete', 'EventCreativeCtrl::deleteFile/$1/$2/$3', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/(:num)/status', 'EventCreativeCtrl::updateStatus/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/(:num)/realisasi/add', 'EventCreativeCtrl::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/(:num)/realisasi/(:num)/delete', 'EventCreativeCtrl::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/(:num)/insight/add', 'EventCreativeCtrl::storeInsight/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/creative/(:num)/insight/(:num)/delete', 'EventCreativeCtrl::deleteInsight/$1/$2/$3', ['filter' => 'auth']);

// Standalone Creative & Design
$routes->get('creative/monthly-summary',       'CreativeCtrl::monthly',       ['filter' => 'auth']);
$routes->get('creative/monthly-summary/print', 'CreativeCtrl::printMonthly',  ['filter' => 'auth']);
$routes->get('creative', 'CreativeCtrl::index', ['filter' => 'auth']);
$routes->post('creative/add', 'CreativeCtrl::store', ['filter' => 'auth']);
$routes->post('creative/(:num)/edit', 'CreativeCtrl::update/$1', ['filter' => 'auth']);
$routes->post('creative/(:num)/delete', 'CreativeCtrl::delete/$1', ['filter' => 'auth']);
$routes->post('creative/(:num)/upload', 'CreativeCtrl::uploadFile/$1', ['filter' => 'auth']);
$routes->post('creative/(:num)/file/(:num)/delete', 'CreativeCtrl::deleteFile/$1/$2', ['filter' => 'auth']);
$routes->post('creative/(:num)/status', 'CreativeCtrl::updateStatus/$1', ['filter' => 'auth']);
$routes->post('creative/(:num)/realisasi/add', 'CreativeCtrl::storeRealisasi/$1', ['filter' => 'auth']);
$routes->post('creative/(:num)/realisasi/(:num)/delete', 'CreativeCtrl::deleteRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('creative/(:num)/insight/add', 'CreativeCtrl::storeInsight/$1', ['filter' => 'auth']);
$routes->post('creative/(:num)/insight/(:num)/delete', 'CreativeCtrl::deleteInsight/$1/$2', ['filter' => 'auth']);

// Media Promo
$routes->get('creative/media-promo',                              'PromoMediaCtrl::index',             ['filter' => 'auth']);
$routes->get('creative/media-promo/master',                       'PromoMediaCtrl::master',             ['filter' => 'auth']);
$routes->get('creative/media-promo/pending',                      'PromoMediaCtrl::pending',            ['filter' => 'auth']);
$routes->get('creative/media-promo/gantt',                        'PromoMediaCtrl::gantt',              ['filter' => 'auth']);
$routes->get('creative/media-promo/summary',                      'PromoMediaCtrl::summary',            ['filter' => 'auth']);
$routes->get('creative/media-promo/print',                        'PromoMediaCtrl::printBooking',       ['filter' => 'auth']);
$routes->get('creative/media-promo/print-summary',                'PromoMediaCtrl::printSummary',       ['filter' => 'auth']);
$routes->post('creative/media-promo/spots/store',                 'PromoMediaCtrl::storeSpot',          ['filter' => 'auth']);
$routes->post('creative/media-promo/spots/(:num)/update',         'PromoMediaCtrl::updateSpot/$1',      ['filter' => 'auth']);
$routes->post('creative/media-promo/spots/(:num)/delete',          'PromoMediaCtrl::deleteSpot/$1',      ['filter' => 'auth']);
$routes->get('creative/media-promo/spots/check-cetak',            'PromoMediaCtrl::checkCetakAvailability',  ['filter' => 'auth']);
$routes->get('creative/media-promo/spots/check-digital',          'PromoMediaCtrl::checkDigitalAvailability', ['filter' => 'auth']);
$routes->get('creative/media-promo/spots/(:num)/slots',           'PromoMediaCtrl::getAvailableSlots/$1', ['filter' => 'auth']);
$routes->post('creative/media-promo/usage/batch-approve',         'PromoMediaCtrl::batchApprove',       ['filter' => 'auth']);
$routes->post('creative/media-promo/usage/reject-batch',          'PromoMediaCtrl::rejectBatch',        ['filter' => 'auth']);
$routes->post('creative/media-promo/usage/(:num)/approve',        'PromoMediaCtrl::approve/$1',         ['filter' => 'auth']);
$routes->post('creative/media-promo/usage/(:num)/reject',         'PromoMediaCtrl::reject/$1',          ['filter' => 'auth']);
$routes->get('creative/media-promo/my',                           'PromoMediaUsageCtrl::myUsage',       ['filter' => 'auth']);
$routes->post('creative/media-promo/usage/store',                 'PromoMediaUsageCtrl::store',         ['filter' => 'auth']);
$routes->post('creative/media-promo/usage/(:num)/update',         'PromoMediaUsageCtrl::update/$1',     ['filter' => 'auth']);
$routes->post('creative/media-promo/usage/(:num)/submit',         'PromoMediaUsageCtrl::submit/$1',     ['filter' => 'auth']);
$routes->post('creative/media-promo/usage/submit-selected',       'PromoMediaUsageCtrl::submitSelected', ['filter' => 'auth']);
$routes->post('creative/media-promo/usage/(:num)/cancel',         'PromoMediaUsageCtrl::cancel/$1',     ['filter' => 'auth']);

// Exhibitors (Casual Leasing)
$routes->get('events/(:num)/exhibitors', 'EventExhibitors::index/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/exhibitors/add', 'EventExhibitors::store/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/exhibitors/save-target', 'EventExhibitors::saveTarget/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/exhibitors/(:num)/edit', 'EventExhibitors::update/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/exhibitors/(:num)/delete', 'EventExhibitors::delete/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/exhibitors/(:num)/programs/add', 'EventExhibitors::addProgram/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/exhibitors/(:num)/programs/(:num)/delete', 'EventExhibitors::deleteProgram/$1/$2/$3', ['filter' => 'auth']);

// Sponsors (Sponsorship)
$routes->get('events/(:num)/sponsors', 'EventSponsors::index/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/sponsors/add', 'EventSponsors::store/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/sponsors/(:num)/edit', 'EventSponsors::update/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/sponsors/(:num)/delete', 'EventSponsors::delete/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/sponsors/(:num)/realisasi/add', 'EventSponsors::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/sponsors/(:num)/realisasi/(:num)/delete', 'EventSponsors::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);

// Standalone Loyalty Programs
$routes->get('loyalty', 'LoyaltyCtrl::index', ['filter' => 'auth']);
$routes->get('loyalty/summary',       'LoyaltyCtrl::summary',       ['filter' => 'auth']);
$routes->get('loyalty/summary/print', 'LoyaltyCtrl::printSummary',  ['filter' => 'auth']);
$routes->post('loyalty/summary/analisa', 'LoyaltyCtrl::saveAnalisa', ['filter' => 'auth']);
$routes->get('loyalty/tenants',               'LoyaltyCtrl::indexTenants',      ['filter' => 'auth']);
$routes->post('loyalty/tenants/add',          'LoyaltyCtrl::storeTenant',       ['filter' => 'auth']);
$routes->post('loyalty/tenants/(:num)/edit',  'LoyaltyCtrl::updateTenant/$1',   ['filter' => 'auth']);
$routes->post('loyalty/tenants/(:num)/delete','LoyaltyCtrl::deleteTenant/$1',   ['filter' => 'auth']);
$routes->get('loyalty/tenants/(:num)',        'LoyaltyCtrl::tenantDetail/$1',   ['filter' => 'auth']);
$routes->get('loyalty/detail/(:alpha)/(:num)', 'LoyaltyCtrl::detail/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/add', 'LoyaltyCtrl::storeProgram', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/edit', 'LoyaltyCtrl::updateProgram/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/delete', 'LoyaltyCtrl::deleteProgram/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/toggle', 'LoyaltyCtrl::toggleStatus/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/lock', 'LoyaltyCtrl::lock/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/unlock', 'LoyaltyCtrl::unlock/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/duplikat', 'LoyaltyCtrl::duplikat/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/realisasi/add', 'LoyaltyCtrl::storeRealisasi/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/realisasi/(:num)/delete', 'LoyaltyCtrl::deleteRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/hadiah/add', 'LoyaltyCtrl::storeHadiahItem/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/hadiah/(:num)/update', 'LoyaltyCtrl::updateHadiahItem/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/hadiah/(:num)/delete', 'LoyaltyCtrl::deleteHadiahItem/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/hadiah/(:num)/realisasi/add', 'LoyaltyCtrl::storeHadiahRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/hadiah/(:num)/realisasi/(:num)/delete', 'LoyaltyCtrl::deleteHadiahRealisasi/$1/$2/$3', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/voucher/add', 'LoyaltyCtrl::storeVoucherItem/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/voucher/(:num)/delete', 'LoyaltyCtrl::deleteVoucherItem/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/voucher/(:num)/realisasi/add', 'LoyaltyCtrl::storeVoucherRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/voucher/(:num)/realisasi/(:num)/delete', 'LoyaltyCtrl::deleteVoucherRealisasi/$1/$2/$3', ['filter' => 'auth']);

// Stock Barang (Master Stock Hadiah Fisik)
$routes->get('stock/barang', 'StockBarangCtrl::index', ['filter' => 'auth']);
$routes->get('stock/barang/mutasi', 'StockBarangCtrl::mutasi', ['filter' => 'auth']);
$routes->post('stock/barang/store', 'StockBarangCtrl::store', ['filter' => 'auth']);
$routes->post('stock/barang/(:num)/update', 'StockBarangCtrl::update/$1', ['filter' => 'auth']);
$routes->post('stock/barang/(:num)/tambah-stok', 'StockBarangCtrl::tambahStok/$1', ['filter' => 'auth']);
$routes->post('stock/barang/(:num)/realisasi', 'StockBarangCtrl::storeRealisasi/$1', ['filter' => 'auth']);
$routes->post('stock/barang/(:num)/delete', 'StockBarangCtrl::delete/$1', ['filter' => 'auth']);

// Stock Voucher Fisik
$routes->get('stock/summary', 'StockCtrl::summary', ['filter' => 'auth']);
$routes->get('stock/barang/(:num)/kartu', 'StockCtrl::kartuBarang/$1', ['filter' => 'auth']);
$routes->get('stock/voucher/(:num)/kartu', 'StockCtrl::kartuVoucher/$1', ['filter' => 'auth']);
$routes->get('stock/voucher', 'StockVoucherCtrl::index', ['filter' => 'auth']);
$routes->post('stock/voucher/store', 'StockVoucherCtrl::store', ['filter' => 'auth']);
$routes->post('stock/voucher/(:num)/update', 'StockVoucherCtrl::update/$1', ['filter' => 'auth']);
$routes->post('stock/voucher/(:num)/import-kode', 'StockVoucherCtrl::importKode/$1', ['filter' => 'auth']);
$routes->post('stock/voucher/(:num)/kode/(:num)/delete', 'StockVoucherCtrl::deleteKode/$1/$2', ['filter' => 'auth']);
$routes->post('stock/voucher/(:num)/kode/(:num)/distribute', 'StockVoucherCtrl::distributeKode/$1/$2', ['filter' => 'auth']);
$routes->post('stock/voucher/(:num)/kode/(:num)/deassign', 'StockVoucherCtrl::deassignKode/$1/$2', ['filter' => 'auth']);
$routes->post('stock/voucher/(:num)/delete', 'StockVoucherCtrl::deleteBatch/$1', ['filter' => 'auth']);
$routes->get('stock/voucher/(:num)/available-kodes', 'StockVoucherCtrl::getAvailableKodes/$1', ['filter' => 'auth']);

// Sponsorship Standalone
$routes->get('sponsorship', 'SponsorshipCtrl::index', ['filter' => 'auth']);
$routes->get('sponsorship/(:num)/file/(:segment)', 'SponsorshipCtrl::viewFile/$1/$2', ['filter' => 'auth']);
$routes->get('sponsorship/summary', 'SponsorshipCtrl::summary', ['filter' => 'auth']);
$routes->get('sponsorship/summary/print', 'SponsorshipCtrl::printSummary', ['filter' => 'auth']);
$routes->post('sponsorship/add', 'SponsorshipCtrl::storeProgram', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/edit', 'SponsorshipCtrl::updateProgram/$1', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/delete', 'SponsorshipCtrl::deleteProgram/$1', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/toggle', 'SponsorshipCtrl::toggleStatus/$1', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/lock', 'SponsorshipCtrl::lock/$1', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/unlock', 'SponsorshipCtrl::unlock/$1', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/sponsor/add', 'SponsorshipCtrl::storeSponsor/$1', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/sponsor/(:num)/edit', 'SponsorshipCtrl::updateSponsor/$1/$2', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/sponsor/(:num)/delete', 'SponsorshipCtrl::deleteSponsor/$1/$2', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/sponsor/(:num)/realisasi/add', 'SponsorshipCtrl::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('sponsorship/(:num)/sponsor/(:num)/realisasi/(:num)/delete', 'SponsorshipCtrl::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);
$routes->post('sponsorship/analisa/save', 'SponsorshipCtrl::saveAnalisa', ['filter' => 'auth']);

// VM Standalone (non-event)
$routes->get('vm/monthly-summary', 'VMStandalone::monthly', ['filter' => 'auth']);
$routes->get('vm', 'VMStandalone::index', ['filter' => 'auth']);
$routes->post('vm/add', 'VMStandalone::store', ['filter' => 'auth']);
$routes->post('vm/(:num)/edit', 'VMStandalone::update/$1', ['filter' => 'auth']);
$routes->post('vm/(:num)/delete', 'VMStandalone::delete/$1', ['filter' => 'auth']);
$routes->post('vm/(:num)/realisasi/add', 'VMStandalone::storeRealisasi/$1', ['filter' => 'auth']);
$routes->post('vm/(:num)/realisasi/(:num)/delete', 'VMStandalone::deleteRealisasi/$1/$2', ['filter' => 'auth']);

// Daily Traffic (Operasional — standalone)
$routes->get('traffic', 'Traffic::index', ['filter' => 'auth']);
$routes->get('traffic/export',         'Traffic::export',        ['filter' => 'auth']);
$routes->get('traffic/summary',        'Traffic::summary',       ['filter' => 'auth']);
$routes->get('traffic/export-summary', 'Traffic::exportSummary', ['filter' => 'auth']);
$routes->get('traffic/print-summary',  'Traffic::printSummary',  ['filter' => 'auth']);
$routes->get('traffic/print-compare',  'Traffic::printCompare',  ['filter' => 'auth']);
$routes->get('traffic/import', 'Traffic::importForm', ['filter' => 'auth']);
$routes->post('traffic/import', 'Traffic::importPreview', ['filter' => 'auth']);
$routes->post('traffic/import/save', 'Traffic::importSave', ['filter' => 'auth']);
$routes->post('traffic/import/bulk-save', 'Traffic::importBulkSave', ['filter' => 'auth']);
$routes->get('traffic/compare', 'Traffic::compare', ['filter' => 'auth']);
$routes->get('traffic/input/(:alpha)/(:any)', 'Traffic::form/$1/$2', ['filter' => 'auth']);
$routes->get('traffic/input/(:alpha)', 'Traffic::form/$1', ['filter' => 'auth']);
$routes->post('traffic/save', 'Traffic::save', ['filter' => 'auth']);
$routes->post('traffic/save-cell', 'Traffic::saveCell', ['filter' => 'auth']);
$routes->post('traffic/delete/(:alpha)/(:any)', 'Traffic::delete/$1/$2', ['filter' => 'auth']);
// Input Kendaraan manual dihapus (v2.15.x) — daily_vehicles kini dicerminkan otomatis dari SPI (mic:spi-sync).

// Parkir — dashboard read-only dari SPI. Satu halaman Live gabungan (field digate),
// Summary terpisah per domain, Compare periode.
$routes->get('parking',                  'ParkingLive::index',       ['filter' => 'auth']);
$routes->get('parking/live',             'ParkingLive::index',       ['filter' => 'auth']);
$routes->get('parking/live-data',        'ParkingLive::data',        ['filter' => 'auth']);
$routes->get('parking/vehicles',         'ParkingVehicles::index',   ['filter' => 'auth']);
$routes->get('parking/vehicles/summary', 'ParkingVehicles::summary', ['filter' => 'auth']);
$routes->get('parking/revenue',          'ParkingRevenue::index',    ['filter' => 'auth']);
$routes->get('parking/revenue/summary',  'ParkingRevenue::summary',  ['filter' => 'auth']);
$routes->get('parking/compare',          'ParkingCompare::index',    ['filter' => 'auth']);
$routes->get('parking/occupancy',        'ParkingOccupancy::index',  ['filter' => 'auth']); // okupansi intraday + rekonsiliasi
$routes->get('parking/recon',            'ParkingRecon::index',      ['filter' => 'auth']); // analisa: rekaman vs SPI final
$routes->post('parking/sync',            'ParkingSync::run',         ['filter' => 'auth']); // tarik data SPI manual

// Event Locations master (admin only)
$routes->get('event-locations', 'EventLocations::index', ['filter' => 'auth:admin']);
$routes->post('event-locations/add', 'EventLocations::store', ['filter' => 'auth:admin']);
$routes->post('event-locations/(:num)/edit', 'EventLocations::update/$1', ['filter' => 'auth:admin']);
$routes->post('event-locations/(:num)/delete', 'EventLocations::delete/$1', ['filter' => 'auth:admin']);

// Traffic Doors master (admin only)
$routes->get('traffic-doors', 'TrafficDoors::index', ['filter' => 'auth:admin']);
$routes->post('traffic-doors/add', 'TrafficDoors::store', ['filter' => 'auth:admin']);
$routes->post('traffic-doors/(:num)/edit', 'TrafficDoors::update/$1', ['filter' => 'auth:admin']);
$routes->post('traffic-doors/(:num)/delete', 'TrafficDoors::delete/$1', ['filter' => 'auth:admin']);
$routes->post('traffic-doors/reorder', 'TrafficDoors::reorder', ['filter' => 'auth:admin']);

// Activity Logs
$routes->get('logs', 'Logs::index', ['filter' => 'auth']);

// Roles (admin only)
$routes->get('roles', 'Roles::index', ['filter' => 'auth:admin']);
$routes->post('roles/add', 'Roles::store', ['filter' => 'auth:admin']);
$routes->post('roles/(:num)/edit', 'Roles::update/$1', ['filter' => 'auth:admin']);
$routes->post('roles/(:num)/delete', 'Roles::delete/$1', ['filter' => 'auth:admin']);

// Departments (admin only)
$routes->get('departments', 'Departments::index', ['filter' => 'auth:admin']);
$routes->post('departments/add', 'Departments::store', ['filter' => 'auth:admin']);
$routes->get('departments/(:num)/edit', 'Departments::edit/$1', ['filter' => 'auth:admin']);
$routes->post('departments/(:num)/edit', 'Departments::update/$1', ['filter' => 'auth:admin']);
$routes->post('departments/(:num)/delete', 'Departments::delete/$1', ['filter' => 'auth:admin']);

// Divisions (admin only)
$routes->get('admin/clusters',                  'AdminClusters::index',       ['filter' => 'auth:admin']);
$routes->post('admin/clusters/store',           'AdminClusters::store',       ['filter' => 'auth:admin']);
$routes->post('admin/clusters/(:num)/update',   'AdminClusters::update/$1',   ['filter' => 'auth:admin']);
$routes->post('admin/clusters/(:num)/delete',    'AdminClusters::delete/$1',   ['filter' => 'auth:admin']);

$routes->get('admin/settings',                  'AdminSettings::index',       ['filter' => 'auth:admin']);
$routes->post('admin/settings/save',            'AdminSettings::save',        ['filter' => 'auth:admin']);
$routes->get('admin/settings/test-email',       'AdminSettings::testEmail',   ['filter' => 'auth:admin']);
$routes->get('divisions', 'Divisions::index', ['filter' => 'auth:admin']);
$routes->post('divisions/store', 'Divisions::store', ['filter' => 'auth:admin']);
$routes->post('divisions/(:num)/update', 'Divisions::update/$1', ['filter' => 'auth:admin']);
$routes->post('divisions/(:num)/delete', 'Divisions::delete/$1', ['filter' => 'auth:admin']);
$routes->post('divisions/assign-dept', 'Divisions::assignDept', ['filter' => 'auth:admin']);

// Theme Periods (admin CRUD + shared JSON endpoint)
$routes->get ('theme-periods',              'ThemePeriods::index',   ['filter' => 'auth:admin']);
$routes->post('theme-periods/add',          'ThemePeriods::add',     ['filter' => 'auth:admin']);
$routes->post('theme-periods/(:num)/edit',  'ThemePeriods::edit/$1', ['filter' => 'auth:admin']);
$routes->get ('theme-periods/(:num)/delete','ThemePeriods::delete/$1',['filter' => 'auth:admin']);
$routes->get ('theme-periods/(:num)/toggle','ThemePeriods::toggle/$1',['filter' => 'auth:admin']);
$routes->get ('theme-periods/today',        'ThemePeriods::today',   ['filter' => 'auth']);

// Gantt Chart
$routes->get('gantt',      'GanttController::index', ['filter' => 'auth']);
$routes->get('gantt/data', 'GanttController::data',  ['filter' => 'auth']);

// Admin: Public Holidays
$routes->get ('admin/holidays',            'AdminHolidays::index',     ['filter' => 'auth:admin']);
$routes->post('admin/holidays/store',      'AdminHolidays::store',     ['filter' => 'auth:admin']);
$routes->post('admin/holidays/delete/(:num)', 'AdminHolidays::delete/$1', ['filter' => 'auth:admin']);
$routes->post('admin/holidays/bulk',       'AdminHolidays::bulkStore', ['filter' => 'auth:admin']);
$routes->post('admin/holidays/sync',       'AdminHolidays::syncApi',   ['filter' => 'auth:admin']);

// Jabatans (admin only)
$routes->get('jabatans', 'Jabatans::index', ['filter' => 'auth:admin']);
$routes->post('jabatans/store', 'Jabatans::store', ['filter' => 'auth:admin']);
$routes->post('jabatans/(:num)/update', 'Jabatans::update/$1', ['filter' => 'auth:admin']);
$routes->post('jabatans/(:num)/delete', 'Jabatans::delete/$1', ['filter' => 'auth:admin']);

// Users (admin only)
$routes->get('users', 'Users::index', ['filter' => 'auth:admin']);
$routes->post('users/add', 'Users::store', ['filter' => 'auth:admin']);
$routes->post('users/(:num)/edit', 'Users::update/$1', ['filter' => 'auth:admin']);
$routes->get('users/(:num)/menu-access', 'Users::menuAccess/$1', ['filter' => 'auth:admin']);
$routes->post('users/(:num)/menu-access', 'Users::saveMenuAccess/$1', ['filter' => 'auth:admin']);
$routes->post('users/(:num)/toggle', 'Users::toggle/$1', ['filter' => 'auth:admin']);
$routes->post('users/(:num)/unlock', 'Users::unlock/$1', ['filter' => 'auth:admin']);
$routes->post('users/(:num)/delete', 'Users::delete/$1', ['filter' => 'auth:admin']);

// People Development — Dashboard
$routes->get('people/dashboard', 'PeopleDashboard::index', ['filter' => 'auth']);

// People Development — Training Programs
$routes->get('people/training',                                              'PeopleTraining::index',             ['filter' => 'auth']);
$routes->get('people/training/budget',                                       'PeopleTraining::budget',            ['filter' => 'auth']);
$routes->post('people/training/budget/save',                                 'PeopleTraining::saveBudget',        ['filter' => 'auth']);
$routes->get('people/training/budget-detail/(:num)',                         'PeopleTraining::budgetDetail/$1',   ['filter' => 'auth']);
$routes->post('people/training/add',                                         'PeopleTraining::store',             ['filter' => 'auth']);
$routes->get('people/training/(:num)',                                        'PeopleTraining::show/$1',           ['filter' => 'auth']);
$routes->post('people/training/(:num)/edit',                                 'PeopleTraining::update/$1',         ['filter' => 'auth']);
$routes->post('people/training/(:num)/delete',                                'PeopleTraining::delete/$1',         ['filter' => 'auth']);
$routes->post('people/training/(:num)/participants/add',                     'PeopleTraining::addParticipant/$1', ['filter' => 'auth']);
$routes->post('people/training/(:num)/participants/(:num)/remove',            'PeopleTraining::removeParticipant/$1/$2', ['filter' => 'auth']);
$routes->post('people/training/(:num)/participants/(:num)/update',           'PeopleTraining::updateParticipant/$1/$2', ['filter' => 'auth']);

// People Development — TNA Assessment 360°
$routes->get('people/tna',                                               'PeopleTna::index',           ['filter' => 'auth']);
$routes->post('people/tna/periods/add',                                  'PeopleTna::storePeriod',     ['filter' => 'auth']);
$routes->post('people/tna/periods/(:num)/edit',                          'PeopleTna::updatePeriod/$1', ['filter' => 'auth']);
$routes->post('people/tna/periods/(:num)/delete',                         'PeopleTna::deletePeriod/$1', ['filter' => 'auth']);
$routes->post('people/tna/periods/(:num)/toggle-close',                   'PeopleTna::toggleClose/$1',  ['filter' => 'auth']);
$routes->get('people/tna/period/(:num)',                                  'PeopleTna::period/$1',       ['filter' => 'auth']);
$routes->post('people/tna/period/(:num)/employees/add',                  'PeopleTna::addEmployee/$1',  ['filter' => 'auth']);
$routes->post('people/tna/period/(:num)/employees/(:num)/remove',         'PeopleTna::removeEmployee/$1/$2',  ['filter' => 'auth']);
$routes->post('people/tna/period/(:num)/employees/(:num)/assessors/add', 'PeopleTna::addAssessor/$1/$2',     ['filter' => 'auth']);
$routes->post('people/tna/period/(:num)/assessors/(:num)/remove',         'PeopleTna::removeAssessor/$1/$2',  ['filter' => 'auth']);
$routes->get('people/tna/assess/(:num)',                                  'PeopleTna::assess/$1',             ['filter' => 'auth']);
$routes->post('people/tna/assess/(:num)/submit',                         'PeopleTna::submitAssessment/$1',   ['filter' => 'auth']);
$routes->get('people/tna/period/(:num)/result/(:num)',                   'PeopleTna::result/$1/$2',                ['filter' => 'auth']);
$routes->get('people/tna/assessments/(:num)/regenerate-token',           'PeopleTna::regenerateToken/$1',          ['filter' => 'auth']);
$routes->get('people/tna/period/(:num)/employees/(:num)/send-email',     'PeopleTna::sendEmail/$1/$2',             ['filter' => 'auth']);
$routes->get('people/tna/period/(:num)/send-email-all',                  'PeopleTna::sendEmailAll/$1',             ['filter' => 'auth']);

// TNA Fill — public token-based (no auth required)
$routes->get('tna/fill/(:segment)',        'TnaFill::show/$1');
$routes->post('tna/fill/(:segment)/submit', 'TnaFill::submit/$1');

// People Development — EEI
$routes->get('people/eei',                                          'PeopleEei::index',                    ['filter' => 'auth']);
$routes->get('people/eei/manage',                                   'PeopleEei::manage',                   ['filter' => 'auth']);
$routes->get('people/eei/survey',                                   'PeopleEei::survey',                   ['filter' => 'auth']);
$routes->post('people/eei/submit',                                  'PeopleEei::submit',                   ['filter' => 'auth']);
$routes->post('people/eei/dimension/add',                           'PeopleEei::storeDimension',           ['filter' => 'auth:admin']);
$routes->post('people/eei/dimension/(:num)/edit',                   'PeopleEei::updateDimension/$1',       ['filter' => 'auth:admin']);
$routes->post('people/eei/dimension/(:num)/delete',                  'PeopleEei::deleteDimension/$1',       ['filter' => 'auth:admin']);
$routes->post('people/eei/dimension/(:num)/questions/add',          'PeopleEei::storeQuestion/$1',         ['filter' => 'auth:admin']);
$routes->post('people/eei/question/(:num)/delete',                   'PeopleEei::deleteQuestion/$1',        ['filter' => 'auth:admin']);
$routes->post('people/eei/period/add',                              'PeopleEei::storePeriod',              ['filter' => 'auth:admin']);
$routes->post('people/eei/period/(:num)/edit',                      'PeopleEei::updatePeriod/$1',          ['filter' => 'auth:admin']);
$routes->post('people/eei/period/(:num)/delete',                     'PeopleEei::deletePeriod/$1',          ['filter' => 'auth:admin']);
$routes->get('people/eei/period/(:num)/activate',                   'PeopleEei::activatePeriod/$1',        ['filter' => 'auth:admin']);

// People Development — Org Chart
$routes->get('people/orgchart', 'PeopleOrgChart::index', ['filter' => 'auth']);

// ── Appraisal (HR) ──────────────────────────────────────────────────────
$routes->get('appraisal',                              'Appraisal::index',                ['filter' => 'auth']);
$routes->get('appraisal/authors',                      'Appraisal::authors',              ['filter' => 'auth']);
$routes->post('appraisal/authors/save',                'Appraisal::saveAuthors',          ['filter' => 'auth']);
// Template KPI per jabatan (dept head/deputy susun → HR approve)
$routes->get('appraisal/templates',                    'AppraisalTemplate::index',        ['filter' => 'auth']);
$routes->post('appraisal/templates/create',            'AppraisalTemplate::create',       ['filter' => 'auth']);
$routes->post('appraisal/templates/copy',              'AppraisalTemplate::copy',         ['filter' => 'auth']);
$routes->get('appraisal/templates/(:num)',             'AppraisalTemplate::edit/$1',      ['filter' => 'auth']);
$routes->post('appraisal/templates/(:num)/kpi/save',   'AppraisalTemplate::saveKpi/$1',    ['filter' => 'auth']);
$routes->post('appraisal/templates/(:num)/competency/save', 'AppraisalTemplate::saveCompetency/$1', ['filter' => 'auth']);
$routes->post('appraisal/templates/(:num)/submit',     'AppraisalTemplate::submit/$1',     ['filter' => 'auth']);
$routes->post('appraisal/templates/(:num)/approve',    'AppraisalTemplate::approve/$1',     ['filter' => 'auth']);
$routes->post('appraisal/templates/(:num)/reject',     'AppraisalTemplate::reject/$1',      ['filter' => 'auth']);
$routes->post('appraisal/templates/(:num)/delete',     'AppraisalTemplate::delete/$1',      ['filter' => 'auth']);
// Periode + form (Phase 2/3/4)
$routes->post('appraisal/periods/create',              'AppraisalPeriod::create',          ['filter' => 'auth']);
$routes->get('appraisal/periods/(:num)',               'AppraisalPeriod::show/$1',          ['filter' => 'auth']);
$routes->post('appraisal/periods/(:num)/close',        'AppraisalPeriod::close/$1',         ['filter' => 'auth']);
$routes->post('appraisal/periods/(:num)/add-employee', 'AppraisalPeriod::addEmployee/$1',    ['filter' => 'auth']);
$routes->get('appraisal/forms/(:num)',                 'AppraisalForm::show/$1',            ['filter' => 'auth']);
$routes->post('appraisal/forms/(:num)/score',          'AppraisalForm::saveScore/$1',       ['filter' => 'auth']);
$routes->post('appraisal/forms/(:num)/forward',        'AppraisalForm::forward/$1',         ['filter' => 'auth']);
$routes->post('appraisal/forms/(:num)/finalize',       'AppraisalForm::finalize/$1',        ['filter' => 'auth']);
$routes->post('appraisal/forms/(:num)/pendapat',       'AppraisalForm::savePendapat/$1',    ['filter' => 'auth']);
$routes->get('appraisal/forms/(:num)/print',           'AppraisalForm::printForm/$1',       ['filter' => 'auth']);
$routes->post('appraisal/forms/(:num)/release',        'AppraisalForm::release/$1',         ['filter' => 'auth']);
$routes->get('appraisal/saya',                         'AppraisalForm::saya',               ['filter' => 'auth']);

// HR: approval pengajuan perubahan data
$routes->get('people/change-requests',                 'PeopleEmployees::changeRequests',   ['filter' => 'auth']);
$routes->post('people/change-requests/(:num)/approve', 'PeopleEmployees::approveChange/$1', ['filter' => 'auth']);
$routes->post('people/change-requests/(:num)/reject',  'PeopleEmployees::rejectChange/$1',  ['filter' => 'auth']);
$routes->get('people/photo/(:segment)',                     'PeopleEmployees::viewPhoto/$1',      ['filter' => 'auth']);
$routes->get('people/certificates/(:num)/view',             'PeopleEmployees::viewCertificate/$1', ['filter' => 'auth']);
$routes->get('people/trainings/(:num)/view',                'PeopleEmployees::viewTraining/$1',   ['filter' => 'auth']);
$routes->get('people/documents/(:num)/view',                'PeopleEmployees::viewDocument/$1',   ['filter' => 'auth']);
$routes->post('people/employees/(:num)/documents/upload',   'PeopleEmployees::uploadDocument/$1', ['filter' => 'auth']);
$routes->post('people/documents/(:num)/approve',            'PeopleEmployees::approveDocument/$1', ['filter' => 'auth']);
$routes->post('people/documents/(:num)/reject',             'PeopleEmployees::rejectDocument/$1', ['filter' => 'auth']);
$routes->post('people/documents/(:num)/delete',             'PeopleEmployees::deleteDocument/$1', ['filter' => 'auth']);

// People Development — Performance Improvement Plan
$routes->get('people/pip',                                    'PeoplePip::index',           ['filter' => 'auth']);
$routes->post('people/pip/store',                             'PeoplePip::store',            ['filter' => 'auth']);
$routes->get('people/pip/(:num)',                             'PeoplePip::show/$1',          ['filter' => 'auth']);
$routes->post('people/pip/(:num)/update',                     'PeoplePip::update/$1',        ['filter' => 'auth']);
$routes->post('people/pip/(:num)/delete',                      'PeoplePip::delete/$1',        ['filter' => 'auth']);
$routes->post('people/pip/(:num)/reviews/add',                'PeoplePip::storeReview/$1',   ['filter' => 'auth']);
$routes->post('people/pip/(:num)/reviews/(:num)/delete',       'PeoplePip::deleteReview/$1/$2', ['filter' => 'auth']);
$routes->get('people/pip/(:num)/print',                       'PeoplePip::printPip/$1',      ['filter' => 'auth']);
$routes->get('people/pip/(:num)/token/(:alpha)',               'PeoplePip::generateToken/$1/$2', ['filter' => 'auth']);
$routes->get('people/pip/(:num)/approve',                      'PeoplePip::approve/$1',          ['filter' => 'auth']);
$routes->get('people/pip/aspek',                              'PipAspekMasterCtrl::index',       ['filter' => 'auth']);
$routes->post('people/pip/aspek/store',                       'PipAspekMasterCtrl::store',       ['filter' => 'auth']);
$routes->post('people/pip/aspek/(:num)/update',               'PipAspekMasterCtrl::update/$1',   ['filter' => 'auth']);
$routes->post('people/pip/aspek/(:num)/delete',                'PipAspekMasterCtrl::delete/$1',   ['filter' => 'auth']);
$routes->post('people/pip/aspek/(:num)/toggle',                'PipAspekMasterCtrl::toggle/$1',   ['filter' => 'auth']);

// PIP Approval — public token-based (no auth)
$routes->get('pip/approval/(:alpha)/(:segment)',               'PipApproval::show/$1/$2');
$routes->post('pip/approval/(:alpha)/(:segment)/submit',       'PipApproval::submit/$1/$2');

// IDP — Individual Development Plan
$routes->get('people/idp',                                     'PeopleIdp::index',                ['filter' => 'auth']);
$routes->post('people/idp/store',                              'PeopleIdp::store',                ['filter' => 'auth']);
$routes->get('people/idp/import-tna/(:num)/(:num)',            'PeopleIdp::importFromTna/$1/$2',  ['filter' => 'auth']);
$routes->get('people/idp/(:num)',                              'PeopleIdp::show/$1',              ['filter' => 'auth']);
$routes->post('people/idp/(:num)/update',                      'PeopleIdp::update/$1',            ['filter' => 'auth']);
$routes->post('people/idp/(:num)/delete',                       'PeopleIdp::delete/$1',            ['filter' => 'auth']);
$routes->get('people/idp/(:num)/print',                        'PeopleIdp::printIdp/$1',          ['filter' => 'auth']);
$routes->get('people/idp/(:num)/token',                        'PeopleIdp::generateToken/$1',     ['filter' => 'auth']);
$routes->post('people/idp/(:num)/items/store',                 'PeopleIdp::storeItem/$1',         ['filter' => 'auth']);
$routes->post('people/idp/(:num)/items/(:num)/update',         'PeopleIdp::updateItem/$1/$2',     ['filter' => 'auth']);
$routes->post('people/idp/(:num)/items/(:num)/delete',          'PeopleIdp::deleteItem/$1/$2',     ['filter' => 'auth']);

// IDP Approval — public token-based (no auth)
$routes->get('idp/approval/(:segment)',                        'IdpApproval::show/$1');
$routes->post('idp/approval/(:segment)/submit',                'IdpApproval::submit/$1');

// People Development — Competencies
$routes->get('people/competencies',                               'PeopleCompetencies::index',           ['filter' => 'auth']);
$routes->post('people/competencies/add',                          'PeopleCompetencies::store',           ['filter' => 'auth']);
$routes->post('people/competencies/(:num)/edit',                  'PeopleCompetencies::update/$1',       ['filter' => 'auth']);
$routes->post('people/competencies/(:num)/delete',                 'PeopleCompetencies::delete/$1',       ['filter' => 'auth']);
$routes->post('people/competencies/targets/save',                 'PeopleCompetencies::saveTargets',     ['filter' => 'auth']);
$routes->get('people/competencies/(:num)/questions',              'PeopleCompetencies::questions/$1',    ['filter' => 'auth']);
$routes->post('people/competencies/(:num)/questions/add',         'PeopleCompetencies::storeQuestion/$1',   ['filter' => 'auth']);
$routes->post('people/competencies/questions/(:num)/delete',       'PeopleCompetencies::deleteQuestion/$1',  ['filter' => 'auth']);
$routes->post('people/competencies/questions/(:num)/levels',      'PeopleCompetencies::updateQuestionLevels/$1', ['filter' => 'auth']);
$routes->get('people/competencies/dept/(:num)/assign',            'PeopleCompetencies::manageAssignments/$1',        ['filter' => 'auth']);
$routes->post('people/competencies/dept/(:num)/assign',           'PeopleCompetencies::saveAssignments/$1',          ['filter' => 'auth']);
$routes->get('people/competencies/jabatan/(:num)/assign',         'PeopleCompetencies::manageJabatanAssignments/$1', ['filter' => 'auth']);
$routes->post('people/competencies/jabatan/(:num)/assign',        'PeopleCompetencies::saveJabatanAssignments/$1',   ['filter' => 'auth']);
$routes->get('people/competencies/import',                        'PeopleCompetencies::importForm',      ['filter' => 'auth']);
$routes->get('people/competencies/import/template',               'PeopleCompetencies::importTemplate',  ['filter' => 'auth']);
$routes->post('people/competencies/import/parse',                 'PeopleCompetencies::importParse',     ['filter' => 'auth']);
$routes->get('people/competencies/import/preview',                'PeopleCompetencies::importPreview',   ['filter' => 'auth']);
$routes->post('people/competencies/import/confirm',               'PeopleCompetencies::importConfirm',   ['filter' => 'auth']);

// People Development — Talent Portfolio (9-Box)
$routes->get('people/talent',                          'TalentPortfolio::index',          ['filter' => 'auth']);
$routes->get('people/talent/input',                    'TalentPortfolio::input',          ['filter' => 'auth']);
$routes->post('people/talent/(:num)/save',             'TalentPortfolio::save/$1',         ['filter' => 'auth']);
$routes->get('people/talent/periods',                  'TalentPortfolio::periods',        ['filter' => 'auth']);
$routes->post('people/talent/periods/create',          'TalentPortfolio::createPeriod',   ['filter' => 'auth']);
$routes->post('people/talent/periods/(:num)/activate', 'TalentPortfolio::activatePeriod/$1', ['filter' => 'auth']);
$routes->post('people/talent/periods/(:num)/lock',     'TalentPortfolio::lockPeriod/$1',   ['filter' => 'auth']);
$routes->get('people/talent/viewers',                  'TalentPortfolio::viewers',        ['filter' => 'auth']);
$routes->post('people/talent/viewers/add',             'TalentPortfolio::addViewer',      ['filter' => 'auth']);
$routes->post('people/talent/viewers/(:num)/remove',   'TalentPortfolio::removeViewer/$1', ['filter' => 'auth']);

// People Development — Employees
$routes->get('people/hr-dashboard',                                       'HrDashboard::index',                 ['filter' => 'auth']);
$routes->get('people/employees',                                          'PeopleEmployees::index',             ['filter' => 'auth']);
$routes->get('people/employees/export',                                   'PeopleEmployees::export',            ['filter' => 'auth']);
$routes->post('people/employees/add',                                     'PeopleEmployees::store',             ['filter' => 'auth']);
$routes->get('people/employees/(:num)',                                   'PeopleEmployees::show/$1',           ['filter' => 'auth']);
$routes->post('people/employees/(:num)/edit',                             'PeopleEmployees::update/$1',         ['filter' => 'auth']);
$routes->post('people/employees/(:num)/create-account',                   'PeopleEmployees::createAccount/$1',  ['filter' => 'auth']);
$routes->post('people/employees/(:num)/link-account',                     'PeopleEmployees::linkAccount/$1',    ['filter' => 'auth']);
$routes->post('people/employees/(:num)/delete',                            'PeopleEmployees::delete/$1',         ['filter' => 'auth']);
$routes->post('people/employees/(:num)/positions/add',                    'PeopleEmployees::storePosition/$1',  ['filter' => 'auth']);
$routes->post('people/employees/(:num)/positions/(:num)/delete',           'PeopleEmployees::deletePosition/$1/$2', ['filter' => 'auth']);
$routes->post('people/employees/(:num)/certificates/add',                 'PeopleEmployees::storeCertificate/$1',  ['filter' => 'auth']);
$routes->post('people/employees/(:num)/certificates/(:num)/delete',        'PeopleEmployees::deleteCertificate/$1/$2', ['filter' => 'auth']);
$routes->post('people/employees/(:num)/trainings/add',                    'PeopleEmployees::storeTraining/$1',     ['filter' => 'auth']);
$routes->post('people/employees/(:num)/trainings/(:num)/delete',           'PeopleEmployees::deleteTraining/$1/$2',  ['filter' => 'auth']);

// Profile
$routes->get('profile', 'Users::profile', ['filter' => 'auth']);
$routes->post('profile', 'Users::updateProfile', ['filter' => 'auth']);
$routes->post('profile/theme', 'Users::updateTheme', ['filter' => 'auth']);
$routes->post('profile/request-change', 'Users::submitChange', ['filter' => 'auth']);
$routes->post('profile/upload-document', 'Users::uploadDocument', ['filter' => 'auth']);

// ── Legal ────────────────────────────────────────────────────────────────
$routes->get ('legal',                                    'Legal\LegalController::index',                   ['filter' => 'auth']);

// Perizinan
$routes->get ('legal/permits',                            'Legal\LegalPermitController::index',             ['filter' => 'auth']);
$routes->get ('legal/permits/new',                        'Legal\LegalPermitController::new',               ['filter' => 'auth']);
$routes->post('legal/permits',                            'Legal\LegalPermitController::create',            ['filter' => 'auth']);
$routes->get ('legal/permits/(:num)',                     'Legal\LegalPermitController::show/$1',           ['filter' => 'auth']);
$routes->get ('legal/permits/(:num)/edit',                'Legal\LegalPermitController::edit/$1',           ['filter' => 'auth']);
$routes->post('legal/permits/(:num)/edit',                'Legal\LegalPermitController::update/$1',         ['filter' => 'auth']);
$routes->post('legal/permits/(:num)/delete',              'Legal\LegalPermitController::delete/$1',         ['filter' => 'auth']);

// Review SPK
$routes->get ('legal/spk',                                'Legal\LegalSpkController::index',                ['filter' => 'auth']);
$routes->get ('legal/spk/new',                            'Legal\LegalSpkController::new',                  ['filter' => 'auth']);
$routes->post('legal/spk',                                'Legal\LegalSpkController::create',               ['filter' => 'auth']);
$routes->get ('legal/spk/(:num)',                         'Legal\LegalSpkController::show/$1',              ['filter' => 'auth']);
$routes->get ('legal/spk/(:num)/edit',                    'Legal\LegalSpkController::edit/$1',              ['filter' => 'auth']);
$routes->post('legal/spk/(:num)/edit',                    'Legal\LegalSpkController::update/$1',            ['filter' => 'auth']);
$routes->post('legal/spk/(:num)/delete',                  'Legal\LegalSpkController::delete/$1',            ['filter' => 'auth']);

// Perjanjian Kerja Sama
$routes->get ('legal/pks',                                'Legal\LegalPksController::index',                ['filter' => 'auth']);
$routes->get ('legal/pks/new',                            'Legal\LegalPksController::new',                  ['filter' => 'auth']);
$routes->post('legal/pks',                                'Legal\LegalPksController::create',               ['filter' => 'auth']);
$routes->get ('legal/pks/(:num)',                         'Legal\LegalPksController::show/$1',              ['filter' => 'auth']);
$routes->get ('legal/pks/(:num)/edit',                    'Legal\LegalPksController::edit/$1',              ['filter' => 'auth']);
$routes->post('legal/pks/(:num)/edit',                    'Legal\LegalPksController::update/$1',            ['filter' => 'auth']);
$routes->post('legal/pks/(:num)/delete',                  'Legal\LegalPksController::delete/$1',            ['filter' => 'auth']);

// PSM Mall
$routes->get ('legal/psm-mall',                           'Legal\LegalPsmMallController::index',            ['filter' => 'auth']);
$routes->get ('legal/psm-mall/new',                       'Legal\LegalPsmMallController::new',              ['filter' => 'auth']);
$routes->post('legal/psm-mall',                           'Legal\LegalPsmMallController::create',           ['filter' => 'auth']);
$routes->get ('legal/psm-mall/(:num)',                    'Legal\LegalPsmMallController::show/$1',          ['filter' => 'auth']);
$routes->get ('legal/psm-mall/(:num)/edit',               'Legal\LegalPsmMallController::edit/$1',          ['filter' => 'auth']);
$routes->post('legal/psm-mall/(:num)/edit',               'Legal\LegalPsmMallController::update/$1',        ['filter' => 'auth']);
$routes->post('legal/psm-mall/(:num)/delete',             'Legal\LegalPsmMallController::delete/$1',        ['filter' => 'auth']);

// PSM Developer
$routes->get ('legal/psm-developer',                      'Legal\LegalPsmDeveloperController::index',       ['filter' => 'auth']);
$routes->get ('legal/psm-developer/new',                  'Legal\LegalPsmDeveloperController::new',         ['filter' => 'auth']);
$routes->post('legal/psm-developer',                      'Legal\LegalPsmDeveloperController::create',      ['filter' => 'auth']);
$routes->get ('legal/psm-developer/(:num)',               'Legal\LegalPsmDeveloperController::show/$1',     ['filter' => 'auth']);
$routes->get ('legal/psm-developer/(:num)/edit',          'Legal\LegalPsmDeveloperController::edit/$1',     ['filter' => 'auth']);
$routes->post('legal/psm-developer/(:num)/edit',          'Legal\LegalPsmDeveloperController::update/$1',   ['filter' => 'auth']);
$routes->post('legal/psm-developer/(:num)/delete',        'Legal\LegalPsmDeveloperController::delete/$1',   ['filter' => 'auth']);

// PSM Gudang
$routes->get ('legal/psm-gudang',                         'Legal\LegalPsmGudangController::index',          ['filter' => 'auth']);
$routes->get ('legal/psm-gudang/new',                     'Legal\LegalPsmGudangController::new',            ['filter' => 'auth']);
$routes->post('legal/psm-gudang',                         'Legal\LegalPsmGudangController::create',         ['filter' => 'auth']);
$routes->get ('legal/psm-gudang/(:num)',                  'Legal\LegalPsmGudangController::show/$1',        ['filter' => 'auth']);
$routes->get ('legal/psm-gudang/(:num)/edit',             'Legal\LegalPsmGudangController::edit/$1',        ['filter' => 'auth']);
$routes->post('legal/psm-gudang/(:num)/edit',             'Legal\LegalPsmGudangController::update/$1',      ['filter' => 'auth']);
$routes->post('legal/psm-gudang/(:num)/delete',           'Legal\LegalPsmGudangController::delete/$1',      ['filter' => 'auth']);

// Kontrak Sewa Pameran
$routes->get ('legal/kontrak-pameran',                    'Legal\LegalKontrakPameranController::index',     ['filter' => 'auth']);
$routes->get ('legal/kontrak-pameran/new',                'Legal\LegalKontrakPameranController::new',       ['filter' => 'auth']);
$routes->post('legal/kontrak-pameran',                    'Legal\LegalKontrakPameranController::create',    ['filter' => 'auth']);
$routes->get ('legal/kontrak-pameran/(:num)',             'Legal\LegalKontrakPameranController::show/$1',   ['filter' => 'auth']);
$routes->get ('legal/kontrak-pameran/(:num)/edit',        'Legal\LegalKontrakPameranController::edit/$1',   ['filter' => 'auth']);
$routes->post('legal/kontrak-pameran/(:num)/edit',        'Legal\LegalKontrakPameranController::update/$1', ['filter' => 'auth']);
$routes->post('legal/kontrak-pameran/(:num)/delete',      'Legal\LegalKontrakPameranController::delete/$1', ['filter' => 'auth']);

// Dokumen (shared upload/delete)
$routes->post('legal/documents/upload',                   'Legal\LegalController::uploadDocument',          ['filter' => 'auth']);
$routes->post('legal/documents/(:num)/delete',            'Legal\LegalController::deleteDocument/$1',       ['filter' => 'auth']);
$routes->get ('legal/documents/(:num)/download',          'Legal\LegalController::downloadDocument/$1',     ['filter' => 'auth']);
$routes->get ('legal/review-file/(:segment)',             'Legal\LegalReviewController::viewFile/$1',       ['filter' => 'auth']);

// Review Kontrak
$routes->get ('legal/reviews',                            'Legal\LegalReviewController::index',             ['filter' => 'auth']);
$routes->get ('legal/reviews/new',                        'Legal\LegalReviewController::new',               ['filter' => 'auth']);
$routes->post('legal/reviews',                            'Legal\LegalReviewController::create',            ['filter' => 'auth']);
$routes->get ('legal/reviews/(:num)',                     'Legal\LegalReviewController::show/$1',           ['filter' => 'auth']);
$routes->get ('legal/reviews/(:num)/edit',                'Legal\LegalReviewController::edit/$1',           ['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/edit',                'Legal\LegalReviewController::update/$1',         ['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/delete',              'Legal\LegalReviewController::delete/$1',         ['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/version',             'Legal\LegalReviewController::uploadVersion/$1',  ['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/comment',             'Legal\LegalReviewController::addComment/$1',     ['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/request-revision',    'Legal\LegalReviewController::requestRevision/$1',['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/mark-final',          'Legal\LegalReviewController::markFinal/$1',      ['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/mark-signed',         'Legal\LegalReviewController::markSigned/$1',     ['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/generate-link',       'Legal\LegalReviewController::generateLink/$1',   ['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/toggle-link',         'Legal\LegalReviewController::toggleLink/$1',     ['filter' => 'auth']);
$routes->post('legal/reviews/(:num)/archive',             'Legal\LegalReviewController::archive/$1',        ['filter' => 'auth']);

// Review eksternal (tanpa auth)
$routes->get ('legal/ext/(:segment)',                     'Legal\LegalReviewExtController::show/$1');
$routes->post('legal/ext/(:segment)/comment',             'Legal\LegalReviewExtController::comment/$1');

// ── Work Initiative Report ──────────────────────────────────────────────
$routes->get ('work-report',                              'WorkReportCtrl::index',                    ['filter' => 'auth']);
$routes->get ('work-report/admin',                        'WorkReportCtrl::admin',                    ['filter' => 'auth']);
$routes->post('work-report/store',                        'WorkReportCtrl::store',                    ['filter' => 'auth']);
$routes->post('work-report/(:num)/edit',                  'WorkReportCtrl::edit/$1',                  ['filter' => 'auth']);
$routes->post('work-report/(:num)/delete',                'WorkReportCtrl::delete/$1',                ['filter' => 'auth']);
$routes->post('work-report/(:num)/archive',               'WorkReportCtrl::archive/$1',               ['filter' => 'auth']);
$routes->post('work-report/(:num)/unarchive',             'WorkReportCtrl::unarchive/$1',             ['filter' => 'auth']);
$routes->post('work-report/(:num)/restore',               'WorkReportCtrl::restore/$1',               ['filter' => 'auth']);
$routes->get ('work-report/dashboard',                    'WorkReportCtrl::dashboard',                ['filter' => 'auth']);
$routes->post('work-report/(:num)/update',                'WorkReportCtrl::addUpdate/$1',             ['filter' => 'auth']);
$routes->post('work-report/(:num)/comment',               'WorkReportCtrl::addComment/$1',            ['filter' => 'auth']);
$routes->get ('work-report/(:num)/detail',                'WorkReportCtrl::detail/$1',                ['filter' => 'auth']);

$routes->get ('work-report/division',                     'WorkReportDeputyCtrl::index',              ['filter' => 'auth']);
$routes->post('work-report/division/store',               'WorkReportDeputyCtrl::store',              ['filter' => 'auth']);
$routes->post('work-report/division/(:num)/edit',         'WorkReportDeputyCtrl::edit/$1',            ['filter' => 'auth']);
$routes->post('work-report/division/(:num)/flag',         'WorkReportDeputyCtrl::flag/$1',            ['filter' => 'auth']);
$routes->post('work-report/division/(:num)/comment',      'WorkReportDeputyCtrl::addComment/$1',      ['filter' => 'auth']);
$routes->post('work-report/division/(:num)/reply-gm',     'WorkReportDeputyCtrl::replyGm/$1',         ['filter' => 'auth']);
$routes->get ('work-report/division/(:num)/detail',       'WorkReportDeputyCtrl::detail/$1',          ['filter' => 'auth']);

$routes->get ('work-report/gm',                           'WorkReportGmCtrl::index',                  ['filter' => 'auth']);
$routes->post('work-report/gm/(:num)/note',               'WorkReportGmCtrl::addNote/$1',             ['filter' => 'auth']);
