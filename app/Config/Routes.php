<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Auth
$routes->get('login', 'Auth::index');
$routes->post('login', 'Auth::login');
$routes->get('logout', 'Auth::logout');

// Dashboard
$routes->get('/', 'Dashboard::index', ['filter' => 'auth']);

// Events
$routes->get('events', 'Events::index', ['filter' => 'auth']);
$routes->get('events/compare', 'EventCompare::index', ['filter' => 'auth']);
$routes->get('events/create', 'Events::create', ['filter' => 'auth']);
$routes->post('events/create', 'Events::store', ['filter' => 'auth']);
$routes->get('events/(:num)', 'Events::show/$1', ['filter' => 'auth']);
$routes->get('events/(:num)/edit', 'Events::edit/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/edit', 'Events::update/$1', ['filter' => 'auth']);
$routes->get('events/(:num)/delete', 'Events::delete/$1', ['filter' => 'auth']);

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
$routes->get('events/(:num)/content/(:num)/delete-item', 'EventContent::deleteItem/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/content/(:num)/realisasi/add', 'EventContent::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->get('events/(:num)/content/(:num)/realisasi/(:num)/delete', 'EventContent::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);

// Rundown
$routes->get('events/(:num)/rundown', 'EventContent::rundown/$1', ['filter' => 'auth']);
$routes->get('events/(:num)/rundown/print', 'EventContent::printRundown/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/rundown/save', 'EventContent::saveRundown/$1', ['filter' => 'auth']);

// Loyalty (Loyalty dept)
$routes->get('events/(:num)/loyalty', 'EventLoyaltyCtrl::index/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/add', 'EventLoyaltyCtrl::storeProgram/$1', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/edit', 'EventLoyaltyCtrl::updateProgram/$1/$2', ['filter' => 'auth']);
$routes->get('events/(:num)/loyalty/(:num)/delete', 'EventLoyaltyCtrl::deleteProgram/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/realisasi/add', 'EventLoyaltyCtrl::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->get('events/(:num)/loyalty/(:num)/realisasi/(:num)/delete', 'EventLoyaltyCtrl::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);
$routes->post('events/(:num)/loyalty/(:num)/hadiah/add', 'EventLoyaltyCtrl::storeHadiahItem/$1/$2', ['filter' => 'auth']);
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
$routes->get('events/(:num)/vm/(:num)/delete', 'EventVM::delete/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/vm/(:num)/realisasi/add', 'EventVM::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->get('events/(:num)/vm/(:num)/realisasi/(:num)/delete', 'EventVM::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);

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
$routes->get('creative/monthly-summary', 'CreativeCtrl::monthly', ['filter' => 'auth']);
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
$routes->get('events/(:num)/sponsors/(:num)/delete', 'EventSponsors::delete/$1/$2', ['filter' => 'auth']);
$routes->post('events/(:num)/sponsors/(:num)/realisasi/add', 'EventSponsors::storeRealisasi/$1/$2', ['filter' => 'auth']);
$routes->get('events/(:num)/sponsors/(:num)/realisasi/(:num)/delete', 'EventSponsors::deleteRealisasi/$1/$2/$3', ['filter' => 'auth']);

// Standalone Loyalty Programs
$routes->get('loyalty', 'LoyaltyCtrl::index', ['filter' => 'auth']);
$routes->get('loyalty/summary', 'LoyaltyCtrl::summary', ['filter' => 'auth']);
$routes->get('loyalty/detail/(:alpha)/(:num)', 'LoyaltyCtrl::detail/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/add', 'LoyaltyCtrl::storeProgram', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/edit', 'LoyaltyCtrl::updateProgram/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/delete', 'LoyaltyCtrl::deleteProgram/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/toggle', 'LoyaltyCtrl::toggleStatus/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/lock', 'LoyaltyCtrl::lock/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/unlock', 'LoyaltyCtrl::unlock/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/realisasi/add', 'LoyaltyCtrl::storeRealisasi/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/realisasi/(:num)/delete', 'LoyaltyCtrl::deleteRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/hadiah/add', 'LoyaltyCtrl::storeHadiahItem/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/hadiah/(:num)/delete', 'LoyaltyCtrl::deleteHadiahItem/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/hadiah/(:num)/realisasi/add', 'LoyaltyCtrl::storeHadiahRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/hadiah/(:num)/realisasi/(:num)/delete', 'LoyaltyCtrl::deleteHadiahRealisasi/$1/$2/$3', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/voucher/add', 'LoyaltyCtrl::storeVoucherItem/$1', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/voucher/(:num)/delete', 'LoyaltyCtrl::deleteVoucherItem/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/voucher/(:num)/realisasi/add', 'LoyaltyCtrl::storeVoucherRealisasi/$1/$2', ['filter' => 'auth']);
$routes->post('loyalty/(:num)/voucher/(:num)/realisasi/(:num)/delete', 'LoyaltyCtrl::deleteVoucherRealisasi/$1/$2/$3', ['filter' => 'auth']);

// VM Standalone (non-event)
$routes->get('vm/monthly-summary', 'VMStandalone::monthly', ['filter' => 'auth']);
$routes->get('vm', 'VMStandalone::index', ['filter' => 'auth']);
$routes->post('vm/add', 'VMStandalone::store', ['filter' => 'auth']);
$routes->post('vm/(:num)/edit', 'VMStandalone::update/$1', ['filter' => 'auth']);
$routes->get('vm/(:num)/delete', 'VMStandalone::delete/$1', ['filter' => 'auth']);
$routes->post('vm/(:num)/realisasi/add', 'VMStandalone::storeRealisasi/$1', ['filter' => 'auth']);
$routes->get('vm/(:num)/realisasi/(:num)/delete', 'VMStandalone::deleteRealisasi/$1/$2', ['filter' => 'auth']);

// Daily Traffic (Operasional — standalone)
$routes->get('traffic', 'Traffic::index', ['filter' => 'auth']);
$routes->get('traffic/summary', 'Traffic::summary', ['filter' => 'auth']);
$routes->get('traffic/import', 'Traffic::importForm', ['filter' => 'auth']);
$routes->post('traffic/import', 'Traffic::importPreview', ['filter' => 'auth']);
$routes->post('traffic/import/save', 'Traffic::importSave', ['filter' => 'auth']);
$routes->post('traffic/import/bulk-save', 'Traffic::importBulkSave', ['filter' => 'auth']);
$routes->get('traffic/compare', 'Traffic::compare', ['filter' => 'auth']);
$routes->get('traffic/input/(:alpha)/(:any)', 'Traffic::form/$1/$2', ['filter' => 'auth']);
$routes->get('traffic/input/(:alpha)', 'Traffic::form/$1', ['filter' => 'auth']);
$routes->post('traffic/save', 'Traffic::save', ['filter' => 'auth']);
$routes->get('traffic/delete/(:alpha)/(:any)', 'Traffic::delete/$1/$2', ['filter' => 'auth']);

// Event Locations master (admin only)
$routes->get('event-locations', 'EventLocations::index', ['filter' => 'auth:admin']);
$routes->post('event-locations/add', 'EventLocations::store', ['filter' => 'auth:admin']);
$routes->post('event-locations/(:num)/edit', 'EventLocations::update/$1', ['filter' => 'auth:admin']);
$routes->get('event-locations/(:num)/delete', 'EventLocations::delete/$1', ['filter' => 'auth:admin']);

// Traffic Doors master (admin only)
$routes->get('traffic-doors', 'TrafficDoors::index', ['filter' => 'auth:admin']);
$routes->post('traffic-doors/add', 'TrafficDoors::store', ['filter' => 'auth:admin']);
$routes->post('traffic-doors/(:num)/edit', 'TrafficDoors::update/$1', ['filter' => 'auth:admin']);
$routes->get('traffic-doors/(:num)/delete', 'TrafficDoors::delete/$1', ['filter' => 'auth:admin']);
$routes->post('traffic-doors/reorder', 'TrafficDoors::reorder', ['filter' => 'auth:admin']);

// Activity Logs
$routes->get('logs', 'Logs::index', ['filter' => 'auth']);

// Roles (admin only)
$routes->get('roles', 'Roles::index', ['filter' => 'auth:admin']);
$routes->post('roles/add', 'Roles::store', ['filter' => 'auth:admin']);
$routes->post('roles/(:num)/edit', 'Roles::update/$1', ['filter' => 'auth:admin']);
$routes->get('roles/(:num)/delete', 'Roles::delete/$1', ['filter' => 'auth:admin']);

// Departments (admin only)
$routes->get('departments', 'Departments::index', ['filter' => 'auth:admin']);
$routes->post('departments/add', 'Departments::store', ['filter' => 'auth:admin']);
$routes->get('departments/(:num)/edit', 'Departments::edit/$1', ['filter' => 'auth:admin']);
$routes->post('departments/(:num)/edit', 'Departments::update/$1', ['filter' => 'auth:admin']);
$routes->get('departments/(:num)/delete', 'Departments::delete/$1', ['filter' => 'auth:admin']);

// Users (admin only)
$routes->get('users', 'Users::index', ['filter' => 'auth:admin']);
$routes->post('users/add', 'Users::store', ['filter' => 'auth:admin']);
$routes->post('users/(:num)/edit', 'Users::update/$1', ['filter' => 'auth:admin']);
$routes->get('users/(:num)/toggle', 'Users::toggle/$1', ['filter' => 'auth:admin']);
$routes->get('users/(:num)/delete', 'Users::delete/$1', ['filter' => 'auth:admin']);

// Profile
$routes->get('profile', 'Users::profile', ['filter' => 'auth']);
$routes->post('profile', 'Users::updateProfile', ['filter' => 'auth']);
$routes->post('profile/theme', 'Users::updateTheme', ['filter' => 'auth']);
