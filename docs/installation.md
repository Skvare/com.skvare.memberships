# Installation and upgrade

## Install

On the FOIS sites the extension is managed by Composer. The project
`composer.json` downloads it into
`web/sites/default/civicrm/extensions/contrib/com.skvare.memberships`:

```json
"extra": {
  "civicrm": {
    "extensions": {
      "com.skvare.memberships": {
        "url": "https://github.com/Skvare/com.skvare.memberships/archive/refs/heads/civicrm_6_16.zip"
      }
    }
  }
}
```

The directory is ignored by Git in the site repository, so local edits are
overwritten by the next `composer install`. Make changes in the extension
repository and update the branch the URL points to.

Enable it once per database:

```bash
cv en memberships
```

Installing adds `civicrm_contribution_page.is_recur_installments_number`
(int, default 0). The upgrader only adds it when it is missing.
Uninstalling leaves the column in place, so the per-page installment limits
survive a reinstall.

## Permissions

| Permission | Who needs it |
| --- | --- |
| `parent_signup_child` ("Allow Parent to signup their Child") | The CMS role used by parents. The FOIS patch to `CRM_Member_BAO_Membership::getContactMembership()` (`membership_fix.patch`) uses it to keep the parent's selected membership, including a Pending one, when the signup renews it. |
| `administer CiviCRM` | The settings page. |
| `access CiviCRM` | Only the legacy `civicrm/members/signup` wizard. |

In Drupal grant `parent_signup_child` under **People » Permissions** (CiviCRM
section) to the parent role.

## Multi-site / multi-domain notes

* Settings are stored per CiviCRM domain (`memberships_config_<domainId>`).
  Configure each site on its own settings page.
* Each Drupal site directory has its own cached service container. After
  deploying new code, rebuild caches for **every** site, not only the default
  one, e.g. `drush -l http://<site-host> cr` per site, or empty the shared
  container cache with `drush sqlq "TRUNCATE cache_container"`.

## Upgrading to 1.1.0 / CiviCRM 6.16

1. Deploy the new extension code (Composer).
2. Run the CiviCRM database upgrade if core was upgraded, then
   `cv flush` (or **Administer » System Settings » Cleanup Caches**) and
   rebuild Drupal caches for every site.
3. No extension schema change is pending: existing installs are already at
   schema revision 1001 and the installments column exists.
4. If you want the membership table in online receipts, add the snippet from
   [configuration](configuration.md#receipt-template) to each domain's
   *Contributions - Receipt (on-line)* template.

See the [changelog](CHANGELOG.md) for behaviour changes in this release.
