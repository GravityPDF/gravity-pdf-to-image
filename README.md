Gravity PDF to Image
==========================

[![Build Status](https://travis-ci.org/GravityPDF/gravity-pdf-to-image.svg?branch=development)](https://travis-ci.org/GravityPDF/gravity-pdf-to-image)

Gravity PDF to Image is a commercial plugin [available from GravityPDF.com](https://gravitypdf.com/shop/gravity-pdf-to-image-add-on/). The plugin is hosted here on a public GitHub repository in order to better facilitate community contributions from developers and users. If you have a suggestion, a bug report, or a patch for an issue, feel free to submit it here.

If you are using the plugin on a live site, please purchase a valid license from the website. **We cannot provide support to anyone that does not hold a valid license key**.

# About

This Git repository is for developers who want to contribute to Gravity PDF to Image. **Don't use it in production**. For production use, [purchase a license and install the packaged version from our online store](https://gravitypdf.com/shop/watermark-add-on/).

The `development` branch is considered our bleeding edge branch, with all new changes pushed to it. The `master` branch is our latest stable version of Gravity PDF to Image.

# Requirements

This plugin relies on the following packages to be installed on your server or development environment:

* [ImageMagick](https://www.imagemagick.org/)
* [Imagick](https://www.php.net/manual/en/book.imagick.php)
* [Ghostscript](https://www.ghostscript.com/)

# Installation

Before beginning, ensure you have [Git](https://git-scm.com/) and [Composer](https://getcomposer.org/) installed and their commands are globally accessible via the command line.

1. Clone the repository using `git clone https://github.com/GravityPDF/gravity-pdf-to-image/`
1. Open your terminal / command prompt to the Gravity PDF to Image root directory and run `composer install`
1. Copy the plugin to your WordPress plugin directory (if not there already) and active through your WordPress admin area

### Run Unit Tests

#### PHPUnit

We use PHPUnit to test out all the PHP we write. The tests are located in `tests/phpunit/unit-tests/`

Installing the testing environment is best done using a flavour of Vagrant (try [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV)) or Docker.

1. From your terminal SSH into your Vagrant box using the `vagrant ssh` command
2. `cd` into the root of your Gravity PDF to Image directory
3. Run `bash tests/bin/install.sh gravitypdf_test root root localhost` where `root root` is substituted for your mysql username and password (VVV users can run the command as is).
4. Upon success you can run `vendor/bin/phpunit`