<?php

namespace DrupalTemplateSymfony\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ScriptHandler {

  /**
   * Configures Drupal repo name
   * Creates & configures settings.php
   * Configures drupal-sf-dic
   * Creates & configures profil & themes
   *
   * TODO Webpack integration & config
   * TODO patches integration
   *
   * @param Event $event
   */
  public static function postCreateProject(Event $event) {
    $drupalFinder = new \DrupalFinder\DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();
    $composerRoot = $drupalFinder->getComposerRoot();

    $event->getIO()->write("                                   ");
    $event->getIO()->write("-----------------------------------");
    $event->getIO()->write("                                   ");
    $event->getIO()->write("           Makina Corpus           ");
    $event->getIO()->write("        drupal-template-sf         ");
    $event->getIO()->write("                                   ");
    $event->getIO()->write("-----------------------------------");
    $event->getIO()->write("                                   ");
    $event->getIO()->write("    A composer template binding    ");
    $event->getIO()->write("         Drupal 7 & Symfony        ");
    $event->getIO()->write("                                   ");
    $event->getIO()->write("-----------------------------------");
    $event->getIO()->write("                                   ");

    // Choose Drupal repository name
    $drupalRoot = ScriptHandler::configDrupalDir($event, $drupalRoot, $composerRoot);

    // Configure Drupal-Sf-Dic
    ScriptHandler::configSfDic($event, $drupalRoot, $composerRoot);

    // Create settings.php
    $settingsPath = ScriptHandler::createSettings($event, $drupalRoot, $composerRoot);

    // Create & configure new Profile (with or without themes)
    ScriptHandler::createProfile($event, $drupalRoot, $composerRoot);

    // Configure Webpack
    // TODO

    $event->getIO()->write(" ");
  }

  /**
   * Configures the Drupal Repository name
   *
   * @param Event $event
   * @param string $drupalRoot
   * @param string $composerRoot
   * @return string  drupal root
   */
  private static function configDrupalDir(Event $event, string $drupalRoot, string $composerRoot) {
    // Ask new profile name
    $drupalDirName = $event->getIO()->ask("\nDrupal directory name (default : 'web'):", 'web');

    while (!preg_match("/^[a-z_]*$/", $drupalDirName)) {
      $event->getIO()->write("\nIncorrect input : can only contains lowercase letters and underscores");
      $drupalDirName = $event->getIO()->ask("Drupal directory name (default : 'web'):", 'web');
    }

    if ($drupalDirName == basename($drupalRoot)) {
      return $drupalRoot;
    }

    $oldDrupalDirName = basename($drupalRoot);
    $oldDrupalRoot = $drupalRoot;
    $drupalRoot = dirname($oldDrupalRoot) . "/" . $drupalDirName;

    // Change repo name in file system
    rename($oldDrupalRoot, $drupalRoot);

    // Change all occurrences of $oldDrupalRepo in composer.json
    $composerJson = file($composerRoot . "/composer.json");
    $composerJson = str_replace('"' . $oldDrupalDirName . '/', '"' . $drupalDirName . '/', $composerJson);
    file_put_contents($composerRoot . "/composer.json", $composerJson);

    // Change all occurrences of $oldDrupalRepo in .gitignore
    $composerJson = file($composerRoot . "/.gitignore");
    $composerJson = str_replace($oldDrupalDirName . '/', $drupalDirName . '/', $composerJson);
    file_put_contents($composerRoot . "/.gitignore", $composerJson);

    return $drupalRoot;
  }

  /**
   * Creates & inits settings.php
   *
   * @param Event $event
   * @param string $drupalRoot
   * @param string $composerRoot
   * @return string settings.php path
   */
  private static function createSettings(Event $event, string $drupalRoot, string $composerRoot) {
    $fs = new Filesystem();

    if ($fs->exists($drupalRoot . '/sites/default/settings.php')) {
      if (!$event->getIO()->askConfirmation("\nsites/default/settings.php already exists, do you want to override it [Y,n] ?")) {
        return $drupalRoot . '/sites/default/settings.php';
      }
    }

    // Prepare the settings file for installation
    $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
    $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);

    // Creation of files dir
    if (!file_exists($drupalRoot . '/sites/default/files')) {
      mkdir($drupalRoot . '/sites/default/files');
    }
    $fs->chmod($drupalRoot . '/sites/default/files', 0777);

    return $drupalRoot . '/sites/default/settings.php';
  }

  /**
   * Configures drupal-sf-dic module
   *
   * @param Event $event
   * @param string $drupalRoot
   * @param string $composerRoot
   */
  private static function configSfDic(Event $event, string $drupalRoot, string $composerRoot) {
    $fs = new Filesystem();

    // Add param in default.settings.php
    $settingsPath = $drupalRoot . '/sites/default/default.settings.php';
    $settings = file($settingsPath);

    $settings[] = "\n";
    $settings[] = "// drupal-sf-dic configuration\n";
    $settings[] = "include_once DRUPAL_ROOT . '/../vendor/autoload.php';\n";
    $settings[] = "\$conf['kernel.cache_dir'] = dirname(DRUPAL_ROOT) . '/var/cache/';\n";
    $settings[] = "\$conf['kernel.logs_dir'] = dirname(DRUPAL_ROOT) . '/var/log/';\n";
    $settings[] = "\$conf['kernel.symfony_all_the_way'] = true;\n";

    file_put_contents($settingsPath, $settings);

    // Creation of var dir
    if (!file_exists($composerRoot . '/var')) {
      mkdir($composerRoot . '/var', 0755, true);
    }
    $fs->chown($composerRoot . '/var', get_current_user());

    // Creation of log dir
    if (!file_exists($composerRoot . '/var/log')) {
      mkdir($composerRoot . '/var/log', 0755, true);
    }
    $fs->chown($composerRoot . '/var/log', get_current_user());

    // Creation of cache dir
    if (!file_exists($composerRoot . '/cache')) {
      mkdir($composerRoot . '/var/cache', 0755, true);
    }
    $fs->chown($composerRoot . '/var/cache', get_current_user());
  }

  /**
   * Creates & inits a new Profile
   * Asks for new frontend & backend themes for profile and inits them
   *
   * @param Event $event
   * @param string $drupalRoot
   * @param string $composerRoot
   * @return profile name
   */
  private static function createProfile(Event $event, string $drupalRoot, string $composerRoot) {

    // Ask new profile name
    $profileName = basename($composerRoot);
    $profileName = $event->getIO()->ask("\nProfile name (default : '" . $profileName . "'):", $profileName);

    while (!preg_match("/^[a-z_]*$/", $profileName)) {
      $event->getIO()->write("\nIncorrect input : can only contains lowercase letters and underscores");
      $profileName = $event->getIO()->ask("Profile name (default : '" . $profileName . "'):", $profileName);
    }

    // Create repositories
    $profilePath = $drupalRoot . '/profiles/' . $profileName;

    if (!file_exists($profilePath)) {
      mkdir($profilePath);
    }
    if (!file_exists($profilePath . "/js")) {
      mkdir($profilePath . "/js");
    }
    if (!file_exists($profilePath . "/modules")) {
      mkdir($profilePath . "/modules");
    }
    if (!file_exists($profilePath . "/templates")) {
      mkdir($profilePath . "/templates");
    }
    if (!file_exists($profilePath . "/themes")) {
      mkdir($profilePath . "/themes");
    }
    if (!file_exists($profilePath . "/usync")) {
      mkdir($profilePath . "/usync");
    }
    if (!file_exists($profilePath . "/usync-partial")) {
      mkdir($profilePath . "/usync-partial");
    }

    // .info file
    $infoContent = <<<EOT
name = $profileName
description = $profileName
version = 1
core = 7.x

; This is a distribution.
exclusive = true

; Core dependencies.
dependencies[] = dblog
dependencies[] = field
dependencies[] = field_sql_storage
dependencies[] = file
dependencies[] = filter
dependencies[] = image
dependencies[] = list
dependencies[] = locale

; better not to have this always loaded, and better to avoid uninstall of profile
; when removing it
; dependencies[] = migrate
dependencies[] = node
dependencies[] = number
dependencies[] = options
dependencies[] = path
dependencies[] = system
dependencies[] = taxonomy
dependencies[] = text
dependencies[] = user

; Custom modules.
dependencies[] = sf_dic
dependencies[] = usync

usync[] = usync/
EOT;

    file_put_contents($profilePath . "/" . $profileName . ".info", $infoContent);

    // List of themes required in hook_install
    $themes = "['bootbase'";

    // Ask for new frontend theme, if wanted : create & configure it
    $setDefaultTheme = "";
    if ($event->getIO()->askConfirmation("\nDo you want to create a frontend theme [Y,n] ?")) {
      $frontThemes = ScriptHandler::createTheme($event, $profileName, $profilePath . "/themes");

      // Add frontend theme to required themes
      foreach ($frontThemes as $theme) {
        $themes .= ", '" . $theme . "'";
      }

      $setDefaultTheme = "variable_set('theme_default', '" . $frontThemes['theme'] . "');";

    }

    // Ask for new backend theme, if wanted : create & configure it
    $setAdminTheme = "";
    if ($event->getIO()->askConfirmation("\nDo you want to create a backend theme [Y,n] ?")) {
      $backThemes = ScriptHandler::createTheme($event, $profileName, $profilePath . "/themes", true);

      // Add backend theme to required themes
      foreach ($backThemes as $theme) {
        $themes .= ", '" . $theme . "'";
      }

      $setAdminTheme = "variable_set('admin_theme', '" . $backThemes['theme'] . "');";
    }

    $themes .= "]";

    // .module file
    $installContent = <<<EOT
<?php

/**
 * @file
 * Installation procedure.
 *
 * Please keep this file to the bare minimum, almost everything can be
 * installed within modules and should not get in here.
 */

/**
 * Implements hook_install().
 */
function {$profileName}_enable() {

}
EOT;

    file_put_contents($profilePath . "/" . $profileName . ".install", $installContent);

    // .profile file
    $profileContent = <<<EOT
<?php


/**
 * Implements hook_install_tasks().
 */
function {$profileName}_install_tasks(\$install_state) {
  \$task['setTheme'] = [
    'display_name' => st('Setting admin and front theme'),
    'display' => TRUE,
    'type' => 'normal',
    'run' => INSTALL_TASK_RUN_IF_REACHED,
    'function' => 'setTheme',
  ];
  return \$task;
}

/**
 * Set profile themes as admin and default theme
 */
function setTheme() {
  theme_enable($themes);
  $setDefaultTheme
  $setAdminTheme
}
EOT;

    file_put_contents($profilePath . "/" . $profileName . ".profile", $profileContent);


    return $profileName;
  }

  /**
   * Creates & inits a new theme
   *
   * @param Event $event
   * @param string $defaultName
   * @param string $rootPath
   * @param bool $isAdmin
   * @return string[] themes name
   */
  private static function createTheme(Event $event, string $defaultName = 'toto', string $rootPath = '/', bool $isAdmin = false) {
    // Ask for a name
    $themeName = $defaultName . ($isAdmin ? "_admin" : "_default");
    $themeName = $event->getIO()->ask("\nProfile name (default : '" . $themeName . "'):", $themeName);

    while (!preg_match("/^[a-z_]*$/", $themeName)) {
      $event->getIO()->write("\nIncorrect input : can only contains lowercase letters and underscores");
      $themeName = $event->getIO()->ask("Profile name (default : '" . $themeName . "'):", $themeName);
    }

    // If admin thme, ask if is a badm based theme
    $isBadmBased = false;
    if ($isAdmin) {
      $isBadmBased = $event->getIO()->askConfirmation("\nDo you want your admin theme to be based on drupal-badm [Y,n] ?");
    }

    $themePath = $rootPath . "/" . $themeName;

    // Create repositories
    if (!file_exists($themePath)) {
      mkdir($themePath);
    }
    if (!file_exists($themePath . "/dist")) {
      mkdir($themePath . "/dist");
    }
    if (!file_exists($themePath . "/js")) {
      mkdir($themePath . "/js");
    }
    if (!file_exists($themePath . "/less")) {
      mkdir($themePath . "/less");
    }

    // .info file
    $basedTheme = $isBadmBased ? "badm" : "bootbase";
    $descriptionTheme = ($isAdmin ? "An admin" : "A front") . " theme.";

    $infoContent = <<<EOT
name = $themeName
description = $descriptionTheme
package = Core
version = VERSION
core = 7.x

engine = twig

base theme = $basedTheme
EOT;

    file_put_contents($themePath . "/" . $themeName . ".info", $infoContent);

    $themes =[ 'theme' => $themeName];
    if ($isBadmBased) {
      $themes['base']='badm';
    }

    return $themes;
  }
}
