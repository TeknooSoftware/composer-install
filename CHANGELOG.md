#Teknoo Software - Composer Install - Change Log

##[0.0.10] - 2020-12-20
###Stable Release
- ignore when a file already exists but is identical to package

##[0.0.9] - 2020-12-03
###Stable Release
- Official Support of PHP8

##[0.0.8] - 2020-11-27
###Dev Release
- Fix installer when the command `composer require` is used instead of `composer update`

##[0.0.7] - 2020-11-27
###Dev Release
- Fix installer when the command `composer require` is used instead of `composer update`

##[0.0.6] - 2020-11-25
###Dev Release
- Fix path displayed using \realpath

##[0.0.5] - 2020-11-25
###Dev Release
- Clean prompted texts
 
##[0.0.3] - 2020-11-24
###Dev Release
- Fix bundles.php update when array_merge_recursive works on doublons to union all boolean value for each env
 
##[0.0.2] - 2020-11-23
###Dev Release
- Add configuration to define in installer's actions in the composer.json's extra, and disabled behavior to not 
 execute action for a repository, like a library or a metapackage.

##[0.0.1] - 2020-11-23
###Dev Release
- Require Composer 1.10 or 2.0+
- Provide three actions to install packages (bundle, config and route) for Symfony 4.0+ without require Symfony Recipe
- Under MIT Licence.
