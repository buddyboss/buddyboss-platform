<?php
/*
    "WordPress Plugin Template" Copyright (C) 2015 Michael Simpson  (email : michael.d.simpson@gmail.com)

    This file is part of WordPress Plugin Template for WordPress.

    WordPress Plugin Template is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WordPress Plugin Template is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Contact Form to Database Extension.
    If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/

include_once('WPPerformanceTester_OptionsManager.php');

class WPPerformanceTester_InstallIndicator extends WPPerformanceTester_OptionsManager {

    const optionInstalled = '_installed';
    const optionVersion = '_version';

    /**
     * @return bool indicating if the plugin is installed already
     */
    public function isInstalled() {
        return $this->getOption(self::optionInstalled) == true;
    }

    /**
     * Note in DB that the plugin is installed
     * @return null
     */
    protected function markAsInstalled() {
        return $this->updateOption(self::optionInstalled, true);
    }

    /**
     * Note in DB that the plugin is uninstalled
     * @return bool returned form delete_option.
     * true implies the plugin was installed at the time of this call,
     * false implies it was not.
     */
    protected function markAsUnInstalled() {
        return $this->deleteOption(self::optionInstalled);
    }

    /**
     * Set a version string in the options. This is useful if you install upgrade and
     * need to check if an older version was installed to see if you need to do certain
     * upgrade housekeeping (e.g. changes to DB schema).
     * @return null
     */
    protected function getVersionSaved() {
        return $this->getOption(self::optionVersion);
    }

    /**
     * Set a version string in the options.
     * need to check if
     * @param  $version string best practice: use a dot-delimited string like '1.2.3' so version strings can be easily
     * compared using version_compare (http://php.net/manual/en/function.version-compare.php)
     * @return null
     */
    protected function setVersionSaved($version) {
        return $this->updateOption(self::optionVersion, $version);
    }

    /**
     * @return string name of the main plugin file that has the header section with
     * "Plugin Name", "Version", "Description", "Text Domain", etc.
     */
    protected function getMainPluginFileName() {
        return basename(dirname(__FILE__)) . 'php';
    }

    /**
     * Get a value for input key in the header section of main plugin file.
     * E.g. "Plugin Name", "Version", "Description", "Text Domain", etc.
     * @param $key string plugin header key
     * @return string if found, otherwise null
     */
    public function getPluginHeaderValue($key) {
        // Read the string from the comment header of the main plugin file
        $data = file_get_contents($this->getPluginDir() . DIRECTORY_SEPARATOR . $this->getMainPluginFileName());
        $match = array();
        preg_match('/' . $key . ':\s*(\S+)/', $data, $match);
        if (count($match) >= 1) {
            return $match[1];
        }
        return null;
    }

    /**
     * If your subclass of this class lives in a different directory,
     * override this method with the exact same code. Since __FILE__ will
     * be different, you will then get the right dir returned.
     * @return string
     */
    protected function getPluginDir() {
        return dirname(__FILE__);
    }

    /**
     * Version of this code.
     * Best practice: define version strings to be easily compared using version_compare()
     * (http://php.net/manual/en/function.version-compare.php)
     * NOTE: You should manually make this match the SVN tag for your main plugin file 'Version' release and 'Stable tag' in readme.txt
     * @return string
     */
    public function getVersion() {
        return $this->getPluginHeaderValue('Version');
    }


    /**
     * Useful when checking for upgrades, can tell if the currently installed version is earlier than the
     * newly installed code. This case indicates that an upgrade has been installed and this is the first time it
     * has been activated, so any upgrade actions should be taken.
     * @return bool true if the version saved in the options is earlier than the version declared in getVersion().
     * true indicates that new code is installed and this is the first time it is activated, so upgrade actions
     * should be taken. Assumes that version string comparable by version_compare, examples: '1', '1.1', '1.1.1', '2.0', etc.
     */
    public function isInstalledCodeAnUpgrade() {
        return $this->isSavedVersionLessThan($this->getVersion());
    }

    /**
     * Used to see if the installed code is an earlier version than the input version
     * @param  $aVersion string
     * @return bool true if the saved version is earlier (by natural order) than the input version
     */
    public function isSavedVersionLessThan($aVersion) {
        return $this->isVersionLessThan($this->getVersionSaved(), $aVersion);
    }

    /**
     * Used to see if the installed code is the same or earlier than the input version.
     * Useful when checking for an upgrade. If you haven't specified the number of the newer version yet,
     * but the last version (installed) was 2.3 (for example) you could check if
     * For example, $this->isSavedVersionLessThanEqual('2.3') == true indicates that the saved version is not upgraded
     * past 2.3 yet and therefore you would perform some appropriate upgrade action.
     * @param  $aVersion string
     * @return bool true if the saved version is earlier (by natural order) than the input version
     */
    public function isSavedVersionLessThanEqual($aVersion) {
        return $this->isVersionLessThanEqual($this->getVersionSaved(), $aVersion);
    }

    /**
     * @param  $version1 string a version string such as '1', '1.1', '1.1.1', '2.0', etc.
     * @param  $version2 string a version string such as '1', '1.1', '1.1.1', '2.0', etc.
     * @return bool true if version_compare of $versions1 and $version2 shows $version1 as the same or earlier
     */
    public function isVersionLessThanEqual($version1, $version2) {
        return (version_compare($version1, $version2) <= 0);
    }

    /**
     * @param  $version1 string a version string such as '1', '1.1', '1.1.1', '2.0', etc.
     * @param  $version2 string a version string such as '1', '1.1', '1.1.1', '2.0', etc.
     * @return bool true if version_compare of $versions1 and $version2 shows $version1 as earlier
     */
    public function isVersionLessThan($version1, $version2) {
        return (version_compare($version1, $version2) < 0);
    }

    /**
     * Record the installed version to options.
     * This helps track was version is installed so when an upgrade is installed, it should call this when finished
     * upgrading to record the new current version
     * @return void
     */
    protected function saveInstalledVersion() {
        $this->setVersionSaved($this->getVersion());
    }


}
