<?php

namespace app\index\controller;

use app\common\typeCode\InformationCate as InformationCateController;
use app\common\typeCode\ForumCate as ForumCateController;
use app\common\typeCode\VideoCate as VideoCateController;
use app\common\model\Article;
use app\common\model\Category;
use think\Controller;
use think\facade\Session;
use think\Request;
use app\common\model\User as UserModel;

class Base extends Controller
{

    const WEB_SITE_PATH = CONFIG_PATH . 'website_config.json';        //网站配置路径

    private $configObject = null;

    private $userInfo = [];

    protected $userInfoSessionPath = null;   // 用户session 存储路径

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        $this->userInfoSessionPath = config('app.index_user_session_path');

        $this->getUserInfo();

        $this->loadingPublicData(\think\facade\Request::instance());


    }

    /**
     * 加载公共数据
     * @param Request $request
     */
    private function loadingPublicData(Request $request)
    {
        if ($request->isGet()){
            $this->assign('description',$this->getConfig('description'));

            $this->assign('keywords',$this->getConfig('keywords'));

            $this->assign('hot_line',$this->getConfig('hotLine'));

            $this->assign('base_address',$this->getConfig('address'));

            $this->assign('phone',$this->getConfig('phone'));

            $this->assign('fax',$this->getConfig('fax'));

            $this->assign('email',$this->getConfig('email'));

            $this->assign('footer_qr_code',$this->getConfig('footer_qr_code'));

            //产品标题
            $this->assign('top_product',Article::where(['type'=>1,'status'=>1,'delete_time'=>0])->order('id','desc')->field('id,title')->limit(6)->select());

            //资讯分类
            $ic = new InformationCateController();
            $this->assign('top_information',(new Category())->getList($ic->cacheName,$ic->cateType));

            //社区分类
            $fc = new ForumCateController();
            $this->assign('top_forum',(new Category())->getList($fc->cacheName,$fc->cateType));

            //视频分类
            $vc = new VideoCateController();
            $this->assign('top_video',(new Category())->getList($vc->cacheName,$vc->cateType));

        }
    }


    protected function getUserInfo()
    {
        $user_info = Session::get($this->userInfoSessionPath);
        if ($user_info) {
            $this->userInfo = (new UserModel())->where(['id' => $user_info['id'],'status'=>0])->find();
        }
    }

    public function __get($name)
    {
        // TODO: Implement __get() method.
        if ($name == 'userInfo'){
            if (!$this->$name) {
                if (\request()->isAjax()){
                    header('Content-type: application/json');
                    exit(json_encode(['code' => 0, 'msg' => '请先登陆账号~'], 256));
                }else{
                    return $this->redirect('index/Index/index');
                }
            }
            return $this->$name;
        }
    }

    /**
     * 获取配置信息
     * @param $name
     * @return mixed|null
     */
    protected function getConfig($name)
    {
        if (!$this->configObject){
            $this->configObject = json_decode(file_get_contents(self::WEB_SITE_PATH));
        }
        $configPath = explode('.', $name);
        $temp = $this->configObject;
        try {
            foreach ($configPath as $key => $value) {
                $temp = $temp->$value;
            }
            if ($temp === null) throw new \Exception();
        } catch (\Exception $e) {
            header('Content-type: application/json');
            exit(json_encode(['code' => 0, 'msg' => '获取配置失败'], 256));
        }
        $temp = json_decode(json_encode($temp,256),true);
        return $temp;
    }

}
