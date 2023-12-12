# com.skvare.memberships

![Screenshot](/images/membership_ext_setting.png)

Membership customized signup process

This extension allows you to sign up for membership for multiple contacts with discount (Discount based on contact present in the group).

This extension is used for sibling discounts and can be used as a generic feature.


## Requirements

* PHP v7.0+
* CiviCRM: 5.45, 5,63

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl com.skvare.memberships@https://github.com/FIXME/com.skvare.memberships/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/FIXME/com.skvare.memberships.git
cv en memberships
```

## Usage

* Create a contribution page with a single fee amount.
* That amount gets updated based on the number of contacts selected, and a discount is applied to the total amount.


![Screenshot](/images/membership_signup.png)

![Screenshot](/images/membership_signup_confirm.png)
