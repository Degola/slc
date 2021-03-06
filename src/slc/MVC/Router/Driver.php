<?php
/**
 * Created by JetBrains PhpStorm.
 * User: degola
 * Date: 24.02.13
 * Time: 16:08
 * To change this template use File | Settings | File Templates.
 */

namespace slc\MVC;

class Router_Driver {
	protected $QueryString = null;
	protected $StartRoute = null;
	protected $CurrentRoute = null;
	protected $Controller = null;
	protected $View = null;
	protected $ViewArguments = null;
	protected $Namespace = null;

	public function __construct($QueryString, $Namespace = null) {
		$this->QueryString = $QueryString;
		if (is_null($Namespace)) {
			$this->Namespace = Base::Factory()->getConfig('Application', 'Namespace');
		} else {
			$this->Namespace = $Namespace;
		}
		if(!is_null($QueryString) && $this->parseRoute() === false)
			throw new Router_Driver_Exception('INVALID_ROUTE', array('Driver' => get_called_class(), 'QueryString' => $this->QueryString, 'StartRoute' => $this->StartRoute));
	}
	public static function Factory($QueryString, $Driver = null, $namespace = null) {
		if(is_null($Driver) && get_called_class() == 'slc\MVC\Router_Driver') {
			$Driver = Base::Factory()->getConfig('Framework', 'DefaultRouterDriver');
		} elseif(is_null($Driver)) {
            $Driver = get_called_class();
        }

		if(is_null($Driver))
			throw new Router_Driver_Exception('INVALID_DRIVER', array('Driver' => $Driver, 'QueryString' => $QueryString));

		return new $Driver($QueryString, $namespace);
	}
	public function Execute(Router_Driver $Driver) {
		$Controller = $Driver->getControllerClass();
	}
	protected function parseRoute() {
		throw new Router_Driver_Exception(Router_Driver_Exception::NEED_IMPLEMENTATION, array('Driver' => get_called_class(), 'QueryString' => $this->QueryString));
	}
	protected function getQueryString() {
		return $this->QueryString;
	}
	protected function setController($filepath, $controller) {
		$this->Controller = (object)array(
			'Class' => $controller,
			'FilePath' => $filepath
		);
	}
	protected function setView($view) {
		$this->View = $view;
	}
	protected function setViewArguments($arguments) {
		$this->ViewArguments = (object)$arguments;
	}
	public function getView() {
		return $this->View;
	}
	public function getViewArguments() {
		return $this->ViewArguments;
	}
	public function getCurrentRoute() {
		return $this->CurrentRoute;
	}
	protected function findControllerAndViewByRouteString($string, $prefix = 'Controller') {

		$stringArray = explode('::', $string);
		$fnPrefix = Base::Factory()->getConfig('Paths', $prefix);
		$fnAppendix = Base::Factory()->getConfig('FileExtensions', $prefix);
		$lastElement = null;
		$remainingElements = array();

		if(substr($fnPrefix, -1) != DIRECTORY_SEPARATOR) $fnPrefix .= DIRECTORY_SEPARATOR;

		while(sizeof($stringArray) > 0) {
			$class = $this->Namespace.'\\'.$prefix.'\\'.implode('_', $stringArray);
			$fn = $fnPrefix.implode(DIRECTORY_SEPARATOR, $stringArray).$fnAppendix;

			if(file_exists($fn)) {
				if($lastElement) {
					array_pop($remainingElements);
				}
				rsort($remainingElements);
				$result = (object)array(
					'Class' => $class,
					'ViewPath' => implode('::', $stringArray),
					'FilePath' => $fn,
					'AdditionalViewParameters' => $remainingElements,
					'View' => (
						$lastElement?$lastElement:(
							Base::Factory()->getConfig('Application', 'DefaultView')?Base::Factory()->getConfig('Application', 'DefaultView'):'DefaultView'
						)
					)
				);
				$this->CurrentRoute = $result->ViewPath.'::'.$result->View;
				return $result;
			} else {
				$lastElement = array_pop($stringArray);
				$remainingElements[] = $lastElement;
			}
		}
		return null;
	}

	/**
	 * returns controller instance for given route
	 *
	 * @return null|Application_Controller
	 * @throws Router_Driver_Exception
	 */
	public function getControllerInstance() {
		if(is_a($this->Controller, 'stdClass')) {
			$cls = $this->Controller->Class;

			require_once $this->Controller->FilePath;
			if(class_exists($cls, true)) {
				$this->Controller = new $cls($this);
				if(!($this->Controller instanceof Application_Controller)) {
					throw new Router_Driver_Exception(Router_Driver_Exception::INVALID_CONTROLLER_INSTANCE, array(
						'Driver' => get_called_class(),
						'QueryString' => $this->QueryString,
						'Controller' => $this->Controller,
						'View' => $this->View
					));
				}
			} else {
				throw new Router_Driver_Exception(Router_Driver_Exception::INVALID_CONTROLLER, array(
					'Driver' => get_called_class(),
					'QueryString' => $this->QueryString,
					'Controller' => $this->Controller,
					'View' => $this->View
				));
			}
		}
		return $this->Controller;
	}
}

/**
 * Class Router_Driver_Exception
 *
 * @package slc\MVC
 */
class Router_Driver_Exception extends Application_Exception {
	const INVALID_DRIVER = 'invalid router driver, check default driver configuration';
	const NEED_IMPLEMENTATION = 'router driver needs parseRoute() implementation';
	const INVALID_ROUTE = 'invalid route, check url';
	const INVALID_CONTROLLER = 'invalid controller, file exists but has invalid class';
	const INVALID_CONTROLLER_INSTANCE = 'invalid controller, controller is not inheriting Application_Controller';
}

?>