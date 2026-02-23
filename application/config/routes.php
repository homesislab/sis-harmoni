<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Routes
 * - Grouped by feature/module (flow-friendly)
 * - Consistent alignment + indentation
 * - Keep route patterns unchanged
 */

$route['default_controller'] = 'welcome';
$route['404_override']       = '';
$route['translate_uri_dashes'] = FALSE;

/* =========================================================
 * Onboarding / Registration Flow
 * ======================================================= */
$route['api/v1/onboarding/units']['GET']           = 'api/Onboarding/units';
$route['api/v1/onboarding/blocks']['GET']          = 'api/Onboarding/blocks';
$route['api/v1/onboarding/check-username']['GET']  = 'api/Onboarding/check_username';
$route['api/v1/onboarding/register']['POST']       = 'api/Onboarding/register';

$route['api/v1/registrations']['GET']                      = 'api/Registrations/index';
$route['api/v1/registrations/(:num)']['GET']               = 'api/Registrations/show/$1';
$route['api/v1/registrations/(:num)/approve']['POST']      = 'api/Registrations/approve/$1';
$route['api/v1/registrations/(:num)/reject']['POST']       = 'api/Registrations/reject/$1';

/* =========================================================
 * Auth
 * ======================================================= */
$route['api/v1/auth/login']['POST']   = 'api/Auth/login';
$route['api/v1/auth/me']['GET']       = 'api/Auth/me';
$route['api/v1/auth/me']['PUT']       = 'api/Auth/update_me';
$route['api/v1/auth/logout']['POST']  = 'api/Auth/logout';

/* =========================================================
 * Profile
 * ======================================================= */
$route['api/v1/profile/family-accounts']['GET']                = 'api/ProfileFamilyAccounts/index';
$route['api/v1/profile/family-accounts']['POST']               = 'api/ProfileFamilyAccounts/store';
$route['api/v1/profile/family-accounts/(:num)']['PUT']         = 'api/ProfileFamilyAccounts/update/$1';

/* =========================================================
 * Users / Roles / Permissions
 * ======================================================= */
$route['api/v1/users']['GET']                 = 'api/Users/index';
$route['api/v1/users']['POST']                = 'api/Users/store';
$route['api/v1/users/(:num)']['GET']          = 'api/Users/show/$1';
$route['api/v1/users/(:num)']['PUT']          = 'api/Users/update/$1';
$route['api/v1/users/(:num)/roles']['PUT']    = 'api/Users/roles/$1';

$route['api/v1/roles']['GET']                         = 'api/Roles/index';
$route['api/v1/roles']['POST']                        = 'api/Roles/store';
$route['api/v1/roles/(:num)']['GET']                  = 'api/Roles/show/$1';
$route['api/v1/roles/(:num)']['PUT']                  = 'api/Roles/update/$1';
$route['api/v1/roles/(:num)']['DELETE']               = 'api/Roles/destroy/$1';
$route['api/v1/roles/(:num)/permissions']['PUT']      = 'api/Roles/set_permissions/$1';

$route['api/v1/permissions']['GET']          = 'api/Permissions/index';

/* =========================================================
 * People & Households
 * ======================================================= */
$route['api/v1/persons']['GET']              = 'api/Persons/index';
$route['api/v1/persons']['POST']             = 'api/Persons/store';
$route['api/v1/persons/me']['GET']           = 'api/Persons/me';
$route['api/v1/persons/(:num)']['GET']       = 'api/Persons/show/$1';
$route['api/v1/persons/(:num)']['PUT']       = 'api/Persons/update/$1';
$route['api/v1/persons/(:num)']['DELETE']    = 'api/Persons/destroy/$1';

$route['api/v1/households']['GET']           = 'api/Households/index';
$route['api/v1/households']['POST']          = 'api/Households/store';
$route['api/v1/households/(:num)']['GET']    = 'api/Households/show/$1';
$route['api/v1/households/(:num)']['PUT']    = 'api/Households/update/$1';

$route['api/v1/household-members']['POST']           = 'api/HouseholdMembers/store';
$route['api/v1/household-members/(:num)']['PUT']     = 'api/HouseholdMembers/update/$1';
$route['api/v1/household-members/(:num)']['DELETE']  = 'api/HouseholdMembers/destroy/$1';

/* =========================================================
 * Houses / Occupancy / Ownership / Claims
 * ======================================================= */
$route['api/v1/houses']['GET']               = 'api/House/index';
$route['api/v1/houses']['POST']              = 'api/House/store';
$route['api/v1/houses/(:num)']['GET']        = 'api/House/show/$1';
$route['api/v1/houses/(:num)']['PUT']        = 'api/House/update/$1';

$route['api/v1/house-claims']['GET']                  = 'api/HouseClaims/index';
$route['api/v1/house-claims']['POST']                 = 'api/HouseClaims/store';
$route['api/v1/house-claims/(:num)/approve']['POST']  = 'api/HouseClaims/approve/$1';
$route['api/v1/house-claims/(:num)/reject']['POST']   = 'api/HouseClaims/reject/$1';

$route['api/v1/ownerships']['GET']           = 'api/Ownerships/index';
$route['api/v1/ownerships']['POST']          = 'api/Ownerships/store';

$route['api/v1/occupancies']['GET']          = 'api/Occupancies/index';
$route['api/v1/occupancies']['POST']         = 'api/Occupancies/store';
$route['api/v1/occupancies/(:num)']['PUT']   = 'api/Occupancies/update/$1';

/* =========================================================
 * Vehicles
 * ======================================================= */
$route['api/v1/vehicles']['GET']             = 'api/Vehicles/index';
$route['api/v1/vehicles']['POST']            = 'api/Vehicles/store';
$route['api/v1/vehicles/(:num)']['PUT']      = 'api/Vehicles/update/$1';
$route['api/v1/vehicles/(:num)']['DELETE']   = 'api/Vehicles/destroy/$1';

/* =========================================================
 * Billing / Charges
 * ======================================================= */
$route['api/v1/charge-types']['GET']                 = 'api/ChargeTypes/index';
$route['api/v1/charge-types']['POST']                = 'api/ChargeTypes/store';
$route['api/v1/charge-types/(:num)']['GET']          = 'api/ChargeTypes/show/$1';
$route['api/v1/charge-types/(:num)']['PUT']          = 'api/ChargeTypes/update/$1';
$route['api/v1/charge-types/(:num)']['DELETE']       = 'api/ChargeTypes/destroy/$1';

$route['api/v1/charge-components']['GET']            = 'api/ChargeComponents/index';
$route['api/v1/charge-components']['POST']           = 'api/ChargeComponents/store';
$route['api/v1/charge-components/(:num)']['PUT']     = 'api/ChargeComponents/update/$1';
$route['api/v1/charge-components/(:num)']['DELETE']  = 'api/ChargeComponents/destroy/$1';
$route['api/v1/charge-components/reorder']['POST']   = 'api/ChargeComponents/reorder';

$route['api/v1/billing/generate']['POST']            = 'api/Billing/generate';

/* =========================================================
 * Invoices (Admin) + My Invoices (Resident)
 * ======================================================= */
$route['api/v1/invoices']['GET']              = 'api/Invoices/index';
$route['api/v1/invoices']['POST']             = 'api/Invoices/store';
$route['api/v1/invoices/(:num)']['GET']       = 'api/Invoices/show/$1';
$route['api/v1/invoices/(:num)']['PUT']       = 'api/Invoices/update/$1';
$route['api/v1/invoices/(:num)']['DELETE']    = 'api/Invoices/destroy/$1';

$route['api/v1/my/invoices']['GET']           = 'api/MyInvoices/index';
$route['api/v1/my/invoices/(:num)']['GET']    = 'api/MyInvoices/show/$1';
$route['api/v1/my/invoices/ensure']['POST']   = 'api/MyInvoices/ensure';
$route['api/v1/my/invoices/preview']['POST']  = 'api/MyInvoices/preview';

/* =========================================================
 * Payments
 * ======================================================= */
$route['api/v1/payments']['GET']                    = 'api/Payments/index';
$route['api/v1/payments']['POST']                   = 'api/Payments/store';
$route['api/v1/payments/(:num)']['GET']             = 'api/Payments/show/$1';
$route['api/v1/payments/(:num)/approve']['POST']    = 'api/Payments/approve/$1';
$route['api/v1/payments/(:num)/reject']['POST']     = 'api/Payments/reject/$1';

/* =========================================================
 * Ledger (Accounts + Entries)
 * ======================================================= */
$route['api/v1/ledger/accounts']['GET']             = 'api/LedgerAccounts/index';
$route['api/v1/ledger/accounts']['POST']            = 'api/LedgerAccounts/store';
$route['api/v1/ledger/accounts/(:num)']['GET']      = 'api/LedgerAccounts/show/$1';
$route['api/v1/ledger/accounts/(:num)']['PUT']      = 'api/LedgerAccounts/update/$1';
$route['api/v1/ledger/accounts/(:num)']['DELETE']   = 'api/LedgerAccounts/destroy/$1';

$route['api/v1/ledger/entries']['GET']              = 'api/LedgerEntries/index';
$route['api/v1/ledger/entries']['POST']             = 'api/LedgerEntries/store';

/* =========================================================
 * Content: Posts / Events
 * ======================================================= */
$route['api/v1/posts']['GET']               = 'api/Posts/index';
$route['api/v1/posts']['POST']              = 'api/Posts/store';
$route['api/v1/posts/(:num)']['GET']        = 'api/Posts/show/$1';
$route['api/v1/posts/(:num)']['PUT']        = 'api/Posts/update/$1';
$route['api/v1/posts/(:num)']['DELETE']     = 'api/Posts/destroy/$1';

$route['api/v1/events']['GET']              = 'api/Events/index';
$route['api/v1/events']['POST']             = 'api/Events/store';
$route['api/v1/events/(:num)']['GET']       = 'api/Events/show/$1';
$route['api/v1/events/(:num)']['PUT']       = 'api/Events/update/$1';
$route['api/v1/events/(:num)']['DELETE']    = 'api/Events/destroy/$1';

/* =========================================================
 * Polls
 * ======================================================= */
$route['api/v1/polls']['GET']                       = 'api/Polls/index';
$route['api/v1/polls']['POST']                      = 'api/Polls/store';
$route['api/v1/polls/(:num)']['GET']                = 'api/Polls/show/$1';
$route['api/v1/polls/(:num)']['PUT']                = 'api/Polls/update/$1';
$route['api/v1/polls/(:num)']['DELETE']             = 'api/Polls/destroy/$1';
$route['api/v1/polls/(:num)/publish']['POST']       = 'api/Polls/publish/$1';
$route['api/v1/polls/(:num)/close']['POST']         = 'api/Polls/close/$1';
$route['api/v1/polls/(:num)/vote']['POST']          = 'api/Polls/vote/$1';
$route['api/v1/polls/(:num)/my-vote']['GET']        = 'api/Polls/my_vote/$1';
$route['api/v1/polls/(:num)/result']['GET']         = 'api/Polls/results/$1';

$route['api/v1/polls/options']['POST']              = 'api/PollOptions/store';
$route['api/v1/polls/options/(:num)']['PUT']        = 'api/PollOptions/update/$1';
$route['api/v1/polls/options/(:num)']['DELETE']     = 'api/PollOptions/destroy/$1';

/* =========================================================
 * Fundraisers + Donations + Updates
 * ======================================================= */
$route['api/v1/fundraisers']['GET']                        = 'api/Fundraisers/index';
$route['api/v1/fundraisers']['POST']                       = 'api/Fundraisers/store';
$route['api/v1/fundraisers/(:num)']['GET']                 = 'api/Fundraisers/show/$1';
$route['api/v1/fundraisers/(:num)']['PUT']                 = 'api/Fundraisers/update/$1';
$route['api/v1/fundraisers/(:num)']['DELETE']              = 'api/Fundraisers/destroy/$1';
$route['api/v1/fundraisers/(:num)/close']['POST']          = 'api/Fundraisers/close/$1';
$route['api/v1/fundraisers/(:num)/donate']['POST']         = 'api/Fundraisers/donate/$1';
$route['api/v1/fundraisers/(:num)/donations']['GET']       = 'api/Fundraisers/donations/$1';

$route['api/v1/fundraisers/donations']['GET']                   = 'api/FundraiserDonations/index_admin';
$route['api/v1/fundraisers/donations/(:num)/approve']['POST']   = 'api/FundraiserDonations/approve/$1';
$route['api/v1/fundraisers/donations/(:num)/reject']['POST']    = 'api/FundraiserDonations/reject/$1';

$route['api/v1/fundraisers/(:num)/updates']['GET']              = 'api/FundraiserUpdates/index/$1';
$route['api/v1/fundraisers/updates']['POST']                    = 'api/FundraiserUpdates/store';
$route['api/v1/fundraisers/updates/(:num)']['PUT']              = 'api/FundraiserUpdates/update/$1';
$route['api/v1/fundraisers/updates/(:num)']['DELETE']           = 'api/FundraiserUpdates/destroy/$1';

/* =========================================================
 * Security
 * ======================================================= */
$route['api/v1/security/guards']['GET']                         = 'api/SecurityGuards/index';
$route['api/v1/security/guards']['POST']                        = 'api/SecurityGuards/store';
$route['api/v1/security/guards/(:num)']['GET']                  = 'api/SecurityGuards/show/$1';
$route['api/v1/security/guards/(:num)']['PUT']                  = 'api/SecurityGuards/update/$1';
$route['api/v1/security/guards/(:num)']['DELETE']               = 'api/SecurityGuards/destroy/$1';

$route['api/v1/security/shifts']['GET']                         = 'api/SecurityShifts/index';
$route['api/v1/security/shifts']['POST']                        = 'api/SecurityShifts/store';
$route['api/v1/security/shifts/(:num)']['GET']                  = 'api/SecurityShifts/show/$1';
$route['api/v1/security/shifts/(:num)']['PUT']                  = 'api/SecurityShifts/update/$1';
$route['api/v1/security/shifts/(:num)']['DELETE']               = 'api/SecurityShifts/destroy/$1';

$route['api/v1/security/attendance']['GET']                     = 'api/SecurityAttendance/index';
$route['api/v1/security/attendance/summary']['GET']             = 'api/SecurityAttendance/summary';
$route['api/v1/security/attendance/calendar']['GET']            = 'api/SecurityAttendance/calendar';
$route['api/v1/security/attendance/check-in']['POST']           = 'api/SecurityAttendance/check_in';
$route['api/v1/security/attendance/check-out']['POST']          = 'api/SecurityAttendance/check_out';
$route['api/v1/security/attendance/manual-log']['POST']         = 'api/SecurityAttendance/manual_log';
$route['api/v1/security/attendance/(:num)']['DELETE']           = 'api/SecurityAttendance/destroy/$1';

/* =========================================================
 * Guest Visits (Admin) + My Guest Visits
 * ======================================================= */
$route['api/v1/guest-visits']['GET']                            = 'api/GuestVisits/index';
$route['api/v1/guest-visits']['POST']                           = 'api/GuestVisits/store';
$route['api/v1/guest-visits/(:num)/check-out']['POST']          = 'api/GuestVisits/check_out/$1';

$route['api/v1/my/guest-visits']['GET']                         = 'api/MyGuestVisits/index';

/* =========================================================
 * Emergencies
 * ======================================================= */
$route['api/v1/emergencies']['GET']                             = 'api/Emergencies/index';
$route['api/v1/emergencies']['POST']                            = 'api/Emergencies/store';
$route['api/v1/emergencies/(:num)/acknowledge']['POST']         = 'api/Emergencies/acknowledge/$1';
$route['api/v1/emergencies/(:num)/resolve']['POST']             = 'api/Emergencies/resolve/$1';
$route['api/v1/emergencies/(:num)/cancel']['POST']              = 'api/Emergencies/cancel/$1';

$route['api/v1/my/emergencies']['GET']                          = 'api/MyEmergencies/index';

/* =========================================================
 * Feedback
 * ======================================================= */
$route['api/v1/feedback-categories']['GET']                     = 'api/FeedbackCategories/index';

$route['api/v1/feedbacks']['GET']                               = 'api/Feedback/index';
$route['api/v1/feedbacks']['POST']                              = 'api/Feedback/store';
$route['api/v1/feedbacks/(:num)']['GET']                        = 'api/Feedback/show/$1';
$route['api/v1/feedbacks/(:num)/respond']['POST']               = 'api/Feedback/respond/$1';
$route['api/v1/feedbacks/(:num)/close']['POST']                 = 'api/Feedback/close/$1';

$route['api/v1/my/feedbacks']['GET']                            = 'api/MyFeedbacks/index';
$route['api/v1/my/feedbacks/(:num)']['GET']                     = 'api/MyFeedbacks/show/$1';

/* =========================================================
 * UMKM: Businesses + Products
 * ======================================================= */
$route['api/v1/businesses']['GET']                              = 'api/Businesses/index';
$route['api/v1/businesses']['POST']                             = 'api/Businesses/store';
$route['api/v1/businesses/(:num)']['GET']                       = 'api/Businesses/show/$1';
$route['api/v1/businesses/(:num)']['PUT']                       = 'api/Businesses/update/$1';
$route['api/v1/businesses/(:num)/approve']['POST']              = 'api/Businesses/approve/$1';
$route['api/v1/businesses/(:num)/reject']['POST']               = 'api/Businesses/reject/$1';
$route['api/v1/businesses/(:num)/resubmit']['POST']             = 'api/Businesses/resubmit/$1';
$route['api/v1/businesses/(:num)/products']['GET']              = 'api/Businesses/products/$1';

$route['api/v1/products']['GET']                                = 'api/Products/index';
$route['api/v1/products']['POST']                               = 'api/Products/store';
$route['api/v1/products/(:num)']['GET']                         = 'api/Products/show/$1';
$route['api/v1/products/(:num)']['PUT']                         = 'api/Products/update/$1';
$route['api/v1/products/(:num)']['DELETE']                      = 'api/Products/destroy/$1';

/* =========================================================
 * Inventory
 * ======================================================= */
$route['api/v1/inventories']['GET']                             = 'api/Inventories/index';
$route['api/v1/inventories']['POST']                            = 'api/Inventories/store';
$route['api/v1/inventories/(:num)']['GET']                      = 'api/Inventories/show/$1';
$route['api/v1/inventories/(:num)']['PUT']                      = 'api/Inventories/update/$1';
$route['api/v1/inventories/(:num)/archive']['POST']             = 'api/Inventories/archive/$1';
$route['api/v1/inventories/(:num)/checkout']['POST']            = 'api/Inventories/checkout/$1';
$route['api/v1/inventories/(:num)/return']['POST']              = 'api/Inventories/return_item/$1';

/* =========================================================
 * Important Contacts
 * ======================================================= */
$route['api/v1/important-contacts']['GET']                       = 'api/ImportantContacts/index';
$route['api/v1/important-contacts']['POST']                      = 'api/ImportantContacts/store';
$route['api/v1/important-contacts/(:num)']['GET']                = 'api/ImportantContacts/show/$1';
$route['api/v1/important-contacts/(:num)']['PUT']                = 'api/ImportantContacts/update/$1';
$route['api/v1/important-contacts/(:num)']['DELETE']             = 'api/ImportantContacts/destroy/$1';

/* =========================================================
 * Meeting Minutes
 * ======================================================= */
$route['api/v1/meeting-minutes']['GET']                          = 'api/MeetingMinutes/index';
$route['api/v1/meeting-minutes']['POST']                         = 'api/MeetingMinutes/store';
$route['api/v1/meeting-minutes/(:num)']['GET']                   = 'api/MeetingMinutes/show/$1';
$route['api/v1/meeting-minutes/(:num)']['PUT']                   = 'api/MeetingMinutes/update/$1';
$route['api/v1/meeting-minutes/(:num)']['DELETE']                = 'api/MeetingMinutes/destroy/$1';
$route['api/v1/meeting-minutes/(:num)/action-items']['POST']     = 'api/MeetingMinutes/action_items_create/$1';

$route['api/v1/meeting-action-items/(:num)']['PUT']              = 'api/MeetingActionItems/update/$1';
$route['api/v1/meeting-action-items/(:num)']['DELETE']           = 'api/MeetingActionItems/destroy/$1';

/* =========================================================
 * Uploads
 * ======================================================= */
$route['api/v1/uploads/image']['POST']                           = 'api/Uploads/image';

/* =========================================================
 * Dashboard & Audit Logs
 * ======================================================= */
$route['api/v1/dashboard/summary']['GET']                        = 'api/Dashboard/summary';
$route['api/v1/dashboard/finance']['GET']                        = 'api/Dashboard/finance';
$route['api/v1/dashboard/activity']['GET']                       = 'api/Dashboard/activity';
$route['api/v1/dashboard/report']['GET']                         = 'api/Dashboard/report';

// Org Units (for dashboard filter)
$route['api/v1/org-units']['GET']                               = 'api/OrgUnits/index';

$route['api/v1/audit-logs']['GET']                               = 'api/AuditLogs/index';
