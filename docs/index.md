# Membership custom signup (com.skvare.memberships)

This extension turns an ordinary CiviCRM contribution page into a family
membership checkout. A logged-in parent sees the related contacts (usually
their children) whose memberships still need paying for, the fee for each
child after discounts, and pays the total in one contribution — optionally in
installments. When the payment is processed the extension renews or creates
each child's membership, adds a line item per child, and tags/groups the
children as fully or partially paid.

It was built for the FOIS multi-domain CiviCRM install: every site is its own
CiviCRM domain and has its own configuration.

## What it does

| Area | Behaviour |
| --- | --- |
| Who is listed | Contacts related to the logged-in user through the configured relationship types (e.g. "Parent of"). |
| Who is charged | Related contacts with a membership of a configured type in an allowed status (typically Pending) and no active membership. |
| Fees | Per membership type: a regular fee, up to five dated discount sets with "Child 1…4+" prices, JCC prices and sibling discounts. |
| Family discounts | Financial aid (amount/type from custom fields on the parent, for members of a group) and a special group discount. |
| Installments | Recurring payment with the number of installments limited per contribution page. |
| After payment | Memberships renewed/created, one line item per child plus discount lines, MembershipPayment links, groups and tags. |
| Receipt | Optional membership table in the online receipt. |

## Requirements

* CiviCRM 6.16+ (Smarty 5), CiviMember enabled
* PHP 8.2 or 8.3 (tested on 8.3; lint-clean on 8.4)
* The "Membership Amount" price set (`default_membership_type_amount`,
  ID 2 on FOIS) with an option for each membership type that is sold
* Parents need a CMS login and the `parent_signup_child` CiviCRM permission

## Pages and components

| Path / file | Purpose |
| --- | --- |
| `civicrm/admin/memberships/setting` | Settings form (Administer » CiviMember » Custom Membership Signup Setting) |
| Configured contribution page(s) | The signup itself (`CRM_Contribute_Form_Contribution_Main/Confirm/ThankYou` + hooks) |
| Contribution page » Amounts tab | Adds "Maximum Offer installments" |
| `civicrm/members/signup` | Legacy stand-alone wizard, see [integration](integration.md#legacy-signup-wizard) |
| `memberships.php` | Hook implementations |
| `CRM/Memberships/Helper.php` | Settings, fee calculation, membership lists |
| `CRM/Memberships/Utils.php` | Related contacts, membership creation, line items, groups/tags |
| `templates/CRM/Memberships/Preview.tpl` | Membership table on the signup, confirm, thank-you pages and receipt |

## Contents

* [Installation and upgrade](installation.md)
* [Configuration](configuration.md)
* [Signup flow](signup-flow.md)
* [Fees and discounts](fees-and-discounts.md)
* [Memberships and accounting](memberships-and-accounting.md)
* [Integration and dependencies](integration.md)
* [Developer notes](developer.md)
* [Known issues and recommendations](known-issues.md)
* [Changelog](CHANGELOG.md)
