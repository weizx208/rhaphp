<?php
// +----------------------------------------------------------------------
// | [RhaPHP System] Copyright (c) 2017 http://www.rhaphp.com/
// +----------------------------------------------------------------------
// | [RhaPHP] 并不是自由软件,你可免费使用,未经许可不能去掉RhaPHP相关版权
// +----------------------------------------------------------------------
// | Author: Geeson <qimengkeji@vip.qq.com>
// +----------------------------------------------------------------------


namespace app\common\controller;

use think\facade\Config;
use \traits\controller\Jump;
use app\common\model\Addons;

class Addon extends Common
{

    use Jump;
    public $mpInfo;//当前公众号信息
    private $addonName;//应用名称
    private $addonController;
    private $addonAction;
    public $addonInfoByDb;//应用配置已保存的信息
    public $addonInfoByFile;//应用配置Config文件信息
    public $getAaddonConfigByMp;//获取应用对应当前的公众号保存的配置信息
    public $addonRoot;//应用的根目录

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->mpInfo = getMpInfo();
        $addonRule = session('addonRule');
        $this->addonName = $addonRule['addon'];
        $this->addonController = $addonRule['col'];
        $this->addonAction = $addonRule['act'];
        $model = new Addons();
        $this->addonInfoByFile = $model->getAddonByFile($this->addonName);
        $this->addonInfoByDb = $model->getAddonByDb($this->addonName);
        $this->getAaddonConfigByMp = $model->getAaddonConfigByMp($this->addonName, $this->mid);
        $this->addonRoot = ADDON_PATH . $this->addonName . '/';
        if ($this->addonInfoByDb['status'] == 0) {
            if (!empty($this->getAaddonConfigByMp) && is_array($this->getAaddonConfigByMp)) {
                if (isset($this->getAaddonConfigByMp['close_msg']) && !empty($this->getAaddonConfigByMp['close_msg'])) {
                    $this->error($this->getAaddonConfigByMp['close_msg']);
                } else {
                    $this->error('此应用已经设置为停止状态');
                }
            }
        }
        session('addonName', $this->addonName);
        session('mid', $this->mid);
    }

    public function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        if ($template == null) {
            $template = $this->addonAction;
        }
        if ($template == 'default') {
            $template = APP_PATH . 'common/view/default.' . config('template.view_suffix');
            echo parent::fetch($template, $vars, $replace, $config); // TODO: Change the autogenerated stub
        } else {
            if (strpos($template, "@") === false) {
                $tpls = explode('/', $template);
                $count = count($tpls);
                $suffix = config('template.view_suffix');
                $isSuffix = false;
                if (count($tpl_suffix = explode('.', $tpls[$count - 1])) == 2) {
                    $isSuffix = true;
                    if ($suffix != $tpl_suffix[1]) {
                        $suffix = $tpl_suffix[1];
                    }
                    $suffixLen = strlen($suffix) + 1;
                    $template = substr($tpls[$count - 1], 0, -$suffixLen);
                }
                switch ($count) {
                    case 1:
                        $template = ADDON_PATH . $this->addonName . '/view/' . strtolower($this->addonController) . '/' . $template . '.' . $suffix;
                        break;
                    case 2:
                        if (!empty($tpls[0]) && $isSuffix == true) {
                            $template = ADDON_PATH . $this->addonName . '/view/' . strtolower($tpls[0]) . '/' . $template . '.' . $suffix;
                        } else {
                            $template = ADDON_PATH . $this->addonName . '/view/' . strtolower($tpls[0]) . '/' . $tpls[1] . '.' . $suffix;
                        }
                        break;
                }
            } else {
                $template = substr($template, 1);
                $tpls = explode('/', $template);
                $count = count($tpls);
                $suffix = config('template.view_suffix');
                $isSuffix = false;
                if (count($tpl_suffix = explode('.', $tpls[$count - 1])) == 2) {
                    $isSuffix = true;
                    if ($suffix != $tpl_suffix[1]) {
                        $suffix = $tpl_suffix[1];
                    }
                    $suffixLen = strlen($suffix) + 1;
                    $template = substr($tpls[$count - 1], 0, -$suffixLen);
                }
                switch ($count) {
                    case 2:
                        if ($isSuffix == true) {
                            $template = ADDON_PATH . $this->addonName . '/view/' . strtolower($tpls[0]) . '/' . $this->addonController . '/' . $template . '.' . $suffix;
                        } else {
                            $template = ADDON_PATH . $this->addonName . '/view/' . strtolower($tpls[0]) . '/' . $this->addonController . '/' . $tpls[1] . '.' . $suffix;
                        }
                        break;
                    case 3:
                        if ($isSuffix == true) {
                            $template = ADDON_PATH . $this->addonName . '/view/' . strtolower($tpls[0]) . '/' . strtolower($tpls[1]) . '/' . $template . '.' . $suffix;
                        } else {
                            $template = ADDON_PATH . $this->addonName . '/view/' . strtolower($tpls[0]) . '/' . strtolower($tpls[1]) . '/' . $tpls[2] . '.' . $suffix;
                        }
                        break;
                }
            }
            $config['view_path'] = ADDON_PATH . $this->addonName . '/view/';
            echo parent::fetch($template, $vars, $config); // TODO: Change the autogenerated stub
        }

    }

//    public function assign($name, $value = '')
//    {
//        return parent::assign($name, $value); // TODO: Change the autogenerated stub
//    }

    public function assign($name, $value = '')
    {
        parent::assign($name, $value); // TODO: Change the autogenerated stub
    }

    public function getAdonnURL($url = '')
    {
        $node = '';
        if ($url == '') {
            $node = $this->addonName . DS . $this->addonController . DS . $this->addonAction;
        } else {
            $nodeArr = array_values(array_filter(explode('/', $url)));
            switch (count($nodeArr)) {
                case 1:
                    $node = $this->addonName . '/' . $this->addonController . '/' . $nodeArr[0];
                    break;
                case 2:
                    $node = $this->addonName . '/' . $nodeArr[0] . '/' . $nodeArr[1];
                    break;
                case 3:
                    $node = $node = $nodeArr[0] . '/' . $nodeArr[1] . '/' . $nodeArr[2];
                    break;
            }
        }
        $url = \think\facade\Url::build(ADDON_ROUTE . $node, ['mid' => $this->mid]);
        return $url = str_replace('.' . config('template.view_suffix'), '', $url);

    }


}