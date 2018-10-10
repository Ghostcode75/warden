<?php

namespace Deeson\WardenBundle\Document;

use Deeson\WardenBundle\Exception\DocumentNotFoundException;
use Deeson\WardenBundle\Managers\ModuleManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(
 *     collection="sites"
 * )
 */
class SiteDocument extends BaseDocument {

  /**
   * @Mongodb\Field(type="string")
   */
  protected $name;

  /**
   * @Mongodb\Field(type="boolean")
   */
  protected $isNew = TRUE;

  /**
   * @Mongodb\Field(type="string")
   */
  protected $url;

  /**
   * @Mongodb\Field(type="hash")
   */
  protected $coreVersion;

  /**
   * @Mongodb\Field(type="string")
   */
  protected $wardenToken;

  /**
   * @Mongodb\Field(type="string")
   */
  protected $wardenEncryptToken;

  /**
   * @Mongodb\Field(type="collection")
   */
  protected $modules;

  /**
   * @Mongodb\Field(type="hash")
   */
  protected $libraries;

  /**
   * @Mongodb\Field(type="string")
   */
  protected $authUser;

  /**
   * @Mongodb\Field(type="string")
   */
  protected $authPass;

  /**
   * @Mongodb\Field(type="boolean")
   */
  protected $hasCriticalIssue;

  /**
   * @Mongodb\Field(type="hash")
   */
  protected $additionalIssues;

  /**
   * @Mongodb\Field(type="string")
   */
  protected $lastSuccessfulRequest;

  /**
   * @return mixed
   */
  public function getName() {
    return (empty($this->name)) ? '[Site Name]' : $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getWardenToken() {
    return $this->wardenToken;
  }

  /**
   * @param string $wardenToken
   */
  public function setWardenToken($wardenToken) {
    $this->wardenToken = $wardenToken;
  }

  /**
   * @return mixed
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @param mixed $url
   */
  public function setUrl($url) {
    $this->url = $url;
  }

  /**
   * @return boolean
   */
  public function getHasCriticalIssue() {
    return $this->hasCriticalIssue;
  }

  /**
   * @param boolean $hasCriticalIssue
   */
  public function setHasCriticalIssue($hasCriticalIssue) {
    $this->hasCriticalIssue = $hasCriticalIssue;
  }

  /**
   * @return mixed
   */
  public function getCoreVersion() {
    return (empty($this->coreVersion['current'])) ? '0' : $this->coreVersion['current'];
  }

  /**
   * @param mixed $coreVersion
   */
  public function setCoreVersion($coreVersion) {
    $majorRelease = ModuleDocument::getMajorVersion($coreVersion);
    if (!isset($this->coreVersion)) {
      $this->coreVersion = array();
    }
    /*$this->coreVersion = array_merge(array(
      'release' => $majorRelease,
      'current' => $coreVersion,
    ));*/
    $this->coreVersion['release'] = $majorRelease;
    $this->coreVersion['current'] = $coreVersion;
  }

  /**
   * @return mixed
   */
  public function getCoreReleaseVersion() {
    return (empty($this->coreVersion['release'])) ? '0' : $this->coreVersion['release'];
  }

  /**
   * @return mixed
   */
  public function getLatestCoreVersion() {
    return (empty($this->coreVersion['latest'])) ? '0' : $this->coreVersion['latest'];
  }

  /**
   * @param mixed $latestVersion
   * @param boolean $isSecurity
   */
  public function setLatestCoreVersion($latestVersion, $isSecurity = FALSE) {
    /*$this->coreVersion += array(
      'latest' => $latestVersion,
      'isSecurity' => $isSecurity,
    );*/
    $this->coreVersion['latest'] = $latestVersion;
    $this->coreVersion['isSecurity'] = $isSecurity;
  }

  /**
   * @return boolean
   */
  public function getIsSecurityCoreVersion() {
    return (empty($this->coreVersion['isSecurity'])) ? FALSE : $this->coreVersion['isSecurity'];
  }

  /**
   * Get the site modules.
   * @todo move this into the DrupalSiteService
   *
   * @return mixed
   */
  public function getModules() {
    return (!empty($this->modules)) ? $this->modules : array();
  }

  /**
   * Set the current modules for the site.
   *
   * @param array $modules
   *   List of modules to add to the site.
   * @param bool $update
   *   If true, update the site module versions while using the existing version
   *   information.
   */
  public function setModules($modules, $update = FALSE) {
    $currentModules = ($update) ? $this->getModules() : array();
    if (!empty($currentModules)) {
      $currentVersions = array();
      foreach ($currentModules as $value) {
        $currentVersions[$value['name']] = $value;
      }
    }

    $moduleList = array();
    foreach ($modules as $name => $version) {
      $module = array(
        'name' => $name,
        'version' => $version['version'],
        /*'version' => array(
          'current' => $version['version'],
          'latest' => '',
          'isSecurity' => 0,
        ),*/
      );

      // Set the current version if there was one.
      if (isset($currentVersions[$name])) {
        if (isset($currentVersions[$name]['latestVersion'])) {
          $module['latestVersion'] = $currentVersions[$name]['latestVersion'];
        }
        if (isset($currentVersions[$name]['isSecurity'])) {
          $module['isSecurity'] = $currentVersions[$name]['isSecurity'];
        }
      }

      $drupalVersion = ModuleDocument::getMajorVersion($module['version']);
      $moduleVersions = $version['latestVersion'][$drupalVersion];

      $module['isUnsupported'] = ModuleDocument::isVersionUnsupported($moduleVersions, $module);
      $moduleList[$name] = $module;
    }
    ksort($moduleList);
    $this->modules = $moduleList;
  }

  /**
   * Get the site third party libraries.
   * @todo move this into the DrupalSiteService
   *
   * @return mixed
   */
  public function getLibraries() {
    return (!empty($this->libraries)) ? $this->libraries : array();
  }

  /**
   * Set the current third party libraries for the site.
   * @todo move this into the DrupalSiteService
   *
   * @param array $libraryData
   *   List of third party library data to add to the site.
   */
  public function setLibraries($libraryData) {
    $libraryList = array();
    foreach ($libraryData as $type => $typeData) {
      foreach ($typeData as $name => $version) {
        $libraryList[$type][] = array(
          'name' => $name,
          'version' => $version,
        );
      }
      ksort($libraryList[$type]);
    }
    ksort($libraryList);
    $this->libraries = $libraryList;
  }

  /**
   * Gets a modules latest version for the site.
   * @todo move this into the DrupalSiteService
   *
   * @param $module
   *
   * @return string
   */
  public function getModuleLatestVersion($module) {
    return (!isset($module['latestVersion'])) ? '' : $module['latestVersion'];
  }

  /**
   * Returns if the provided module has a security release.
   * @todo move this into the DrupalSiteService
   *
   * @param array $module
   *
   * @return boolean
   */
  public function getModuleIsSecurity($module) {
    if ($this->getModuleIsDevRelease($module)) {
      return FALSE;
    }
    return (!isset($module['isSecurity'])) ? FALSE : $module['isSecurity'];
  }

  /**
   * Returns if the provided module is an unsupported release.
   * @todo move this into the DrupalSiteService
   *
   * @param array $module
   *
   * @return boolean
   */
  public function isModuleSupported($module) {
    return (!isset($module['isUnsupported'])) ? FALSE : $module['isUnsupported'];
  }

  /**
   * Determines if the module version is a dev release or not.
   *
   * @param array $module
   *
   * @return bool
   */
  public function getModuleIsDevRelease($module) {
    return ModuleDocument::isDevRelease($module['version']);
  }

  /**
   * Sets the latest versions of each of the modules for the site.
   * @todo move this into the DrupalSiteService
   *
   * @param $moduleLatestVersions
   */
  public function setModulesLatestVersion($moduleLatestVersions) {
    $siteModuleList = $this->getModules();
    foreach ($siteModuleList as $key => $module) {
      if (!isset($moduleLatestVersions[$module['name']])) {
        continue;
      }
      $moduleVersions = $moduleLatestVersions[$module['name']];

      $versionType = ModuleDocument::MODULE_VERSION_TYPE_RECOMMENDED;
      if (isset($moduleVersions[ModuleDocument::MODULE_VERSION_TYPE_OTHER])) {
        $latestVersion = ModuleDocument::getRelevantLatestVersion($module['version'], $moduleVersions[ModuleDocument::MODULE_VERSION_TYPE_OTHER]['version']);
        if ($latestVersion) {
          $versionType = ModuleDocument::MODULE_VERSION_TYPE_OTHER;
        }
      }

      $siteModuleList[$key]['isUnsupported'] = ModuleDocument::isVersionUnsupported($moduleVersions, $module);

      if (!isset($moduleVersions[$versionType])) {
        print "ERROR : module (" . $module['name'] .") version is not valid: " . print_r(array($versionType, $moduleVersions), TRUE);
      }
      else {
        /*$siteModuleList[$key] += array(
          'latestVersion' => $moduleVersions[$versionType]['version'],
          'isSecurity' => $moduleVersions[$versionType]['isSecurity'],
        );*/
        $siteModuleList[$key]['latestVersion'] = $moduleVersions[$versionType]['version'];
        $siteModuleList[$key]['isSecurity'] = $moduleVersions[$versionType]['isSecurity'];
      }
    }
    $this->modules = $siteModuleList;
  }

  /**
   * Updates a specific module on a site with version and/or security info.
   * @todo move this into the DrupalSiteService
   *
   * @param string $moduleName
   *   The module project name.
   * @param array $moduleData
   *   An array of the module data, keyed with version and isSecurity.
   */
  public function updateModule($moduleName, $moduleData) {
    $siteModuleList = $this->getModules();
    foreach ($siteModuleList as $key => $module) {
      if ($moduleName != $module['name']) {
        continue;
      }

      if (isset($moduleData['version'])) {
        $siteModuleList[$key]['latestVersion'] = $moduleData['version'];
      }
      if (isset($moduleData['isSecurity'])) {
        $siteModuleList[$key]['isSecurity'] = $moduleData['isSecurity'];
      }
    }
    $this->modules = $siteModuleList;
  }

  /**
   * Updates the modules list for the provided site.
   *
   * This updates the list of modules that this site has with the module documents.
   * @todo move this into the DrupalSiteService
   *
   * @param ModuleManager $moduleManager
   *
   * @throws DocumentNotFoundException
   */
  public function updateModules(ModuleManager $moduleManager) {
    foreach ($this->getModules() as $siteModule) {
      /** @var ModuleDocument $module */
      $module = $moduleManager->findByProjectName($siteModule['name']);
      if (empty($module)) {
        print "[updateModules] Error : Unable to find module [{$siteModule['name']}]\n";
        continue;
      }
      $moduleSites = $module->getSites();

      // Check if the site URL is already in the list for this module.
      $alreadyExists = FALSE;
      if (is_array($moduleSites)) {
        foreach ($moduleSites as $moduleSite) {
          if ($moduleSite['id'] == $this->getId()) {
            $alreadyExists = TRUE;
            break;
          }
        }
      }

      if ($alreadyExists) {
        $module->updateSite($this->getId(), $siteModule['version']);
      }
      else {
        $module->addSite($this->getId(), $this->getName(), $this->getUrl(), $siteModule['version']);
      }
      $moduleManager->updateDocument();
    }
  }

  /**
   * @return mixed
   */
  public function getIsNew() {
    return $this->isNew;
  }

  /**
   * @param boolean $isNew
   */
  public function setIsNew($isNew) {
    $this->isNew = $isNew;
  }

  /**
   * Get a list of site modules that require updating.
   * @todo move this into the DrupalSiteService
   *
   * @return array
   */
  public function getModulesRequiringUpdates() {
    $siteModuleList = $this->getModules();
    $modulesList = array();
    foreach ($siteModuleList as $module) {
      if (!isset($module['latestVersion'])) {
        continue;
      }
      if (is_null($module['version'])) {
        continue;
      }
      if (ModuleDocument::isLatestVersion($module)) {
        continue;
      }

      $severity = 1;
      if (isset($module['isSecurity'])) {
        $severity = !$module['isSecurity'];
      }

      $modulesList[$severity][] = $module;
    }
    ksort($modulesList);

    $modulesForUpdating = array();
    foreach ($modulesList as $severity => $moduleSeverity) {
      foreach ($moduleSeverity as $module) {
        $modulesForUpdating[$severity.$module['name']] = $module;
      }
    }
    ksort($modulesForUpdating);

    return $modulesForUpdating;
  }

  /**
   * @return mixed
   */
  public function getAuthPass() {
    return !empty($this->authPass) ? $this->authPass : NULL;
  }

  /**
   * @param mixed $authPass
   */
  public function setAuthPass($authPass) {
    $this->authPass = $authPass;
  }

  /**
   * @return mixed
   */
  public function getAuthUser() {
    return !empty($this->authUser) ? $this->authUser : NULL;
  }

  /**
   * @param mixed $authUser
   */
  public function setAuthUser($authUser) {
    $this->authUser = $authUser;
  }

  /**
   * @return mixed
   */
  public function getAdditionalIssues() {
    return !empty($this->additionalIssues) ? $this->additionalIssues : array();
  }

  /**
   * @param mixed $additionalIssues
   */
  public function setAdditionalIssues($additionalIssues) {
    // @todo format of these issues??
    $this->additionalIssues = array_merge($this->getAdditionalIssues(), $additionalIssues);
  }

  /**
   * Compare the current core version with the latest core version.
   *
   * @return bool
   *   TRUE if the current core version is less than the latest core version.
   */
  public function hasOlderCoreVersion() {
    return $this->getCoreVersion() < $this->getLatestCoreVersion();
  }

  /**
   * @return mixed
   */
  public function getLastSuccessfulRequest() {
    return !empty($this->lastSuccessfulRequest) ? $this->lastSuccessfulRequest : 'No request completed yet';
  }

  /**
   * Set the last successful datetime.
   */
  public function setLastSuccessfulRequest() {
    $this->lastSuccessfulRequest = date('d/m/Y H:i:s');
  }

  /**
   * Checks if the site has been updated within the last 24 hours.
   *
   * @return bool
   *   True if the site not been updated recently.
   */
  public function hasNotUpdatedRecently() {
    if (empty($this->lastSuccessfulRequest)) {
      return false;
    }

    $timestamp = \DateTime::createFromFormat('d/m/Y H:i:s', $this->lastSuccessfulRequest)->getTimestamp();
    $diff = time() - $timestamp;
    return $diff > (60 * 60 * 24);
  }

}
