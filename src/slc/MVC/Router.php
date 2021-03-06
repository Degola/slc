<?php
/**
 * Created by JetBrains PhpStorm.
 * User: degola
 * Date: 24.02.13
 * Time: 16:08
 * To change this template use File | Settings | File Templates.
 */

namespace slc\MVC;

class Router {
	static private $__OWN_OBJECT = array();
	private $Hooks = array(
		'onBeforeExecute' => array(),
		'onBeforeLink' => array()
	);

	/**
	 * @return Router
	 */
	public static function Factory() {
		$cls = get_called_class();
		if(!isset(static::$__OWN_OBJECT[$cls]))
			static::$__OWN_OBJECT[$cls] = new $cls();
		return static::$__OWN_OBJECT[$cls];
	}
	protected function validateViewAccess(Router_Driver $Driver, Application_Controller $Controller, $View) {
		$ProtectedViews = array(
			'Render',
			'MergeAssignments',
			'__hasAccess'
		);
		if(!preg_match('/^([a-z0-9\_]{1,})$/i', $View->View))
			throw new Router_Exception('ACCESS_DENIED_INVALID_VIEW', array('View' => $View->View, 'Controller' => get_class($Controller)));

		if(substr($View->View, 2) == '__')
			throw new Router_Exception('ACCESS_DENIED_RESERVED_NAME', array('View' => $View->View, 'Controller' => get_class($Controller)));

		if(in_array($View->View, $ProtectedViews) || (method_exists($Controller, $View->View) && !is_callable(array($Controller, $View->View))))
			throw new Router_Exception('ACCESS_DENIED', array('View' => $View->View, 'Controller' => get_class($Controller)));
			
		if(method_exists($Controller, '__hasAccess') && $Controller->__hasAccess($Driver->getViewArguments()) !== true)
			throw new Router_Exception('ACCESS_DENIED_CONTROLLER_HOOK', array('View' => $View->View, 'Controller' => get_class($Controller)));

		return true;
	}
	public final function addHook($type, $id, $func) {
		$this->Hooks[$type][$id] = $func;
	}
	public final function deleteHook($type, $id) {
		unset($this->Hooks[$type][$id]);
	}
	public final function getHooks($type) {
		if(isset($this->Hooks[$type]) && is_array($this->Hooks[$type])) {
			return $this->Hooks[$type];
		}
		return array();
	}
	public function Execute(Router_Driver $Driver, $PreviousController = null) {
		$controller = $Driver->getControllerInstance();
		$View = $Driver->getView();
		$ViewArguments = $Driver->getViewArguments();
		
		$this->validateViewAccess($Driver, $controller, $View);

		foreach($this->getHooks('onBeforeExecute') AS $hookId => $hookFunction) {
			$hookFunction($Driver);
		}

		if(
			$Driver instanceof Router_Driver_Redirect &&
			is_object($PreviousController) &&
			$PreviousController instanceof Application_Controller
        ) {
			$controller->MergeAssignments($PreviousController);
		}
		$mView = $View->View;
		if($controller->useHTTPRequestMethodInViewName() && isset($_SERVER['REQUEST_METHOD']))
			$mView = strtoupper($_SERVER['REQUEST_METHOD']).'_'.$mView;

		if(method_exists($controller, '__before')) {
			$beforeResult = $controller->__before($Driver->getViewArguments());
			if(is_subclass_of($beforeResult, 'slc\\MVC\\Router_Driver')) {
				return $this->Execute($beforeResult, $controller);
			}
			unset($beforeResult);
		}
		if(method_exists($controller, $mView)) {

			$result = $controller->$mView($Driver->getViewArguments());

			if(method_exists($controller, '__after'))
				$controller->__after($Driver->getViewArguments());

			if(is_subclass_of($result, 'slc\\MVC\\Router_Driver')) {
				return $this->Execute($result, $controller);
			}
		}
		return $controller;
	}
}

class Router_Exception extends Application_Exception {
	const ACCESS_DENIED = 'access to view was denied';
	const ACCESS_DENIED_CONTROLLER_HOOK = 'access to view was denied by controller';
	const ACCESS_DENIED_RESERVED_NAME = 'access to view was denied, reserved name';
	const ACCESS_DENIED_INVALID_VIEW = 'access to view was denied, view doesn\'t match naming convention';
}

?>