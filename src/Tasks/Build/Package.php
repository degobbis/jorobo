<?php
/**
* @package     JoRobo
*
* @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
*/

namespace Joomla\Jorobo\Tasks\Build;

use Robo\Result;
use Robo\Task\BaseTask;
use Robo\Contract\TaskInterface;
use Robo\Exception\TaskException;

use Joomla\Jorobo\Tasks\JTask;

/**
 * Class Package
 *
 * @package  Joomla\Jorobo\Tasks\Build
 */
class Package extends Base implements TaskInterface
{
	use \Robo\Task\Development\loadTasks;
	use \Robo\Common\TaskIO;
	use buildTasks;

	/**
	 * Initialize Build Task
	 *
	 * @param   String  $params  The target directory
	 */
	public function __construct($params)
	{
		parent::__construct();

		// Reset files - > new package
		$this->resetFiles();
	}

	/**
	 * Build the package
	 *
	 * @return  bool
	 */
	public function run()
	{
		$this->say('Building package');

		// Build language files for the package
		$language = $this->buildLanguage("pkg_" . $this->getExtensionName());
		$language->run();

		// Update XML and script.php
		$this->createInstaller();

		return true;
	}

	/**
	 * Generate the installer xml file for the package
	 *
	 * @return  void
	 */
	private function createInstaller()
	{
		$this->say("Creating package installer");

		// Copy XML and script.php
		$sourceFolder = $this->getSourceFolder() . "/administrator/manifests/packages";
		$targetFolder = $this->getBuildFolder() . "/pkg_" . $this->getExtensionName();
		$xmlFile     = $targetFolder . "/pkg_" . $this->getExtensionName() . ".xml";
		$scriptFile  = $targetFolder . "/script.php";

		$this->_copy($sourceFolder . "/pkg_" . $this->getExtensionName() . ".xml", $xmlFile);

		// Version & Date Replace
		$this->taskReplaceInFile($xmlFile)
			->from(array('##DATE##', '##YEAR##', '##VERSION##'))
			->to(array($this->getDate(), date('Y'), $this->getConfig()->version))
			->run();

		if (is_file($sourceFolder . "/" . $this->getExtensionName() . "/script.php"))
		{
			$this->_copy($sourceFolder . "/" . $this->getExtensionName() . "/script.php", $scriptFile);

			$this->taskReplaceInFile($scriptFile)
				->from(array('##DATE##', '##YEAR##', '##VERSION##'))
				->to(array($this->getDate(), date('Y'), $this->getConfig()->version))
				->run();
		}

		// Language files
		$f = $this->generateLanguageFileList($this->getFiles('frontendLanguage'));

		$this->taskReplaceInFile($xmlFile)
			->from('##LANGUAGE_FILES##')
			->to($f)
			->run();
	}
}
