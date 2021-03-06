<?php
/**
 * Created by JetBrains PhpStorm.
 * User: degola
 * Date: 24.02.13
 * Time: 16:08
 * To change this template use File | Settings | File Templates.
 */

namespace slc\MVC;

class Router_Driver_Dummy extends Router_Driver {
	protected function parseRoute() {
		$this->StartRoute = $this->getQueryString();
		if($result = $this->findControllerAndViewByRouteString($this->getQueryString())) {
			$this->setController($result->FilePath, $result->Class);
			if(isset($result->AdditionalViewParameters)) {
				$this->setViewArguments($result->AdditionalViewParameters);
			}
			$this->setView((object)array(
				'View' => $result->View,
				'Path' => $result->ViewPath
			));
			return true;
		}
		return false;
	}

	public function link($link, array $arguments = null, $includeDomain = false) {
		// use default router driver for link generation in dummy router
		return Router_Driver::Factory(null, null)->link($link, $arguments, $includeDomain);
	}

}

?>