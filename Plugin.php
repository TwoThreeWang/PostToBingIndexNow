<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 当发布文章时,自动将文章提交到 Bing 站长平台。使用前请确定你的机器支持curl。
 * 
 * @package PostToBingIndexNow
 * @author 王图思睿
 * @version 1.0
 * @link https://www.wangtwothree.com
 */
 
class PostToBingIndexNow_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('PostToBingIndexNow_Plugin', 'justdoit');
		Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('PostToBingIndexNow_Plugin', 'justdoit');
		return _t('欢迎使用！！第一次使用请查看<a href="https://blog.wangtwothree.com/code/208.html">使用方法</a>');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        
		$debug = new Typecho_Widget_Helper_Form_Element_Text('debug', null, '', _t('<h2><a href="https://blog.wangtwothree.com/code/208.html" target="_blank">使用方法</a> | <a href="https://github.com/TwoThreeWang/PostToBingIndexNow" target="_blank">查看Github</a></h2><br />是否启用日志'), '0或空不开，其它开');
		$form->addInput($debug);
		$key = new Typecho_Widget_Helper_Form_Element_Text('key', null, '', _t('Bing IndexNow key'), '申请地址：https://www.bing.com/webmasters/indexnow');
		$form->addInput($key);
    }
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
	public static function justdoit($contents, $class)
    {
		//如果文章属性为隐藏加入创建时间和修改时间不一致则返回不执行
		if( 'publish' != $contents['visibility']){
            return;
        }
		//必填项如果没填的话直接停止
		if( is_null(Typecho_Widget::widget('Widget_Options')->plugin('PostToBingIndexNow')->key)){
            return;
        }
		//发布文章
		try {
		    post_to_bing_index_now($contents,$class);
		} catch (Exception $e) {
            logInfo($e);
            exit();
        }
    } 
}
/*****************************************/
function post_to_bing_index_now($content,$classa) {
    
    require 'EasyHttp.php';
    require 'EasyHttp/Curl.php';
    require 'EasyHttp/Cookie.php';
    require 'EasyHttp/Encoding.php';
    require 'EasyHttp/Fsockopen.php';
    require 'EasyHttp/Proxy.php';
    require 'EasyHttp/Streams.php';
    
    $request = new EasyHttp();
    $key = Typecho_Widget::widget('Widget_Options')->plugin('PostToBingIndexNow')->key;          //key
    $api_url = 'https://www.bing.com/indexnow';
    $arr=parse_url($classa->permalink);
    $key_url=$arr['scheme'].'://'.$arr['host'].'/'.$key.'.txt';
    $body = json_encode(array(
        'host'   => $arr['host'],         //头条的标题
        'key' => $key,    //头条的正文
        'keyLocation'   => $key_url,                 //头条的封面
        'urlList' => [$classa->permalink]
    ));
    logInfo($body);
    $headers = 'Content-Type:application/json; charset=utf-8';  
    $result = $request->post($api_url, array('body' => $body,'headers' => $headers)); 
    logInfo(json_encode($result['response']));
}
//记录日志
function logInfo($msg)
{
    //日志记录是否启用
    if(Typecho_Widget::widget('Widget_Options')->plugin('PostToBingIndexNow')->debug) 
    {
        $logSwitch = 1;
        
    }else{
        $logSwitch  = 0;
        
    }              // 日志开关：1表示打开，0表示关闭
    $logFile    = 'temp_log/push_bing.log'; // 日志路径           
    if ($logSwitch == 0 ) return;
    date_default_timezone_set('Asia/Shanghai');
    file_put_contents($logFile, date('[Y-m-d H:i:s]: ') . $msg . PHP_EOL, FILE_APPEND);
    return $msg;
}
//读取日志
function readlog(){
    $file = "temp_log/push_bing.log";
    if(file_exists($file) && Typecho_Widget::widget('Widget_Options')->plugin('PostToBingIndexNow')->debug){
        $file = fopen($file, "r") or exit("Unable to open file!");
        //Output a line of the file until the end is reached
        //feof() check if file read end EOF
        while(!feof($file))
        {
         //fgets() Read row by row
         return fgets($file). "<br />";
        }
        fclose($file);
    }
        
}