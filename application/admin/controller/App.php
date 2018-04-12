<?php
// +----------------------------------------------------------------------
// | [RhaPHP System] Copyright (c) 2017-2020 http://www.rhaphp.com/
// +----------------------------------------------------------------------
// | [RhaPHP] 并不是自由软件,你可免费使用,未经许可不能去掉RhaPHP相关版权
// +----------------------------------------------------------------------
// | Author: Geeson <qimengkeji@vip.qq.com>
// +----------------------------------------------------------------------


namespace app\admin\controller;


use app\common\model\Addons;
use think\Db;
use think\facade\Request;
use think\Validate;

class App extends Base
{

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * @author GEESON 314835050@QQ.COM
     * @param string $type
     * @return \think\response\View
     */
    public function index($type = 'index')
    {
        if (!session('mpInfo')) {
            $this->error('请先进入公众号，再操作', url('mp/index/mplist'));
        }
        if ($type == 'index') {
            $model = new Addons();
            $result = Db::name('addons')->where('status', 1)->select();
            foreach ($result as $key => $value) {
                $upgrade_sql_file = ADDON_PATH . $value['addon'] . '/upgrade.sql';
                if (is_file($upgrade_sql_file)) {
                    $result[$key]['upgrade'] = 1;
                } else {
                    $result[$key]['upgrade'] = 0;
                }
            }
            $this->assign('addons', $result);

        }
        if ($type == 'uninstall') {
            $result = Db::name('addons')->where('status', 1)->select();
            $this->assign('addons', $result);
        }
        if ($type == 'notinstall') {
            $addonPath = ADDON_PATH;
            $F = opendir($addonPath);
            $addons = [];
            if ($F) {
                $model = new Addons();
                while (($file = readdir($F)) !== false) {
                    if ($file != '.' && $file != '..') {
                        if ($addonByFile = $model->getAddonByFile($file)) {
                            $addonByDb = $model->getAddonByDb($file);
                            if (empty($addonByDb) || $addonByDb['status'] == 0) {
                                $addons[] = $addonByFile;
                            }

                        }
                    }
                }
            }
            $this->assign('addons', $addons);
        }
        $apps = Db::name('addons')->where('status', 1)->select();
        $this->assign('apps', $apps);
        $this->assign('type', $type);
        $this->assign('menu_title', '公众号应用管理');
        return view('index');
    }

    /**
     * @author GEESON 314935050@qq.com
     * @param string $name
     * @return \think\response\View
     */
    public function config($name = '')
    {
        $model = new Addons();
        if ($name == null) {
            ajaxMsg('0', '没有要配置的应用');
        }
        if (Request::isPost()) {


        } else {

            $apps = Db::name('addons')->where('status', 1)->select();
            $addonCfByDb = Db::name('addons')->where(['addon' => $name, 'status' => 1])->find();
            $addonCfByFile = $model->getAddonByFile($name);
            if ($addonCfByDb['addon'] != $addonCfByFile['addon']) {
                $this->error('应用信息不相符，请检查');
            }
            $this->assign('addonInfo', $addonCfByFile);
            $this->assign('apps', $apps);
            $this->assign('name', $name);
            return view();
        }

    }

    /**
     * 安装应用扩展
     * @arthor GEESON 314835050@QQ.COM
     * @param null $name
     * @return \think\response\View
     */
    public function install($name = null)
    {
        if (Request::isPost()) {
            if ($name == null) {
                ajaxMsg('0', '没有要安装的应用');
            }
            $model = new Addons();
            $cf = $model->getAddonByFile($name);
            $data = [
                'name' => isset($cf['name']) ? $cf['name'] : '',
                'addon' => isset($cf['addon']) ? $cf['addon'] : '',
                'desc' => isset($cf['desc']) ? $cf['desc'] : '',
                'version' => isset($cf['version']) ? $cf['version'] : '',
                'author' => isset($cf['author']) ? $cf['author'] : '',
                'logo' => isset($cf['logo']) ? getAddonLogo($name) : '',
                'menu_show' => isset($cf['menu_show']) ? $cf['menu_show'] : '1',
                'entry_url' => isset($cf['entry_url']) ? $cf['entry_url'] : '',
                'admin_url' => isset($cf['admin_url']) ? $cf['admin_url'] : '',
                'config' => isset($cf['config']) ? json_encode($cf['config']) : '',

            ];
            $validate = new Validate(
                [
                    'name' => 'require',
                    'addon' => 'require',
                    'version' => 'require',
                    'logo' => 'require',
                    'author' => 'require',
                ],
                [
                    'title.require' => '应用名称不能为空',
                    'addon.require' => '应用标识不能为空',
                    'version.require' => '版本不能为空',
                    'logo.require' => 'Logo不能为空',
                    'author.require' => '作者信息不能为空',
                ]
            );
            $result = $validate->check($data);
            if ($result === false) {
                ajaxMsg(0, $validate->getError());
            }
            if ($addon = $model->getAddonByDb($name)) {
                if ($addon['status'] == 1) {
                    ajaxMsg('0', '应用已安装，请先卸载应用再重新安装');
                } else {

                    $data['status'] = 1;
                    $model->isUpdate(true)->save($data, ['id' => $addon['id']]);
                    ajaxMsg('1', '安装应用成功');

                }
            } else {
                if (isset($cf['install_sql']) && $cf['install_sql'] != '') {
                    $instalFile = ADDON_PATH . $name . DS . $cf['install_sql'];
                    if (!is_file($instalFile)) {
                        ajaxMsg('0', '没有找到安装数据的SQL文件：' . $cf['install_sql']);
                    } else {
                        if (!strpos($instalFile, '.sql')) {
                            ajaxMsg('0', '安装文件格式有误');
                        }
                        executeSql($instalFile);
                    }

                }
                if ($model->save($data)) {
                    ajaxMsg('1', '安装应用成功');
                }
            }


        } else {
            return view('index');
        }


    }

    /**
     * 卸载|关闭应用扩展
     * @param string $name
     */
    public function close($name = '')
    {
        if (Request::isPost()) {
            if ($name == null) {
                ajaxMsg('0', '没有要停用的应用');
            }
            $model = new Addons();
            if ($model->where(['addon' => $name, 'status' => 0])->find()) {
                ajaxMsg('0', '没有可以停用的应用');
            } else {
                if ($model->save(['status' => 0], ['addon' => $name])) {
                    ajaxMsg('1', '停用应用成功');
                } else {
                    ajaxMsg('0', '停用应用失败');
                }
            }
        }
    }

    /**
     * 升级应用
     * @param string $name
     */
    public function upgrade($name = '')
    {
        if ($name == null) {
            ajaxMsg('0', '没有此应用');
        }
        $upgradeFile = ADDON_PATH . $name . DS . '/upgrade.sql';
        if (!is_file($upgradeFile)) {
            ajaxMsg('0', '没有找到升级数据的SQL文件：upgrade.sql');
        } else {
            if (!strpos($upgradeFile, '.sql')) {
                ajaxMsg('0', 'SQL文件格式有误');
            }
            executeSql($upgradeFile);
            $model = new Addons();
            $cf = $model->getAddonByFile($name);
            $cf['version'];
            $model->save(['version' => $cf['version']], ['addon' => $name]);
            unlink($upgradeFile);
            ajaxMsg('1', '升级成功');
        }


//        $model = new Addons();
//        $cf = $model->getAddonByFile($name);
//        if (isset($cf['upgrade_sql']) && $cf['upgrade_sql'] != '') {
//            $instalFile = ADDON_PATH . $name . DS . $cf['upgrade_sql'];
//            if (!is_file($instalFile)) {
//                ajaxMsg('0', '没有找到安装数据的SQL文件：' . $cf['upgrade_sql']);
//            } else {
//                if (!strpos($instalFile, '.sql')) {
//                    ajaxMsg('0', 'SQL文件格式有误');
//                }
//                executeSql($instalFile);
//                ajaxMsg('1', '升级成功');
//            }
//
//        } else {
//            ajaxMsg('0', '此应用没有可升级的版本');
//        }
    }

    public function uninstall($name = '')
    {
        if ($name == null) {
            ajaxMsg('0', '没有此应用');
        }
        $path= ADDON_PATH . $name . DS;
        if(!file_exists($path)){
            ajaxMsg(0,$path.'目录不存在');
        }
        if(!is_writable($path)){
            ajaxMsg(0,$path.'目录没有权限删除');
        }
        if (Request::isAjax()) {
            $model = new Addons();
            $cf = $model->getAddonByFile($name);
            if (isset($cf['install_sql']) && $cf['install_sql'] != '') {
                $instalFile = ADDON_PATH . $name . DS . $cf['install_sql'];
                if (is_file($instalFile)) {//有数据表安装文件
                    $sql = file_get_contents($instalFile);
                    $sql = str_replace("\r", "\n", $sql);
                    $sql = explode(";\n", $sql);
                    $orginal = 'rh_';
                    $prefix = \think\facade\Config::get('database.prefix');
                    $sql = str_replace("{$orginal}", "{$prefix}", $sql);
                    foreach ($sql as $value) {
                        $value = trim($value);
                        if (!empty($value)) {
                            if (substr($value, 0, 12) == 'CREATE TABLE') {
                                $Tname = '';
                                preg_match('|EXISTS `(.*?)`|', $value, $outValue1);
                                preg_match('|TABLE `(.*?)`|', $value, $outValue2);
                                if (isset($outValue1[1]) && !empty($outValue1[1])) {
                                    $Tname = $outValue1[1];
                                }
                                if (isset($outValue2[1]) && !empty($outValue2[1])) {
                                    $Tname = $outValue2[1];
                                }
                                if ($Tname) {//如果存在表名
                                    $res = $model->query("SHOW TABLES LIKE '{$Tname}'");
                                    if ($res) {//数据库中存在着表，
                                        if (!$model->execute("DROP TABLE `{$Tname}`;")) {//删除
                                            //ajaxMsg('0', '删除' . $Tname . '表失败');
                                            //这里不知道是什么鬼，删除成功偶有返回 False
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $model = new Addons();
                    $model->where('addon', '=', $name)->delete();
                    $this->delDirAndFile($path);
                    ajaxMsg('1', '删除应用成功');
                }

            }
        }

    }
    //还原|清空
    public function wipeData($name = '')
    {
        if ($name == null) {
            ajaxMsg('0', '没有此应用');
        }
        if (Request::isAjax()) {
            $model = new Addons();
            $cf = $model->getAddonByFile($name);
            if (isset($cf['install_sql']) && $cf['install_sql'] != '') {
                $instalFile = ADDON_PATH . $name . DS . $cf['install_sql'];
                if (is_file($instalFile)) {//有数据表安装文件
                    $sql = file_get_contents($instalFile);
                    $sql = str_replace("\r", "\n", $sql);
                    $sql = explode(";\n", $sql);
                    $orginal = 'rh_';
                    $prefix = \think\facade\Config::get('database.prefix');
                    $sql = str_replace("{$orginal}", "{$prefix}", $sql);
                    foreach ($sql as $value) {
                        $value = trim($value);
                        if (!empty($value)) {
                            if (substr($value, 0, 12) == 'CREATE TABLE') {
                                $Tname = '';
                                preg_match('|EXISTS `(.*?)`|', $value, $outValue1);
                                preg_match('|TABLE `(.*?)`|', $value, $outValue2);
                                if (isset($outValue1[1]) && !empty($outValue1[1])) {
                                    $Tname = $outValue1[1];
                                }
                                if (isset($outValue2[1]) && !empty($outValue2[1])) {
                                    $Tname = $outValue2[1];
                                }
                                if ($Tname) {//如果存在表名
                                    $res = $model->query("SHOW TABLES LIKE '{$Tname}'");
                                    if ($res) {//数据库中存在着表，
                                        if (!$model->execute("DROP TABLE `{$Tname}`;")) {//删除
                                            //ajaxMsg('0', '删除' . $Tname . '表失败');
                                            //这里不知道是什么鬼，删除成功偶有返回 False
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $model = new Addons();
            $model->where('addon', '=', $name)->delete();
            ajaxMsg('1', '还原应用成功');
        }

    }

    private function delDirAndFile($path, $delDir = true)
    {
        $handle = opendir($path);
        if ($handle) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..')
                    is_dir("$path/$item") ? $this->delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
            }
            closedir($handle);
            if ($delDir)
                return rmdir($path);
        } else {
            if (file_exists($path)) {
                return unlink($path);
            }
        }
        return true;
    }
}