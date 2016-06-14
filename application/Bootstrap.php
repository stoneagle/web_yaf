<?php
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _initConfig() 
    {/*{{{*/
		$config = Yaf_Application::app()->getConfig();
		Yaf_Registry::set('config', $config);	
        Yaf_Dispatcher::getInstance()->autoRender(FALSE);

        //错误展示控制
        if ($config->application->showErrors) {
			ini_set('display_errors',"On");
			ini_set('error_reporting',E_ALL ^ E_DEPRECATED);
        } else {
			error_reporting(E_ALL ^ E_NOTICE);
			ini_set('display_errors','Off');
        }
	}/*}}}*/

    //library以外的autoload导入，例如composer
    public function _initLoader() 
    {/*{{{*/
        Yaf_Loader::import(APP_PATH."/vendor/autoload.php");
    }/*}}}*/

    //调用容器，初始化第三方工具单例
    public function _initThird()
    {/*{{{*/
        //todo,改进，在调用的时候，才初始化加载
        $container = Handler_Container::ins();

        $conf                = Yaf_Registry::get("config");
        $container["config"] = $conf;

        //日志工具
        $conf_log            = $conf->application->log;
        $container["monolog"] = new Monolog\Logger($conf_log->name);
        $container["monolog"]->pushHandler(new Monolog\Handler\StreamHandler($conf_log->error,Monolog\Logger::ERROR));

        //队列-beanstalk工具
        $conf_bean           = $conf->beanstalk;
        $container["beanstalk"] = BeanStalk::open(
                [
                "servers" => array($conf_bean->servers),
                "select" => $conf_bean->select,
                //"connection_timeout" => $config->timeout,
                //"connection_retries" => $config->retries,
                ]
        );
        $container["beanstalk"]->use_tube("presure");

        //redis工具
        $conf_redis          = $conf->redis;
        $container["redis"] = new Predis\Client(
            [
                "host"     => $conf_redis->host,
                "password" => $conf_redis->password,
                "port"     => $conf_redis->port,
                "database" => $conf_redis->database,
            ]
        );

        //也可以使用轻量的Requests
        //request请求工具
        $container["request_config"] = [
            "base_uri" => "",
            "timeout" => 0,
        ];
        $container["request"] = function($c) {
            return new GuzzleHttp\Client($c["request_config"]);
        };

        //diff对比工具
        $container["diff"] = new Common_Diff();

        //分页工具
        $container["page_list"] = [];
        $container["arr_adapter"] = function($c) {
            return new Pagerfanta\Adapter\ArrayAdapter($c["page_list"]);
        };
        $container["page"] = function($c) {
            return new Pagerfanta\Pagerfanta($c["arr_adapter"]);
        };
    }/*}}}*/

    //脚本异常处理方法
    public function _initFatalError()
    {/*{{{*/
        register_shutdown_function(function(){
            $last_error = error_get_last();
            //记录日志
            if($last_error['type'] === E_ERROR) {
                $logMsg = 'errno:'.$last_error['type'] . ' | ' . 'error:'.$last_error['message'] . ' | ' . " file:".$last_error['file'] . ' | ' . "line:".$last_error['line'];
                Handler_Container::ins()["monolog"]->addError($logMsg);
            }
        });
    }/*}}}*/

    //脚本错误级别处理方法
    public function _initSetErrorHandler()
    {/*{{{*/
        $log = Yaf_Registry::get("config")->application->logAll;
        Yaf_Application::app()->getDispatcher()->throwException(FALSE);
        Yaf_Application::app()->getDispatcher()->setErrorHandler(
            function ($errno, $errstr, $errfile, $errline) use ($log) {
                switch ($errno) {
                    case YAF_ERR_NOTFOUND_CONTROLLER:
                    case YAF_ERR_NOTFOUND_MODULE:
                    case YAF_ERR_NOTFOUND_ACTION:
                    case E_RECOVERABLE_ERROR:
                    Handler_Container::ins()["monolog"]->addError('errno:'.$errno . ' | ' . 'error:'.$errstr . ' | ' . " file:".$errfile . ' | ' . "line:".$errline);
                        break;
                    default:
                        if ($log) {
                            Handler_Container::ins()["monolog"]->addError('errno:'.$errno . ' | ' . 'error:'.$errstr . ' | ' . " file:".$errfile . ' | ' . "line:".$errline);
                        }
                        break;
                }
                return true;
            }
        );
    }/*}}}*/

    public function _initSetExceptionHandler()
    {/*{{{*/
        $flag = Yaf_Registry::get("config")->application->exception->handler;
        if ($flag) {
            set_exception_handler(
                function($exception){
                    $exception_conf = array(
                        "Requests_Exception" => ErrorModel::ERR_EXCEPTION_REQUEST,
                        "Yaf_Exception_LoadFailed_Controller" => "yaf框架",
                        "GuzzleHttp\Exception\ConnectException" => ErrorModel::ERR_EXCEPTION_REQUEST,
                    );
                    $name = get_class($exception);
                    if (isset($exception_conf[$name])) {
                        Common_Net::outJson($exception_conf[$name]);
                    } else {
                        echo "<pre>";
                        var_dump($exception);
                        //var_dump(get_class($exception));
                        //var_dump($exception->getMessage());
                        //var_dump($exception->getFile());
                        //var_dump($exception->getLine());
                        echo "</pre>";
                    }
                }
            );
        }
    }/*}}}*/

    //初始化eloquent的ORM
    public function _initDatabaseEloquent() 
    {/*{{{*/
        $db_name = "presure";
        $db = Yaf_Registry::get("config")->storage->$db_name->toArray();
		$capsule = new Illuminate\Database\Capsule\Manager;
		$capsule->addConnection($db);
		// 设置全局静态可访问
		$capsule->setAsGlobal();
		$capsule->bootEloquent();
    }/*}}}*/

    //初始化插件
    public function _initPlugin(Yaf_Dispatcher $dispatcher)
    {/*{{{*/
        //360q的用户登录体系
        $dispatcher->registerPlugin(new LoginPlugin);
    }/*}}}*/

    //初始化视图模板
    public function _initView(Yaf_Dispatcher $dispatcher) 
    {/*{{{*/
        //注册view控制器，例如smarty与firekylin
        $config = Yaf_Registry::get("config");
        $view = new Smarty_Adapter(null,$config->application->smarty);
        Yaf_Dispatcher::getInstance()->setView($view);
    }/*}}}*/

    //路由映射初始化
	public function _initRoute(Yaf_Dispatcher $dispatcher) 
	{/*{{{*/
		$router = $dispatcher->getRouter();
		$route = new Yaf_Route_Simple("m", "c", "a");
		$router->addRoute("myroute", $route);
	}/*}}}*/

    //界面切换与映射初始化
    public function _initSwitch()
    {/*{{{*/
        Yaf_Application::app()->getDispatcher()->disableview();
        Yaf_Application::app()->getDispatcher()->autoRender(false);
        Yaf_Application::app()->getDispatcher()->flushInstantly(false);
    }/*}}}*/

    //session初始化
    public function _initSession()
    {/*{{{*/
		Yaf_Session::getInstance()->start();
    }/*}}}*/
}
