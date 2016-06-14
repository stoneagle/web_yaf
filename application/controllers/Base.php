<?php
class BaseController extends Yaf_Controller_Abstract
{
    //todo,获取参数需要结合过滤插件进行修改
    protected function getGetParam($key , $default = "")
    {/*{{{*/
        $value = trim($this->getRequest()->getGet($key , $default));
        if(empty($value))
        {
            return $default;
        }
        else
        {
            return $value;
        }
    }/*}}}*/

    protected function getPostParam($key , $default = "")
    {/*{{{*/
        $value = trim($this->getRequest()->getPost($key , $default));
        if(empty($value))
        {
            return $default;
        }
        else
        {
            return $value;
        }
    }/*}}}*/

    protected function getRequestParam($key , $default = "")
    {/*{{{*/
        return trim($this->getRequest()->getRequest($key , $default));
    }/*}}}*/

    protected function getRestParam($key , $default = "")
    {/*{{{*/
        parse_str(file_get_contents('php://input'), $data);
        $data = array_merge($_GET, $_POST, $data);
        $ret = $data[$key] ? $data[$key] : $default;
        return trim($ret);
    }/*}}}*/

    protected function _getParamsByConfArr($condArr,$methodType = 'request')
    {/*{{{*/
        switch ($methodType) {
            case 'request':
                $method = 'getRequestParam';
                break;
            case 'get' :
                $method = 'getGetParam';
                break;
            case 'post' :
                $method = 'getPostParam';
                break;
            case 'rest' :
                $method = 'getRestParam';
                break;
            default :
                return $result = false;
        }
        $ret = array();
        foreach ($condArr as $param_name => $term) {
            $conf_arr = explode('|',$term);
            $type = $conf_arr[0];
            $default = isset($conf_arr[1]) ? $conf_arr[1] : '';
            switch ($type) {
                case 'int' :
                    $ret[$param_name] = intval($this->$method($param_name,$default));
                    break;
                case 'str' :
                    $ret[$param_name] = htmlspecialchars($this->$method($param_name,$default));
                    break;
                case 'arr' :
                    $param = $this->$method($param_name);
                    if (is_array($param)) {
                        foreach ($param as $index => $term) {
                            $param[$index] = htmlspecialchars($term);
                        }
                        $ret[$param_name] = $param;
                    }
                    break;
                default :
                    continue;
            }
        }
        return $ret;
    }/*}}}*/

    //清除空数据
    protected function _removeNull(& $params_array,$filter_array = [])
    {/*{{{*/
         foreach ($params_array as $index => $term) {
            if (empty($term) && !in_array($index,$filter_array)) {
                unset($params_array[$index]); 
            }         
         }
    }/*}}}*/

    //返回数据
    protected function response($errno, $data = [], $clear_response = false)
    {/*{{{*/
        // header('Content-type:text/json');
        if (empty($data)) {
            $data = ErrorModel::$err_info[$errno];
        }
        $this->setResponse($errno, $data, $clear_response);
        $this->getResponse()->response();
        exit ();
    } /*}}}*/

    protected function setResponse($errno, $data = [], $clear_response = false)
    { /*{{{*/
        $err_msg = ErrorModel::getMsg($errno);
        $response = [
            'code' => $errno,
            'msg'  => $err_msg,
            'data' => $data
        ];
        if ($clear_response) {
            $this->getResponse()->clearBody();
        }
        $this->getResponse()->appendBody(json_encode($response, JSON_UNESCAPED_UNICODE));
        return false;
    } /*}}}*/

    protected function alertForward($msg,$url)
    {/*{{{*/
        echo "<script>alert('{$msg}');document.location.href='$url'</script>";
        exit;
    }/*}}}*/

    protected function alert($msg)
    {/*{{{*/
        echo "<script>alert('{$msg}');</script>";
        return false;
    }/*}}}*/

    protected function makePage($model_name)
    {/*{{{*/
        /*
         * @todo,如果直接将page对象传给页面层，存在耦合
         */
        $page_num   = isset($_GET["page_no"]) ? intval($_GET["page_no"]) : 1;
        $page_size  = isset($_GET["page_size"]) ? intval($_GET["page_size"]) : 10;

        $count = $model_name::count();
        $start_page = $page_num >= 5 ? ($page_num - 5) * $page_size : 0;
        $list = $model_name::skip($start_page)->take(10 * $page_num)->get()->toArray();

        Handler_Container::ins()["page_list"] = $list;
        $pagination = Handler_Container::ins()["page"];
        $pagination->setMaxPerPage($page_size)->setCurrentPage($page_num);

        return $pagination;
    }/*}}}*/

    protected function getRestfulAct($obj)
    {/*{{{*/
        $action = $_REQUEST["a"];
        //rest兼容，将put和delete映射至post
        $requestMethod = isset($_REQUEST["rest_method"]) ? strtolower($_REQUEST["rest_method"]) : strtolower($_SERVER["REQUEST_METHOD"]);
        $restAction = $action.ucwords($requestMethod);
        $controllerName = ucwords(strtolower($_REQUEST["c"]))."Controller";
        if (method_exists($controllerName,$restAction)) {
            $obj->$restAction();
        } else {
            $this->setResponse(ErrorModel::ERR_REST_ACTION_ERROR);
        }
    }/*}}}*/

    protected function getEnvHost()
    {/*{{{*/
        return Yaf_Registry::get("config")->application->host;
    }/*}}}*/
}
