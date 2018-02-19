# Post Promoter Pro
### The most effective way to promote your WordPress content

[![Build Status](https://travis-ci.org/postpromoterpro/post-promoter-pro.svg?branch=master)](https://travis-ci.org/postpromoterpro/post-promoter-pro) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/postpromoterpro/post-promoter-pro/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/postpromoterpro/post-promoter-pro/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/postpromoterpro/post-promoter-pro/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/postpromoterpro/post-promoter-pro/?branch=master)

This is the public repository of _Post Promoter Pro_, the most effective way to share your WordPress content across your Twitter, Facebook, and LinkedIn accounts.

#### About
Post Promoter Pro is a commercial plugin, available at [PostPromoterPro.com](https://postpromoterpro.com/pricing/?discount=GITHUBREPO&utm_campaign=GitHub&utm_source=readme&utm_medium=github). This public repository is here to allow collaboration, bug reporting, and transparency into its development and direction.

#### Bug Reports
If you are a user looking for Technical Support, please use the ['Support'](https://postpromoterpro.com/support/) section of the website. This repository is for development related issues and tracking only. If you request support in this arena, you will be asked to visit the following page, and provide your license key.

https://postpromoterpro.com/support/

#### Using with a license key
Since Post Promoter Pro is a commercial plugin, a license key is necessary for support and automatic upgrades. It also requires a key to retrieve the social network application keys and secrets. These are applications specific to our product and we provide them to license holders in order to make the Twitter, Facebook, LinkedIn, and Bitly sign in process as streamlined as possible. To purchase one you can visit the [Post Promoter Pro Website](https://postpromoterpro.com/pricing/?discount=GITHUBREPO&utm_campaign=GitHub&utm_source=readme&utm_medium=github)

#### Using with your own Social Media Keys/Secrets
If, however, you wish to contribute to the codebase without a license, you can do so by using local social media keys, for applications you create yourself. In order to do this you must visit each social media's "developer" area and configure an application.

To set up local keys, do the following:

1. Set up applications with social media services

2. Copy the `ppp-social-tokens-sample.json` file (in the root of the repo) to `wp-content/uploads/ppp/` and rename it `ppp-social-tokens.json`

3. Place your tokens in the appropriate places within the `.json` file above

4. Authenticate your social media accounts

5. Code amazing things

6. Submit pull requests

If you are running Nginx, you will need to put this into your server configuration to prevent direct access to the file.
```
  location ~* ppp-social-tokens.json$ {
  	deny all;
  	access_log off;
  	log_not_found off;
  }
```

