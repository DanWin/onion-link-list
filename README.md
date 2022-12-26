General information:
--------------------

This is a set of script to list Tor hidden services. An up-to-date copy can be downloaded at https://github.com/DanWin/onion-link-list

Installation instructions:
--------------------------

You'll need to have php with pdo_mysql, pcre, json and date extension, a web-server and a MySQL server installed.
When you have everything installed, you'll have to create a database and a user for the script.
Then edit the configuration in `common_config.php` to reflect the appropriate database settings and to modify the settings the way you like them.
Then copy the scripts to your web-server directory and run the `setup.php` script from cli, if possible.
Note: If you updated the script, please run `setup.php` again, to make sure, that any database changes are applied and no errors occur.
At last, set up cron jobs for the scripts in the `cron` directory.

Recommended schedule:

`update.php` - every 24 hours

`phishing_tests.php` - every 24 hours, shortly after `update.php`

`tests.php` - every 15 minutes

Translating:
------------

The scrip `update-translations.sh` can be used to update the language template and translation files from source.
It will generate the file `locale/main-website.pot` which you can then use as basis to create a new language file in `YOUR_LANG_CODE/LC_MESSAGES/main-website.po` and edit it with a translation program, such as [Poedit](https://poedit.net/).
Once you are done, you can open a pull request, or [email me](mailto:daniel@danwin1210.de), to include the translation.

Live Demo:
----------

If you want to see the scripts in action, you can visit my Tor hidden service http://donionsixbjtiohce24abfgsffo2l4tk26qx464zylumgejukfq2vead.onion/onions.php or via my clearnet proxy https://onions.danwin1210.de/onions.php if you don't have Tor installed.
