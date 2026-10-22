# Integration and dependencies

## FOIS core patches that depend on this extension

The FOIS project applies CiviCRM core patches that call into this extension.
Keep these names and shapes stable:

| Core file (patch) | Depends on |
| --- | --- |
| `CRM/Contribute/Form/Contribution/Confirm.php` (`membership_installment_amount_fix.patch`) | `CRM_Memberships_Helper::getSettingsConfig()`, its `memberships_contribution_page_id` key, and the session keys `membership_custom_signup` / `membership_custom_signup_page`. When they match, Confirm charges the amount set by the extension (installment amount) instead of the page amount. |
| `CRM/Member/BAO/Membership.php` (`membership_fix.patch`) | The `parent_signup_child` permission: `getContactMembership()` keeps the parent's selected membership, including Pending ones. |
| `Civi/Membership/OrderCompleteSubscriber.php` (`membership_no_end_date_extend.patch`) | Completes/renews memberships linked by the extension's `civicrm_membership` line items when an installment payment completes. |

Other FOIS core patches that affect the same records:
`civicrm_membership_type_per_domain.patch` (membership types limited to the
current domain), `civicrm_membership_status_check.patch`,
`task_19944_partial_paid.patch` (installment completion rule).

## Payment processors and installments

The extension does not talk to processors directly. Installments use core
recurring contributions: the first contribution is for the installment
amount, later ones are created by the processor (Authorize.Net, Stripe on
FOIS). The per-page installment cap is stored in
`civicrm_contribution_page.is_recur_installments_number` and exposed as a
ContributionPage field through the `civi.entity.fields::ContributionPage`
event (`memberships_alter_contribution_page_fields()`), which is what lets the
Amounts form save it and API4 return it.

## Other extensions

* The full/partial-paid groups and tags are described on the settings page as
  "required to register for an event"; that requirement is configured outside
  this extension (no other extension calls into it).
* Message templates: see [configuration](configuration.md#receipt-template).

## Legacy signup wizard

`civicrm/members/signup` (`CRM_Memberships_Controller_Memberships`, forms in
`CRM/Memberships/Form/Signup/`) is an earlier, stand-alone version of the
signup: contact selection → fee → review/payment → complete. It charges
through the deprecated `Contribution.transact` API with the source
"FOIS Membership Payment", and only requires `access CiviCRM`.

It still loads on CiviCRM 6.16 (deprecated Smarty calls were replaced) but
it was not tested end-to-end and is not linked from any menu. If no site uses
it, consider removing it (route, classes and templates).
