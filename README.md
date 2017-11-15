# magerun
Extra commands for n98-magerun

## Installation

1. Clone with GitHub
2. Update your n98-magerun.yaml to add the following:

```yaml
autoloaders:
  # Namespace => path to your libs
  ProjectEight: /Users/projecteight/PhpStormProjects/magerun/src

commands:
  customCommands:
    - ProjectEight\Magento\Command\Developer\Environment\SetCommand
    # ...etc
```

## Commands

### sys:patch-scanner
Scans a Magento environment to detect if the specified SUPEE patch has been installed.

#### Plan
* [x] Pass patch file as CLI argument
* [x] Command parses list of files from patch file
* [ ] Command parses 'hunks' from patch file
* [ ] Then attempts to find them in the same location
* [ ] Command outputs report which states how many of the hunks in the patch file matched


### dev:env:set
Update a Magento environment to use the specified settings. Run this command after setting up a new Magento 1.x instance to set default config values.

#### Usage

```bash
$ n98-magerun.phar dev:env:set --help

Usage:
  dev:env:set [<env>]

Arguments:
  env                        An environment to configure.
```

#### Examples

```bash
# Updates the Magento environment to the settings specified in the 'localhost' key in the YAML
$ n98-magerun.phar dev:env:set localhost

# Choose an environment to update from those in the n98-magerun.yaml
$ n98-magerun.phar dev:env:set
```

Configuration scopes and values are set in the n98-magerun.yaml file.

If no environment code (e.g. 'localhost', 'test', 'staging') is specified on the command line, the command reads the YAML and allows the user to choose an environment.

#### Configuration

Add the following to your n98-magerun.yaml:

```yaml
commands:
  customCommands:
    - ProjectEight\Magento\Command\Developer\Environment\SetCommand

  ProjectEight\Magento\Command\Developer\Environment\SetCommand:
    environments:
      localhost:    # Environment key
        config:     
          default:  # Configuration scope (default, websites, stores)
            0:      # Configuration scope ID 
              general/country/default: GB
              general/store_information/merchant_country: FR
              design/head/demonotice: 1
              trans_email/ident_general/email: projecteight@example.com
              trans_email/ident_sales/email: projecteight@example.com
              trans_email/ident_support/email: projecteight@example.com
              trans_email/ident_custom1/email: projecteight@example.com
              trans_email/ident_custom2/email: projecteight@example.com
              contacts/email/recipient_email: projecteight@example.com
              sitemap/generate/error_email: projecteight@example.com
              customer/password/require_admin_user_to_change_user_password: 0
              tax/defaults/country: GB
              tax/defaults/postcode: "YO24 1BF"
              shipping/origin/country_id: GB
              shipping/origin/region_id: North Yorkshire
              shipping/origin/postcode: "YO24 1BF"
#              Shipping methods:
              carriers/dhlint/active: 0
              carriers/dhl/active: 0
              carriers/fedex/active: 0
              carriers/usps/active: 0
              carriers/ups/active: 0
              google/analytics/account: UA-123456-AB
              payment/account/merchant_country: GB
              # PayPal Express Checkout:
#              payment/express_checkout_required_express_checkout/business_account
#              payment/express_checkout_required_express_checkout/api_authentication
#              payment/express_checkout_required_express_checkout/api_username
#              payment/express_checkout_required_express_checkout/api_password
#              payment/express_checkout_required_express_checkout/api_signature
#              payment/express_checkout_required_express_checkout/sandbox_flag
#              payment/express_checkout_required/enable_express_checkout: 0
              payment/settings_ec/payment_action: Sale
              payment/settings_ec_advanced/debug: 1
#              Other payment methods:
              payment/checkmo/active: 1
              payment/checkmo/specificcountry: GB,DE
              admin/security/extensions_compatibility_mode: Disabled
              system/smtp/disable: 1
              dev/log/active: 1
              dev/restrict/allow_ips: 127.0.0.1
      staging:
        config:
          websites:
            1:
              general/country/default: FR
          stores:
            3:
              general/country/default: DE
