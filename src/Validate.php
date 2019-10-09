<?php

namespace Abovesky\Annotation\Validate;

use Abovesky\Annotation\Validate\exception\ValidateException;
use WangYu\Reflex;
use think\Validate;

/**
 * Class Validate 检验路由的参数
 * @package Abovesky\Validate\middleware
 */
class Validate
{
    /**
     * @var mixed 验证规则或者验证模型
     */
    protected $rule = [];
    /**
     * @var array 验证参数名称定义
     */
    protected $field = [];

    /**
     * @var string Validate.item $scene 验证器场景
     */
    protected $scene = null;

    /**
     * @var \think\Request $request 请求数据封装类
     */
    protected $request;

    // 设置默认路径
    protected $default_path = 'api/validate';
    // @param 模式
    protected $param = ['name'=>'param', 'rule'=>['name', 'doc', 'rule']];
    // @validate 模式
    protected $validate = ['name'=>'validate', 'rule'=>['validateModel']];

    private $ext = '.php';

    /**
     * 权限验证
     * @param \think\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws ValidateException
     */
    public function handle(\think\Request $request, \Closure $next)
    {
        $this->request = $request;
        $this->setReflexParamRule($this->request);
        if (!$this->check()) {
            throw new ValidateException();
        }
        return $next($this->request);
    }

    public function check(){
        if (is_string($this->rule)) {
            $validate = new $this->rule();
            (!empty($this->scene)) && $validate = $validate->scene($this->scene);
        }else{
            $validate = (new Validate())->make($this->rule,[],$this->field);
        }

        $res = $validate->batch()->check($this->request->param());
        if(!$res){
            throw new ValidateException([
                'message' => $validate->getError(),
            ]);
        }
        return true;
    }

    // 设置反射参数规则
    public function setReflexParamRule():void {
        $controller = lcfirst(str_replace('.',DIRECTORY_SEPARATOR,$this->request->controller()));
        $class = env('APP_NAMESPACE').DIRECTORY_SEPARATOR.$this->request->module().DIRECTORY_SEPARATOR.
            config('url_controller_layer').DIRECTORY_SEPARATOR.$controller;
        $class = str_replace('/','\\',$class);
        $reflex = new Reflex($class,$this->request->action());
        $param = $reflex->get($this->param['name'],$this->param['rule']);
        $validate = $reflex->get($this->validate['name'],$this->validate['rule']);
        if (!isset($validate[0]['validateModel'])){
            $this->setParamMode($param);
        }else{
            $this->setValidateMode($validate);
        }
    }

    // 设置@validate模式
    public function setValidateMode(array $validate):void {
        // 设置验证器场景
        if (strstr($validate[0]['validateModel'],'.')){
            $validateArr = explode('.',$validate[0]['validateModel']);
            $this->scene = $validateArr[1];
            $validate[0]['validateModel'] = $validateArr[0];
        }
        // 设置验证器
        if (substr($validate[0]['validateModel'],0,1) == '/' or substr($validate[0]['validateModel'],0,1) == '\\'){
            $this->rule = $validate[0]['validateModel'];
        }else{
            $validateFileMap = $this->getDirPhpFile($this->getValidateRootPath());
            $validateFile = $this->getValidateFile($validate[0]['validateModel'],$validateFileMap);
            if ($validateFile == null) return;
            $this->rule = str_replace(env('APP_PATH'),env('APP_NAMESPACE').'/',trim($validateFile,$this->ext));
        }

        // 格式化数据
        $this->rule = str_replace('/','\\',$this->rule);
    }

    // 获取验证器默认路径。
    private function getValidateRootPath(){
        $validate_root_path = empty(config('lin.validate_root_path')) ? $this->default_path :config('lin.validate_root_path');
        return env('APP_PATH').$validate_root_path;
    }

    // 获取验证器文件
    public function getValidateFile(string $validateModel,array $validateFileMap = []):?string {
        // 检测是否为分组验证器
        $controller = strstr($this->request->controller(),'.') ? explode('.',$this->request->controller())[1]:$this->request->controller();
        $groupValidateFile = $this->getValidateRootPath().DIRECTORY_SEPARATOR.strtolower($controller).DIRECTORY_SEPARATOR.$validateModel.$this->ext;
        if(in_array($groupValidateFile,$validateFileMap)) return $groupValidateFile;
        // 检测验证器目录下所有的验证器，是否有同名的验证器
        foreach ($validateFileMap as $item){
            if (strtolower(basename($item)) !== strtolower($validateModel.$this->ext)) continue;
            return $item;
        }
        return null;
    }

    // 获取文件夹下所有 $ext 的文件
    public function getDirPhpFile(string $dir):array {
        $validateFileMap = [];
        foreach (scandir($dir) as $index => $item){
            if (strstr($item,$this->ext) !== false){
                array_push($validateFileMap,$dir.'/'.$item);continue;
            }
            if (strstr($item,'.')!=false)  continue;
            $validateFileMap = array_merge($validateFileMap,$this->getDirPhpFile($dir.'/'.$item));
        }
        return $validateFileMap;
    }

    // 设置@param模式
    public function setParamMode(array $param):void {
        foreach ($param as $item){
            if(empty($item['rule'])) continue;
            $this->setField($item['name'],$item['doc']);
            $this->setRule($item['name'],$item['rule']);
        }
    }

    /**
     * @return array
     */
    public function setField($key,$val)
    {
        return $this->field[$key] = $val;
    }

    /**
     * @return array
     */
    public function setRule($key,$val)
    {
        return $this->rule[$key] = $val;
    }

}