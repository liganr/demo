<?php

namespace app\controllers;

use app\models\db\CarrinfoR;
use app\models\db\SiteinfoR;
use app\models\Pile;
use app\models\Site;
use Yii;
use app\models\common\Common;
use app\models\common\ErrorCode;
use yii\base\DynamicModel;
use app\models\Fail;
use yii\log\Logger;


/**
 * 运营奖补订单控制器
 * @package app\controllers
 * @author sunlei
 */

class NewuploadController extends BaseController
{
    public $noNeedLoginAction = array(
//        'resouceupload'=>1,
//        'resoucereupload'=>1
    );
    public $noValidateAction = array(
//        'resouceupload'=>1,
//        'resoucereupload'=>1
    );
    //excel站字段
    const SITE_EXCEL_FIELD = [
        'A' => 'site_name',//站点名称
        'B' => 'carr_site_code', //运行编码
        'C' => 'build_state', //站点状态
        'D' => 'type',//服务类型
        'E' => 'run_time',//正式投运时间
        'F' => 'try_run_time',//试运行时间
        'G' => 'complete_time',//竣工时间
        'H' => 'project_time',//建设规划时间
        'I' => 'is_grouptube',//是否群管群控
        'J' => 'total_power',//站总功率（KW）

        'K' => 'dc_single_pile_num',//直流单枪桩数
        'L' => 'ac_single_pile_num',//交流单枪桩数
        'M' => 'dc_more_pile_num',//直流多枪桩数
        'N' => 'ac_more_pile_num',//交流多枪桩数
        'O' => 'adc_pile_num',//交直流一体桩数
        'P' => 'province_id',//省级行政区
        'Q' => 'city_id',//市级行政区
        'R' => 'area_id',//市辖区
        'S' => 'address',//具体地址
        'T' => 'lon',//经度
        'U' => 'lat',//纬度
        'V' => 'region_range',//地域范围
        'W' => 'bearing',//站点方位
        'X' => 'have_park_price',//是否有停车费
        'Y' => 'park',//停车场是否开放
        'Z' => 'open_time',//充电站开放时间段
        'AA' => 'park_price',//停车费计费方式
        'AB' => 'park_owner',//停车场产权
        'AC' => 'pay_type',//支付方式
        'AD' => 'pay_model_desc',//支付方式明细
        'AE' => 'price_type',//电费类型
        'AF' => 'service_price',//服务费单价
        'AG' => 'price_method',//计价方式  峰谷分时
        'AH' => 'price',//电费收取标准(json)

        'AI' => 'a',//电费收取标准(json)
        'AJ' => 'b',//电费收取标准(json)
        'AK' => 'c',//电费收取标准(json)
        'AL' => 'd',//电费收取标准(json)
        'AM' => 'spacing_device',//限位器
        'AN' => 'function_area',//所属功能区
        'AO' => 'res_person',//责任人
        'AP' => 'res_telephone',//责任人电话
        'AQ' => 'area',//建筑面积
        'AR' => 'manual_work',//站点人工值守
        'AS' => 'build',//建设单位 产权单位
        'AT' => 'run',//运营单位
        'AU' => 'look',//养护单位
        'AV' => 'camera',//摄像监控
        'AW' => 'schedule',//预约充电
        'AX' => 'notice'//站点公告'
    ];
    //excel桩字段
    const PILE_EXCEL_FIELD = [
        'A' => 'carr_site_code',//站点编码
        'B' => 'site_id',//站点名称
        'C' => 'carr_pile_code',//桩编码
        'D' => 'serial',//
        'E' => 'out_code',//设备出厂编码
        'F' => 'pro_from',//生产商
        'G' => 'equip_brand',//设备品牌
        'H' => 'pile_model',//桩型号
        'I' => 'out_time',//出厂日期
        'J' => 'type',//桩类型
        'K' => 'rated_power',//额定功率
        'L' => 'rated_u',//额定电压
        'M' => 'rated_i',//额定电流
        'N' => 'national_standard',//是否符合新国标
        'O' => 'organizationCode',//技术代码
        'P' => 'gun_num',//枪口数
        'Q' => 'gun_code1',//枪口编码1
        'R' => 'connector_id1',

        'S' => 'gun_charge_url1',//枪口充电二维码1
        'T' => 'gun_code2',//枪口编码2
        'U' => 'connector_id2',
        'V' => 'gun_charge_url2',//枪口充电二维码2
        'W' => 'gun_code3',//枪口编码3
        'X' => 'connector_id3',
        'Y' => 'gun_charge_url3',//枪口充电二维码3
        'Z' => 'gun_code4',//枪口编码4
        'AA' => 'connector_id4',
        'AB' => 'gun_charge_url4',//枪口充电二维码4

        'AC' => 'integer_pile_ac_guncode',//交直一体桩交流枪口编码
        'AD' => 'connector_id5',//交直一体桩交流枪
        'AE' => 'integer_pile_ac_qrcode',//交直一体桩交流枪充电二维码编码

        'AF' => 'integer_pile_dc_guncode',// 交直一体桩直流枪口编码
        'AG' => 'connector_id6',//交直一体桩直流枪F
        'AH' => 'integer_pile_dc_qrcode',//交直一体桩直流枪充电二维码编码
        'AI' => 'remark',//备注
    ];
    //站业务字段
    const SITE_FIELD = [
        'site_name'=>'充电站点标准名称',
        'carr_site_code'=>'充电站点运行编码',
        'build_state'=>'站点当前状态',
        'type'=>'站点服务类型',
        'run_time'=>'正式投运时间',
        'try_run_time'=>'启动试运行时间',
        'complete_time'=>'竣工时间',
        'is_grouptube'=>'是否群管群控',
        'total_power'=>'站总功率（KW）',
        'project_time'=>'建设规划时间',
        'dc_single_pile_num'=>'直流单枪桩数',
        'ac_single_pile_num'=>'交流单枪桩数',
        'dc_more_pile_num'=>'直流多枪桩数',
        'ac_more_pile_num'=>'交流多枪桩数',
        'adc_pile_num'=>'交直流一体桩数',
        'province_id'=>'省级行政区',
        'city_id'=>'市级行政区',
        'area_id'=>'区县',
        'address'=>'具体地址',
        'lon'=>'经度（百度）',
        'lat'=>'纬度（百度）',
        'region_range'=>'地域范围',
        'bearing'=>'站点方位',
        'have_park_price'=>'是否有停车费',
        'park'=>'充电场站是否对外开放',
        'open_time'=>'充电站开放时段',
        'park_price'=>'停车费收费标准',
        'park_owner'=>'停车场产权/物业单位',
        'pay_type'=>'支付方式',
        'pay_model_desc'=>'支付方式明细',
        'price_type'=>'电费类型',
        'service_price'=>'服务费单价',
        'price_method'=>'峰谷分时',
        'price'=>'非峰谷电价',
        'a'=>'尖电价',
        'b'=>'峰电价',
        'c'=>'平电价',
        'd'=>'谷电价',
        'spacing_device'=>'限位器',
        'function_area'=>'所属功能区',
        'res_person'=>'站点责任人姓名',
        'res_telephone'=>'站点责任人电话',
        'area'=>'场站建设面积',
        'manual_work'=>'站点人工值守',
        'build'=>'产权单位',
        'run'=>'运营单位',
        'look'=>'技术支持',
        'camera'=>'视频监控',
        'schedule'=>'预约充电',
        'notice'=>'场站公告',
    ];
    //站必填字段
    const SITE_REQUIRED_FIELD = [
        'site_name'=>'充电站点标准名称',
        'carr_site_code'=>'充电站点运行编码',
        'build_state'=>'站点当前状态',
        'type'=>'站点服务类型',
        'run_time'=>'正式投运时间',
        'complete_time'=>'竣工时间',
        'is_grouptube'=>'是否群管群控',
        'total_power'=>'站总功率（KW）',
        'dc_single_pile_num'=>'直流单枪桩数',
        'ac_single_pile_num'=>'交流单枪桩数',
        'dc_more_pile_num'=>'直流多枪桩数',
        'ac_more_pile_num'=>'交流多枪桩数',
        'adc_pile_num'=>'交直流一体桩数',
        'province_id'=>'省级行政区',
        'city_id'=>'市级行政区',
        'area_id'=>'区县',
        'address'=>'具体地址',
        'lon'=>'经度（百度）',
        'lat'=>'纬度（百度）',
        'region_range'=>'地域范围',
        'bearing'=>'站点方位',
        'have_park_price'=>'是否有停车费',
        'park'=>'充电场站是否对外开放',
        'open_time'=>'充电站开放时段',
        'park_price'=>'停车费收费标准',
        'park_owner'=>'停车场产权/物业单位',
        'pay_type'=>'支付方式',
        'pay_model_desc'=>'支付方式明细',
        'price_type'=>'电费类型',
        'service_price'=>'服务费单价',
        'price_method'=>'峰谷分时',
        'function_area'=>'所属功能区',
        'res_person'=>'站点责任人姓名',
        'res_telephone'=>'站点责任人电话',
        'manual_work'=>'站点人工值守',
        'build'=>'产权单位',
        'run'=>'运营单位',
        'look'=>'技术支持',
        'camera'=>'视频监控',
        'schedule'=>'预约充电',
    ];
    //桩字段
    const PILE_FILED = [
        'carr_site_code'=>'充电站点运行编码',
        'site_id'=>'所属站点标准名称',
        'carr_pile_code'=>'桩运营编码',
        'serial'=>'桩站内序号',
        'out_code'=>'设备出厂编码',
        'pro_from'=>'生产厂家名称',
        'equip_brand'=>'设备品牌',
        'pile_model'=>'生产厂家桩型号',
        'out_time'=>'出厂日期',
        'type'=>'桩类型',
        'rated_power'=>'额定功率(kW)',
        'rated_u'=>'额定电压(V)',
        'rated_i'=>'额定电流(A)',
        'national_standard'=>'符合新国标',
        'organizationCode'=>'技术平台组织机构代码',
        'gun_num'=>'枪口数',
        'gun_code1'=>'枪口编码1',
        'connector_id1'=>'connector_id1',
        'gun_charge_url1'=>'充电二维码编码1',
        'gun_code2'=>'枪口编码2',
        'connector_id2'=>'connector_id2',
        'gun_charge_url2'=>'充电二维码编码2',
        'gun_code3'=>'枪口编码3',
        'connector_id3'=>'connector_id3',
        'gun_charge_url3'=>'充电二维码编码3',
        'gun_code4'=>'枪口编码4',
        'connector_id4'=>'connector_id4',
        'gun_charge_url4'=>'充电二维码编码4',
        'integer_pile_ac_guncode'=>'交直一体桩交流枪口编码',
        'connector_id5'=>'connector_id5',
        'integer_pile_ac_qrcode'=>'交直一体桩交流枪充电二维码编码',
        'integer_pile_dc_guncode'=>'交直一体桩直流枪口编码',
        'connector_id6'=>'connector_id6',
        'integer_pile_dc_qrcode'=>'交直一体桩直流枪充电二维码编码',
        'remark'=>'桩备注信息'
    ];
    //桩必填字段
    const PILE_REQUIRED_FILD = [
        'carr_site_code'=>'充电站点运行编码',
        'site_id'=>'所属站点标准名称',
        'carr_pile_code'=>'桩运营编码',
        'out_code'=>'设备出厂编码',
        'pro_from'=>'生产厂家名称',
        'equip_brand'=>'设备品牌',
        'pile_model'=>'生产厂家桩型号',
        'out_time'=>'出厂日期',
        'type'=>'桩类型',
        'rated_power'=>'额定功率(kW)',
        'rated_u'=>'额定电压(V)',
        'rated_i'=>'额定电流(A)',
        'national_standard'=>'符合新国标',
        'organizationCode'=>'技术平台组织机构代码',
        'gun_num'=>'枪口数',
    ];

    /**
     * 检查文件后缀
     * @param $file_type_site
     * @param $file_type_pile
     * @return array
     */
    private function checkSuffix($file_type_site,$file_type_pile)
    {
        // 1.校验文件类型是否正确
        $err_msg = [];
        if (!in_array($file_type_site, array('XLSX', 'xlsx'))) {
            $err_msg[] = '站信息表上传的不是excel文件';
            Common::Log('the siteFile type is error', Logger::LEVEL_INFO);
        }
        if (!in_array($file_type_pile, array('XLSX', 'xlsx'))) {
            $err_msg[] = '桩信息表上传的不是excel文件';
            Common::Log('the pileFile type is error', Logger::LEVEL_INFO);
        }
        return $err_msg;
    }
    /**
     * 保存excel
     * @param $site_url
     * @param $pile_url
     * @return array
     */
    private function saveExcel($site_url,$pile_url)
    {
        $site_excel = file_get_contents($site_url);
        $pile_excel = file_get_contents($pile_url);

        $path = yii::$app->basePath;
        $site_url = $path . "/runtime/upload_excel/" . strtolower(basename($site_url));
        $pile_url = $path . "/runtime/upload_excel/" . strtolower(basename($pile_url));

        //存放路径
        $path = yii::$app->basePath;
        file_put_contents($site_url, $site_excel);
        file_put_contents($pile_url, $pile_excel);

        return ['site_url'=>$site_url,'pile_url'=>$pile_url];
    }
    /**
     * 导出解析excel
     * @param $site_url
     * @param $pile_url
     * @return array
     */
    private function importExCel($site_url,$pile_url)
    {
        $path = yii::$app->basePath;
        require_once $path . '/models/php_excel/MyExcel.php';
        require_once($path . '/models/php_excel/PHPExcel/Reader/Excel2007.php');

        $myExcel = new \MyExcel();
        $siteData = $myExcel->importExecl($site_url);
        $pileData = $myExcel->importExecl($pile_url);
        array_splice($siteData, 0, 3);
        array_splice($pileData, 0, 1);
        return ['site_data'=>$siteData,'pile_data'=>$pileData];
    }
    /**
     * 将excel字段转为业务字段
     * @param $siteData
     * @param $pileData
     * @return array
     */
    private function escapeField($siteData,$pileData)
    {
        $newSitedata = [];
        $newPiledata = [];
        foreach ($siteData as $k => $v) {
            foreach ($v as $key => $val) {
                if(in_array($key,['E','F','G','H','I','J','K','L','M','N','O','T','U','AH','AI','AJ','AK','AL','AF','AQ']))
                {
                    if(preg_match("/^([0-9]{1,}[.][0-9]*)$/",$val)){ //将浮点字符串转为数字
                        $val = floatval($val);
                    }
                    if(preg_match("/^([0-9]{1,})$/",$val)) { //将数字字符串转为数字
                        $val = intval($val);
                    }
                }
                $newSitedata[$k][self::SITE_EXCEL_FIELD[$key]] = $val;
            }
        }
        foreach ($pileData as $k => $v) {
            foreach ($v as $key => $val) {
                if(in_array($key,['K','L','M','I']))
                {
                    if(preg_match("/^([0-9]{1,}[.][0-9]*)$/",$val)){ //将浮点字符串转为数字
                        $val = floatval($val);
                    }
                    if(preg_match("/^([0-9]{1,})$/",$val)) { //将数字字符串转为数字
                        $val = intval($val);
                    }
                }
                $newPiledata[$k][self::PILE_EXCEL_FIELD[$key]] = $val;
            }
        }
        return ['site_data'=>$newSitedata,'pile_data'=>$newPiledata];
    }
    /**
     * 查询excel数据本身是否有重复
     * @param $siteData
     * @param $pileData
     * @return array
     */
    private function checkRepeat($siteData,$pileData)
    {
        $err_msg = [];
        $failModel = new Fail();
        $siteNames = array_column($siteData, 'site_name');
        $siteCodes = array_column($siteData, 'carr_site_code');

        //2.首先校验excel表中是否有重复项
        $sitenameIsrepeat = $failModel->checkArrDuplicate($siteNames);
        $siteCodeIsrepeat = $failModel->checkArrDuplicate($siteCodes);
        if ($sitenameIsrepeat) {
            $err_msg[] = "站信息表充电场站标准名称为{$sitenameIsrepeat}重复";
            Common::Log('the sitename is repeat', Logger::LEVEL_INFO);
        }
        if (!empty($siteCodeIsrepeat)) {
            $err_msg[] = "站信息表场站运行编码为{$siteCodeIsrepeat}重复";
            Common::Log('the sitecode is repeat', Logger::LEVEL_INFO);
        }
        $pileCodes = array_column($pileData, 'carr_pile_code');
        $pilecodeIsrepeat = $failModel->checkArrDuplicate($pileCodes);
        if (!empty($pilecodeIsrepeat)) {
            $err_msg[] = "桩信息表设备编码为{$pilecodeIsrepeat}重复";
            Common::Log('the pilecode is repeat', Logger::LEVEL_INFO);
        }
        $gun_code_arr = [];
        $gun_url_arr = [];
        $gun_connectid_arr = [];
        foreach ($pileData as $k => $v) {
            foreach ($v as $key => $val) {
                if (in_array($key, ['gun_code1', 'gun_code2', 'gun_code3', 'gun_code4', 'integer_pile_ac_guncode', 'integer_pile_dc_guncode'])) {
                    if (!empty($val)) {
                        $gun_code_arr[] = $val;
                    }
                }
                if (in_array($key, ['gun_charge_url1', 'gun_charge_url2', 'gun_charge_url3', 'gun_charge_url4', 'integer_pile_ac_qrcode', 'integer_pile_dc_qrcode'])) {
                    if (!empty($val)) {
                        $gun_url_arr[] = $val;
                    }
                }
                if (in_array($key, ['connector_id1', 'connector_id2', 'connector_id3', 'connector_id4', 'connector_id5', 'connector_id6'])) {
                    if (!empty($val)) {
                        $gun_connectid_arr[] = $val;
                    }
                }
            }
        }
        $duplicate_guncode = $failModel->checkArrDuplicate($gun_code_arr);
        if ($duplicate_guncode) {
            $err_msg[] = "桩信息表中枪编码为{$duplicate_guncode}重复";
            Common::Log('the guncode is repeat', Logger::LEVEL_INFO);
        }
        //检查excel表是否有重复的二维码
        $duplicate_url = $failModel->checkArrDuplicate($gun_url_arr);
        if ($duplicate_url) {
            $err_msg[] = "桩信息表中枪二维码为{$duplicate_url}重复";
            Common::Log('the gunurl is repeat', Logger::LEVEL_INFO);
        }
        //检查excel表是否有重复的connectid
        $duplicate_connectid = $failModel->checkArrDuplicate($gun_connectid_arr);
        if ($duplicate_connectid) {
            $err_msg[] = "桩信息表中枪connectorid为:{$duplicate_connectid}重复";
            Common::Log('the gunconnectid is repeat', Logger::LEVEL_INFO);
        }
        return $err_msg;
    }
    /**
     * 站数据规范性校验
     * @param $pretreatmentsiteData
     * @param $pretreatmentpiteData
     * @param $type
     * @return array
     */
    private function siteRules($pretreatmentsiteData,$pretreatmentpiteData,$type)
    {
        $err_msg = [];
        $failModel = new Fail();
        $num = 0;
        // 3.校验数据准确性与关联性
        foreach ($pretreatmentsiteData as $k => $v)
        {
            $num++;
            $dc_single_pile_num = 0;
            $ac_single_pile_num = 0;
            $dc_more_pile_num = 0;
            $ac_more_pile_num = 0;
            $adc_pile_num = 0;
            $pile_totalpower = 0;
            $sitePileInfo = isset($pretreatmentpiteData[$k]) ? $pretreatmentpiteData[$k] : false;
            if(!$sitePileInfo)
            {
                //这边需要直接返回错误
                $err_msg[] = "站信息表充电站点运行编码为{$k}未找到匹配桩";
                continue;
            }
            foreach ($sitePileInfo as $pileKey=>$pileValue)
            {
                $pile_totalpower = $pile_totalpower + $pileValue['rated_power'];
                $pileType = $this->getPileType($pileValue['type']);
                if(!$pileType)
                {
                    continue;
                }
                if ($pileType == 1) { //直流单枪桩
                    $dc_single_pile_num += 1;
                } elseif ($pileType == 2) { //交流单枪桩
                    $ac_single_pile_num += 1;
                } elseif ($pileType == 3) { //直流多枪桩
                    $dc_more_pile_num += 1;
                } elseif ($pileType == 4) { //交流多枪桩
                    $ac_more_pile_num += 1;
                } elseif ($pileType == 5) {
                    $adc_pile_num += 1;//交直流一体桩
                }
            }
            foreach ($v as $siteKey=>$siteValue)
            {
                if(is_string($siteValue))
                {
                    $siteValue = str_replace(' ','',$siteValue);
                }
                if($siteKey!= 'site_id')
                {
                    $filedName = self::SITE_FIELD[$siteKey];
                }
                //必填项
                if(array_key_exists($siteKey,self::SITE_REQUIRED_FIELD))
                {
                    if($siteValue === '')
                    {
                        $err_msg[] = "站信息表第{$num}行{$filedName}不能为空";
                    }
                }
                if (in_array($siteKey,['run_time','try_run_time','complete_time','project_time']))
                {
                    if (!empty($siteValue)) {
                        if (gettype($siteValue) == 'double' || gettype($siteValue) == 'integer') { //日期格式
                            $time = \PHPExcel_Shared_Date::ExcelToPHP($siteValue);//把带日期格式时间转为php数据
                            $pretreatmentsiteData[$k][$siteKey] = $time;
                        } else {
                            $err_msg[] = "站信息表第{$num}行正式投运时间、启动试运行时间、竣工时间、建设规划时间要求是日期格式";
                        }
                    }else{
                        $pretreatmentsiteData[$k][$siteKey] = 0;
                    }
                }
                if(in_array($siteKey,['dc_single_pile_num','ac_single_pile_num','dc_more_pile_num','ac_more_pile_num','adc_pile_num']))
                {
                    if(!is_int($siteValue))
                    {
                        $err_msg[] = "站信息表第{$num}行{$filedName}只能为整型格式";
                    }
                    if(empty($siteValue))
                    {
                        $siteValue = 0;
                    }
                    if($siteValue != $$siteKey)
                    {
                        $err_msg[] = "站信息表第{$num}行{$filedName}与桩表桩数不符";
                    }
                }
                if(in_array($siteKey,['lon','lat','service_price','res_telephone','area','total_power']))
                {
                    if(!is_numeric($siteValue))
                    {
                        $err_msg[] = "站信息表第{$num}行{$filedName}只能为整型或者小数格式";
                    }
                    if(empty($siteValue))
                    {
                        $siteValue = 0;
                    }
                }
                switch ($siteKey){
                    case 'site_name':
                        $search = '/公交|环卫|邮政|专用/';
                        if(preg_match($search , $siteValue)){
                            $err_msg[] = "站信息表第{$num}行{$filedName}不可含有'公交'、'环卫'、'邮政'、'专用'词组";
                        }
                        if($siteValue == '未知' || $siteValue == '/')
                        {
                            $err_msg[] = "站信息表第{$num}行{$filedName}不能为未知";
                        }
                        break;
                    case 'build_state':
                        $build_state = $this->getBuildState($siteValue);
                        if($build_state)
                        {
                            $pretreatmentsiteData[$k][$siteKey] = $build_state;
                        }else{
                            $filedName = self::SITE_FIELD[$siteKey];
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'type':
                        $siteType = $this->getType($siteValue);
                        if ($siteType && $siteType==$type) {
                            $pretreatmentsiteData[$k][$siteKey] = $type;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case "province_id":
                        $pretreatmentsiteData[$k][$siteKey] = 1;
                        break;
                    case 'city_id':
                        $pretreatmentsiteData[$k][$siteKey] = 2;
                        break;
                    case 'area_id':
                        $area = $failModel->getAreaID($siteValue);
                        //区县填错
                        if (empty($area) && $siteValue!='亦庄（大兴）' && $siteValue != '亦庄（通州）') {
                            $err_msg[] = "站信息表第{$num}行区县有误";
                        } else {
                            //经济开发区处理
                            if(in_array($area['id'],[3,4,5,8,6,7])){
                                //城六区
                                $development_zone = 1;
                            }else{
                                if($siteValue == '亦庄（大兴）') {
                                    //亦庄经济开发区
                                    $development_zone = 3;
                                    //置为大兴
                                    $area['id'] = 14;
                                }elseif ($siteValue == '亦庄（通州）')
                                {
                                    //亦庄经济开发区
                                    $development_zone = 3;
                                    $area['id'] = 11;
                                } else{
                                    $development_zone = 2;
                                }
                            }
                            $pretreatmentsiteData[$k][$siteKey] = $area['id'];
                            $pretreatmentsiteData[$k]['development_zone'] =$development_zone;
                        }
                        break;
                    case 'address':
                        if($siteValue == '未知' || $siteValue == '/')
                        {
                            $err_msg[] = "站信息表第{$num}行{$filedName}不能为未知";
                        }
                        if(mb_strlen($siteValue,'utf8') > 64)
                        {
                            $err_msg[] = "站信息表第{$num}行{$filedName}不能超过64字符";
                        }
                        break;
                    case 'region_range':
                        $regionRange = $this->getRegionRange($siteValue);
                        if ($regionRange) {
                            $pretreatmentsiteData[$k][$siteKey] = $regionRange;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'bearing':
                        $bearing = $this->getBearing($siteValue, $type);
                        if ($bearing) {
                            $pretreatmentsiteData[$k][$siteKey] = $bearing;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'have_park_price':
                        if ($siteValue == '有') {
                            $pretreatmentsiteData[$k][$siteKey] = 1;
                        } elseif ($siteValue == '无') {
                            $pretreatmentsiteData[$k][$siteKey] = 2;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'park':
                        $parkType = $this->getPark($siteValue, $type);
                        if ($parkType) {
                            $pretreatmentsiteData[$k][$siteKey] = $parkType;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'pay_type':
                        $payType = $this->getPayType($siteValue);
                        if ($payType) {
                            $pretreatmentsiteData[$k][$siteKey] = $payType;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'price_type':
                        $priceType = $this->getPriceType($siteValue);
                        if ($priceType) {
                            $pretreatmentsiteData[$k][$siteKey] = $priceType;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'price_method':
                        if ($siteValue == '是') {
                            $pretreatmentsiteData[$k][$siteKey] = 2;
                        } elseif ($siteValue == '否') {
                            $pretreatmentsiteData[$k][$siteKey] = 1;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'is_grouptube':
                        if($siteValue == '是')
                        {
                            $pretreatmentsiteData[$k][$siteKey] = 1;
                        } elseif ($siteValue == '否') {
                            $pretreatmentsiteData[$k][$siteKey] = 0;
                        }else{
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'total_power':
                        if($siteValue != $pile_totalpower)
                        {
                            $err_msg[] = "站信息表第{$num}行{$filedName}不等于桩总功率和";
                        }
                        $pretreatmentsiteData[$k][$siteKey] = $siteValue;
                        break;
                    case 'price':
                        if($pretreatmentsiteData[$k]['price_method'] == 2) //分时
                        {
                            $pretreatmentsiteData[$k][$siteKey] = '';
                        }else{
                            if(empty($siteValue))
                            {
                                $err_msg[] = "站信息表第{$num}行{$filedName}不能为空";
                            }
                            if(!is_numeric($siteValue))
                            {
                                $err_msg[] = "站信息表第{$num}行{$filedName}只能为整型或者小数格式";
                            }
                        }
                        break;
                    case    'spacing_device':
                        if ($siteValue == '有') {
                            $pretreatmentsiteData[$k][$siteKey] = 1;
                        } elseif ($siteValue == '无') {
                            $pretreatmentsiteData[$k][$siteKey] = 2;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case 'function_area':
                        $functionArea = $this->getFunctionArea($siteValue);
                        if ($functionArea) {
                            $pretreatmentsiteData[$k][$siteKey] = $functionArea;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case  'manual_work':
                        if ($siteValue == '有') {
                            $pretreatmentsiteData[$k][$siteKey] = 1;
                        } elseif ($siteValue == '无') {
                            $pretreatmentsiteData[$k][$siteKey] = 2;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case  'camera':
                        if ($siteValue == '有') {
                            $pretreatmentsiteData[$k][$siteKey] = 1;
                        } elseif ($siteValue == '无') {
                            $pretreatmentsiteData[$k][$siteKey] = 0;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                    case    'schedule':
                        if ($siteValue == '不支持') {
                            $pretreatmentsiteData[$k][$siteKey] = 2;
                        } elseif ($siteValue == '支持') {
                            $pretreatmentsiteData[$k][$siteKey] = 1;
                        } else {
                            $err_msg[] = "站信息表第{$num}行{$filedName}值错误";
                        }
                        break;
                }

            }
            $price_arr = [];
            if($pretreatmentsiteData[$k]['price_method'] == 2) //分时电价
            {
                if(!empty($pretreatmentsiteData[$k]['a']) && is_numeric($pretreatmentsiteData[$k]['a']))
                {
                    $price_arr['aa'] = '11:00-13:00/16:00-17:00';
                    $price_arr['a'] = $pretreatmentsiteData[$k]['a'];
                }
                if(!empty($pretreatmentsiteData[$k]['b']) && is_numeric($pretreatmentsiteData[$k]['b']))
                {
                    $price_arr['bb'] = '10:00-15:00/18:00-21:00';
                    $price_arr['b'] = $pretreatmentsiteData[$k]['b'];
                }
                if(!empty($pretreatmentsiteData[$k]['c']) && is_numeric($pretreatmentsiteData[$k]['c']))
                {
                    $price_arr['cc'] = '07:00-10:00/15:00-18:00/21:00-23:00';
                    $price_arr['c'] = $pretreatmentsiteData[$k]['c'];
                }
                if(!empty($pretreatmentsiteData[$k]['d']) && is_numeric($pretreatmentsiteData[$k]['d']))
                {
                    $price_arr['dd'] = '23:00-07:00';
                    $price_arr['d'] = $pretreatmentsiteData[$k]['d'];
                }
                if(count($price_arr) < 6)
                {
                    $err_msg[] = "站信息表第{$num}行峰时电价不能为空";
                }else{
                    $price_json = $this->getJsonPrice($price_arr);
                    $pretreatmentsiteData[$k]['price'] = $price_json;
                }
            }
        }
        if(empty($err_msg))
        {
            return ['status'=>1,'sitedata'=>$pretreatmentsiteData];
        }else{
            return ['status'=>2,'errmsg'=>$err_msg];
        }
    }
    /**
     * 桩数据规范性校验
     * @param $pretreatmentpiteNewdata
     * @param $type
     * @return array
     */
    private function pileRules($pretreatmentpiteNewdata,$type)
    {
        $num = 0;
        foreach ($pretreatmentpiteNewdata as $k=>$v) {
            $num++;
            foreach ($v as $pileKey => $pileValue) {
                if(is_string($pileValue))
                {
                    $pileValue = str_replace(' ','',$pileValue);
                }
                $filedName = self::PILE_FILED[$pileKey];
                //必填项
                if (array_key_exists($pileKey, self::PILE_REQUIRED_FILD)) {
                    if ($pileValue === '') {
                        $err_msg[] = "桩信息表第{$num}行{$filedName}不能为空";
                    }
                }
                if ($pileKey == 'out_time') {
                    if (!empty($pileValue)) {
                        if (gettype($pileValue) == 'double' || gettype($pileValue) == 'integer') { //日期格式
                            $time = \PHPExcel_Shared_Date::ExcelToPHP($pileValue);//把带日期格式时间转为php数据
                            $pretreatmentpiteNewdata[$k][$pileKey] = $time;
                        } else {
                            $err_msg[] = "桩信息表第{$num}行出厂日期要求是日期格式";
                        }
                    }
                }
                if (in_array($pileKey, ['rated_power', 'rated_u', 'rated_i'])) {
                    if (!is_numeric($pileValue) && $pileValue != 0) {
                        $err_msg[] = "站信息表第{$num}行{$filedName}只能为整型或者小数格式";
                    }
                }
                if ($pileKey == 'gun_num') {
                    if (!is_numeric($pileValue)) {
                        $err_msg[] = "桩信息表第{$num}行{$filedName}只能为整型格式";
                    }
                    if (intval($pileValue) > 4) {
                        $err_msg[] = "桩信息表第{$num}行{$filedName}目前只支持4个枪口";
                    }
                }
                if(in_array($pileKey,['carr_pile_code','out_code','pile_model']))
                {
                    if($pileValue == '未知' || $pileValue == '/')
                    {
                        $err_msg[] = "站信息表第{$num}行{$filedName}不能为未知";
                    }
                }
                if (in_array($pileKey, ['organizationCode', 'gun_code1', 'connector_id1', 'gun_charge_url1', 'gun_code2',
                    'connector_id2', 'gun_charge_url2', 'gun_code3', 'connector_id3', 'gun_charge_url3', 'gun_code4',
                    'connector_id4', 'gun_charge_url4', 'integer_pile_ac_guncode', 'connector_id5', 'integer_pile_ac_qrcode'
                    , 'integer_pile_dc_guncode', 'connector_id6', 'integer_pile_dc_qrcode'])) {
                    if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $pileValue) > 0) {
                        $err_msg[] = "桩信息表第{$num}行{$filedName}不能有中文字符";
                    }
                }
                if ($pileKey == 'type') {
                    $pileType = $this->getPileType($pileValue  );
                    if ($pileType) {
                        $pretreatmentpiteNewdata[$k][$pileKey] = $pileType;
                    } else {
                        $err_msg[] = "桩信息表第{$num}行{$filedName}值有误";
                    }
                }
                if($pileKey == 'national_standard')
                {
                    if ($pileValue == '符合') {
                        $pretreatmentpiteNewdata[$k][$pileKey] = 1;
                    } elseif ($pileValue == '不符合') {
                        $pretreatmentpiteNewdata[$k][$pileKey] = 2;
                    } else {
                        $err_msg[] = "桩信息表第{$num}行{$filedName}值有误";
                    }
                }
            }
            $guncodeKey = 'gun_code';
            $gunchargeurlKey = 'gun_charge_url';
            $connectoridKey = 'connector_id';
            $gun_num = $pretreatmentpiteNewdata[$k]['gun_num'];
            if($pretreatmentpiteNewdata[$k]['type']<5)  //
            {
                for($i=1;$i<=$gun_num;$i++)
                {
                    $tmpguncode = $guncodeKey.$i;
                    $tmpurl = $gunchargeurlKey.$i;
                    $tmpconnectorid = $connectoridKey.$i;
                    if(empty($pretreatmentpiteNewdata[$k][$tmpguncode]))
                    {
                        $tmpguncode = self::PILE_FILED[$tmpguncode];
                        $err_msg[] = "桩信息表第{$num}行枪口数为{$gun_num}时{$tmpguncode}不能为空";
                    }
                    if($type==1 || $type == 2)
                    {
                        if(empty($pretreatmentpiteNewdata[$k][$tmpurl]))
                        {
                            $tmpurl = self::PILE_FILED[$tmpurl];
                            $err_msg[] = "桩信息表第{$num}行枪口数为{$gun_num}时{$tmpurl}不能为空";
                        }
                    }else{
                        $pretreatmentpiteNewdata[$k][$tmpurl] = '';
                    }
                    if(empty($pretreatmentpiteNewdata[$k][$tmpconnectorid]))
                    {
                        $tmpconnectorid = self::PILE_FILED[$tmpconnectorid];
                        $err_msg[] = "桩信息表第{$num}行枪口数为{$gun_num}时{$tmpconnectorid}不能为空";
                    }
                }
                //其他枪口应该置为空
                for ($i=4;$i>$gun_num;$i--)
                {
                    $tmpguncode = $guncodeKey.$i;
                    $tmpurl = $gunchargeurlKey.$i;
                    $tmpconnectorid = $connectoridKey.$i;
                    $pretreatmentpiteNewdata[$k][$tmpurl] = '';
                    $pretreatmentpiteNewdata[$k][$tmpguncode] = '';
                    $pretreatmentpiteNewdata[$k][$tmpconnectorid] = '';
                }
                $pretreatmentpiteNewdata[$k]['integer_pile_ac_guncode'] = '';
                $pretreatmentpiteNewdata[$k]['integer_pile_ac_qrcode'] = '';
                $pretreatmentpiteNewdata[$k]['integer_pile_dc_guncode'] = '';
                $pretreatmentpiteNewdata[$k]['integer_pile_dc_qrcode'] = '';
            }else{
                if(empty($pretreatmentpiteNewdata[$k]['integer_pile_ac_guncode']))
                {
                    $err_msg[] = "桩信息表第{$num}行枪口数为{$gun_num}时交直一体桩交流枪口编码不能为空";
                }
                if($type==1 || $type == 2)
                {
                    if(empty($pretreatmentpiteNewdata[$k]['integer_pile_ac_qrcode']))
                    {
                        $err_msg[] = "桩信息表第{$num}行枪口数为{$gun_num}时交直一体桩交流枪充电二维码编码不能为空";
                    }
                    if(empty($pretreatmentpiteNewdata[$k]['integer_pile_dc_qrcode']))
                    {
                        $err_msg[] = "桩信息表第{$num}行枪口数为{$gun_num}时交直一体桩直流枪充电二维码编码不能为空";
                    }
                }else{
                    $pretreatmentpiteNewdata[$k]['integer_pile_ac_qrcode'] = '';
                    $pretreatmentpiteNewdata[$k]['integer_pile_dc_qrcode'] = '';
                }
                if(empty($pretreatmentpiteNewdata[$k]['connector_id5']))
                {
                    $err_msg[] = "桩信息表第{$num}行枪口数为{$gun_num}时connector_id6不能为空";
                }
                if(empty($pretreatmentpiteNewdata[$k]['integer_pile_dc_guncode']))
                {
                    $err_msg[] = "桩信息表第{$num}行枪口数为{$gun_num}时交直一体桩直流枪口编码不能为空";
                }
                if(empty($pretreatmentpiteNewdata[$k]['connector_id6']))
                {
                    $err_msg[] = "桩信息表第{$num}行枪口数为{$gun_num}时connector_id6不能为空";
                }
                //其他枪口应该置为空
                for ($i=1;$i<=4;$i++)
                {
                    $tmpguncode = $guncodeKey.$i;
                    $tmpurl = $gunchargeurlKey.$i;
                    $tmpconnectorid = $connectoridKey.$i;
                    $pretreatmentpiteNewdata[$k][$tmpurl] = '';
                    $pretreatmentpiteNewdata[$k][$tmpguncode] = '';
                    $pretreatmentpiteNewdata[$k][$tmpconnectorid] = '';
                }

            }
        }
        if(empty($err_msg))
        {
            return ['status'=>1,'piledata'=>$pretreatmentpiteNewdata];
        }else{
            return ['status'=>2,'errmsg'=>$err_msg];
        }

    }
    /**
     * 站桩唯一性校验
     * @param $pretreatmentsiteData
     * @param $pretreatmentpiteNewdata
     * @param $carr_id
     * @param $type
     * @return array
     */
    private function uniqueData($pretreatmentsiteData,$pretreatmentpiteNewdata,$carr_id,$type)
    {
        $err_msg = [];
        $failModel = new Fail();
        $siteModel = new Site() ;
        $pileModel = new Pile();
        //该运营商下的站编码
        $carr_site_code_arr=$siteModel->getCarrSiteCode($carr_id);
        $carr_site_code_db=array_flip($carr_site_code_arr);
        //该运营商下的站名称
        $carr_site_name_arr=$siteModel->getCarrSiteName($carr_id);
        $carr_site_name_db=array_flip($carr_site_name_arr);
        $num = 0;
        foreach ($pretreatmentsiteData as $k=>$v)
        {
            $num++;
            if(isset($carr_site_code_db[$v['carr_site_code']]))
            {
                $filedName = self::SITE_FIELD['carr_site_code'];
                $err_msg[] = "站信息表第{$num}行{$filedName}已存在";

            }
            if(isset($carr_site_name_db[$v['site_name']]))
            {
                $filedName = self::SITE_FIELD['site_name'];
                $err_msg[] = "站信息表第{$num}行{$filedName}已存在";

            }
        }
        //该运营商桩与枪信息
        $pile_code_db = $pileModel->getPilecode($carr_id);
        $gun_code = $failModel->getGuncode($carr_id);
        $charge_url = $failModel->getChargeUrl($carr_id);
        $connector_id = $failModel->getConnectorId($carr_id);
        $gun_code_db = array_flip($gun_code);
        $charge_url_db = array_flip($charge_url);
        $connector_id_db = array_flip($connector_id);
        $num = 0;
        foreach ($pretreatmentpiteNewdata as $k=>$v)
        {
            $num++;
            if(isset($pile_code_db[$v['carr_pile_code']]))
            {
                $filedName = self::PILE_FILED['carr_pile_code'];
                $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";

            }
            if($v['type']<5)  //
            {
                $guncodeKey = 'gun_code';
                $gunchargeurlKey = 'gun_charge_url';
                $connectoridKey = 'connector_id';
                $gun_num = $pretreatmentpiteNewdata[$k]['gun_num'];
                for($i=1;$i<=$gun_num;$i++)
                {
                    $tmpguncode = $guncodeKey.$i;
                    $tmpurl = $gunchargeurlKey.$i;
                    $tmpconnectorid = $connectoridKey.$i;
                    if(isset($gun_code_db[$v[$tmpguncode]]))
                    {
                        $filedName = self::PILE_FILED[$tmpguncode];
                        $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                    }
                    if($type == 1|| $type ==2)
                    {
                        if(isset($charge_url_db[$v[$tmpurl]]))
                        {
                            $filedName = self::PILE_FILED[$tmpurl];
                            $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                        }
                    }
                    if(isset($connector_id_db[$v[$tmpconnectorid]]))
                    {
                        $filedName = self::PILE_FILED[$tmpconnectorid];
                        $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                    }
                }
            }else {
                if (isset($gun_code_db[$v['integer_pile_ac_guncode']])) {
                    $filedName = self::PILE_FILED['integer_pile_ac_guncode'];
                    $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                }
                if($type == 1|| $type == 2)
                {
                    if (isset($charge_url_db[$v['integer_pile_ac_guncode']])) {
                        $filedName = self::PILE_FILED['integer_pile_ac_guncode'];
                        $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                    }
                    if (isset($charge_url_db[$v['integer_pile_dc_qrcode']])) {
                        $filedName = self::PILE_FILED['integer_pile_ac_qrcode'];
                        $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                    }
                }
                if (isset($connector_id_db[$v['connector_id5']])) {
                    $filedName = self::PILE_FILED['connector_id5'];
                    $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                }
                if (isset($gun_code_db[$v['integer_pile_dc_guncode']])) {
                    $filedName = self::PILE_FILED['integer_pile_dc_guncode'];
                    $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                }
                if (isset($connector_id_db[$v['connector_id6']])) {
                    $filedName = self::PILE_FILED['connector_id6'];
                    $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                }
            }
        }
        if(empty($err_msg))
        {
            return ['status'=>1,'sitedata'=>$pretreatmentsiteData];
        }else{
            return ['status'=>2,'errmsg'=>$err_msg];
        }
    }

    private function getJsonPrice($price_arr)
    {
        $price_new = [];
        if (!empty($price_arr['aa'])) {
            $section = '尖';
            $sharp_price = round($price_arr['a'], 4);
            $sharp_price_arr = $this->getPriceMember($price_arr['aa'], $section, $sharp_price);
            $price_new['prices'][] = $sharp_price_arr;
        }
        if (!empty($price_arr['bb'])) {
            $section = '峰';
            $peak_price = round($price_arr['b'], 4);
            $peak_price_arr = $this->getPriceMember($price_arr['bb'], $section, $peak_price);
            $price_new['prices'][] = $peak_price_arr;
        }
        if (!empty($price_arr['cc'])) {
            $section = '平';
            $flat_price = round($price_arr['c'], 4);
            $flat_price_arr = $this->getPriceMember($price_arr['cc'], $section, $flat_price);
            $price_new['prices'][] = $flat_price_arr;
        }
        if (!empty($price_arr['dd'])) {
            $section = '谷';
            $valley_price = round($price_arr['d'], 4);
            $valley_price_arr = $this->getPriceMember($price_arr['dd'], $section, $valley_price);
            $price_new['prices'][] = $valley_price_arr;
        }
        $sharp_price = empty($sharp_price) ? 0 : $sharp_price;
        $peak_price = empty($peak_price) ? 0 : $peak_price;
        $flat_price = empty($flat_price) ? 0 : $flat_price;
        $valley_price = empty($valley_price) ? 0 : $valley_price;
        $max_price = max($sharp_price, $peak_price, $flat_price, $valley_price);
        $price_new['type'] = 1;

        $price_new['max_price'] = $max_price;
        //$res=json_encode($price_new,JSON_UNESCAPED_UNICODE);;
        //echo '<pre>';print_r($res);echo '</pre>';die;
        return json_encode($price_new, JSON_UNESCAPED_UNICODE);
    }

    private function getPriceMember($duration, $section, $price)
    {
        $items = [];
        //多个时间段
        if (strstr($duration, '/')) {
            $durations = explode('/', $duration);
            $items_num = count($durations);
            for ($i = 0; $i < $items_num; $i++) {
                $items[$i]['duration'] = $durations[$i];
                $items[$i]['price'] = $price;
            }
        } else {

            $items[0]['duration'] = $duration;
            $items[0]['price'] = $price;
        }

        $prices['section'] = $section;
        $prices['items'] = $items;

        return $prices;
    }
    /**
     * 上传
     */
    public function actionResouceupload()
    {
        ini_set('memory_limit',-1);
        set_time_limit(0);

        $request = yii::$app->request;
        $carr_id = $request->post('carr_id');
        $type = $request->post('type');
        $site_url = $request->post('site_url');
        $pile_url = $request->post('pile_url');
        $userinfo = $this->userInfo;

        $err_msg = [];
        if ($userinfo['role'] == 1) {
            $carr_id = $userinfo['role_depid'];
        } else {

            $model = DynamicModel::validateData(compact('carr_id', 'site_url', 'pile_url', 'type'), [
                [['carr_id'], 'required', 'message' => 'carr_id不能为空'],
                [['site_url'], 'required', 'message' => 'site_url不能为空'],
                [['pile_url'], 'required', 'message' => 'pile_url不能为空'],
                [['type'], 'required', 'message' => '站类型不能为空'],
                ['carr_id', 'exist', 'targetClass' => '\app\models\db\CarrinfoR', 'message' => '运营商id不存在'],
            ]);
            if ($model->hasErrors()) {
                $errors = $model->getErrors();
                Common::outJsonResult(ErrorCode::PARAM_WRONG, $errors);
                exit;
            }
        }

        $file_type_site = substr(strrchr($site_url, '.'), 1);
        $file_type_pile = substr(strrchr($pile_url, '.'), 1);

        $err_msg = $this->checkSuffix($file_type_site,$file_type_pile);
        //记录上传记录
        $failModel = new Fail();
        $esiteid = $failModel->Saveexcel($userinfo['uid'], '', 1, $site_url);
        $epileid = $failModel->Saveexcel($userinfo['uid'], '', 2, $pile_url);

        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['excel_sid' => $esiteid]);
        }
        //存储excel
        $urlArr = $this->saveExcel($site_url,$pile_url);
        $site_url = $urlArr['site_url'];
        $pile_url = $urlArr['pile_url'];

        $excelData = $this->importExCel($site_url,$pile_url);
        $siteData = $excelData['site_data'];
        $pileData = $excelData['pile_data'];

        if(count($siteData[0]) != 50 || count($pileData[0])!= 35)
        {
            $err_msg[] = '站信息表或桩信息表与模板不一致,请下载最新模板上传';
        }
        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['excel_sid' => $esiteid]);
        }
        //将excel字段转为参数字段
        $dbArr = $this->escapeField($siteData,$pileData);
        $siteData = $dbArr['site_data'];
        $pileData = $dbArr['pile_data'];
        $err_msg = $this->checkRepeat($siteData,$pileData);
        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['excel_sid' => $esiteid]);
        }
        //组装站桩信息
        $pretreatmentsiteData = [];
        $pretreatmentpiteData = [];
        $pretreatmentpiteNewdata = [];
        foreach ($siteData as $k => $v) {
            $pretreatmentsiteData[$v['carr_site_code']] = $v;
        }
        foreach ($pileData as $k => $v) {
            $pretreatmentpiteData[$v['carr_site_code']][] = $v;
            $pretreatmentpiteNewdata[] = $v;
        }
        //站完整性校验
        $siterulesResult = $this->siteRules($pretreatmentsiteData,$pretreatmentpiteData,$type);
        if($siterulesResult['status'] == 2)
        {
            $failModel->saveErrMsg($siterulesResult['errmsg'], $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['excel_sid' => $esiteid]);
        }else{
            $pretreatmentsiteData = $siterulesResult['sitedata'];
        }

        //桩完整性校验
        $pilerulesResult = $this->pileRules($pretreatmentpiteNewdata,$type);
        if($pilerulesResult['status'] == 2)
        {
            $failModel->saveErrMsg($pilerulesResult['errmsg'], $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['excel_sid' => $esiteid]);
        }else{
            $pretreatmentpiteNewdata = $pilerulesResult['piledata'];
        }

        //站桩校验唯一性
        $uniqueResult = $this->uniqueData($pretreatmentsiteData,$pretreatmentpiteNewdata,$carr_id,$type);
        if($uniqueResult['status'] == 2)
        {
            $failModel->saveErrMsg($uniqueResult['errmsg'], $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['excel_sid' => $esiteid]);
        }

        //入库
        $resultSitedata = $pretreatmentsiteData;
        $resultPiledata = [];
        foreach ($pretreatmentpiteNewdata as $k=>$v)
        {
            $resultPiledata[$v['carr_site_code']][] = $v;
        }
        $siteModel = new Site();
        $insertResult = $siteModel->uploadInsert($type,$resultSitedata,$resultPiledata,$carr_id);
        if(!$insertResult)
        {
            $err_msg[] = '提示消息入库失败';
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::EXCEPTION_OCCURE, ['excel_sid' => $esiteid]);
        }
        $success_data = [
            "成功导入" . $insertResult['siteNum'] . "个站点",
            "成功导入" . $insertResult['pileNum'] . "个桩",
            "总功率" . $insertResult['pilePower'] . "KW"
        ];
        $failModel->saveErrMsg($success_data, $esiteid, $epileid,1);
        Common::outJsonResult(ErrorCode::SUCCESS, $success_data);
    }

    /**
     * 整改
     */
    public function actionResoucereupload()
    {
        ini_set('memory_limit',-1);
        set_time_limit(0);
        $request = yii::$app->request;
        $carr_id = $request->post('carr_id');
        $type = $request->post('type');
        $site_url = $request->post('site_url');
        $pile_url = $request->post('pile_url');
        $userinfo = $this->userInfo;

        $err_msg = [];
        if ($userinfo['role'] == 1) {
            $carr_id = $userinfo['role_depid'];
        } else {

            $model = DynamicModel::validateData(compact('carr_id', 'site_url', 'pile_url', 'type'), [
                [['carr_id'], 'required', 'message' => 'carr_id不能为空'],
                [['site_url'], 'required', 'message' => 'site_url不能为空'],
                [['pile_url'], 'required', 'message' => 'pile_url不能为空'],
                [['type'], 'required', 'message' => '站类型不能为空'],
                ['carr_id', 'exist', 'targetClass' => '\app\models\db\CarrinfoR', 'message' => '运营商id不存在'],
            ]);
            if ($model->hasErrors()) {
                $errors = $model->getErrors();
                Common::outJsonResult(ErrorCode::PARAM_WRONG, $errors);
                exit;
            }
        }

        $file_type_site = substr(strrchr($site_url, '.'), 1);
        $file_type_pile = substr(strrchr($pile_url, '.'), 1);

        $err_msg = $this->checkSuffix($file_type_site,$file_type_pile);
        //记录上传记录
        $failModel = new Fail();
        $esiteid = $failModel->Saveexcel($userinfo['uid'], '', 1, $site_url);
        $epileid = $failModel->Saveexcel($userinfo['uid'], '', 2, $pile_url);

        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
        }
        //存储excel
        $urlArr = $this->saveExcel($site_url,$pile_url);
        $site_url = $urlArr['site_url'];
        $pile_url = $urlArr['pile_url'];

        $excelData = $this->importExCel($site_url,$pile_url);
        $siteData = $excelData['site_data'];
        $pileData = $excelData['pile_data'];

        if(count($siteData[0]) != 50 || count($pileData[0])!= 35)
        {
            $err_msg[] = '站信息表或桩信息表与模板不一致,请下载最新模板上传';
        }
        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
        }
        //将excel字段转为参数字段
        $dbArr = $this->escapeField($siteData,$pileData);
        $siteData = $dbArr['site_data'];
        $pileData = $dbArr['pile_data'];
        //此处该封装--------
        $err_msg = $this->checkRepeat($siteData,$pileData);
        //-- 封装到这里了------
        $newSitedata = $siteData;
        $newPiledata = $pileData;

        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
        }

        $siteRmodel = new SiteinfoR();
        $pileModel = new Pile();

        foreach ($newSitedata as $k=>$v)
        {
            //以运营商 站名称 站唯一编码去匹配是否存在 不存在则返回错误
            $tmpInfo = $siteRmodel::find()->where(['site_name'=>$v['site_name'],'carr_id'=>$carr_id])
                ->andWhere(['access_state'=>[0,3]])
                ->andWhere(['build_access_state'=>[0,3]])
                ->andWhere(['site_tag'=>[0,2]])
                ->andWhere(['!=','verify',2])
                ->asArray()->one();
            if(empty($tmpInfo)){
                $msg = '站名为:' . $v['site_name'] . '的站不存在或者不符合整改规范';
                //存入错误信息
                $err_msg[] = $msg;
            }else{
                $newSitedata[$k]['site_id']= $tmpInfo['site_id'];
            }
        }
        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
        }
        $this->checkRepeat($siteData,$pileData);
        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
        }

        $pretreatmentsiteData = [];
        $pretreatmentpiteData = [];
        $pretreatmentpiteNewdata = [];
        foreach ($newSitedata as $k => $v) {
            $pretreatmentsiteData[$v['carr_site_code']] = $v;
        }
        foreach ($newPiledata as $k => $v) {
            $pretreatmentpiteData[$v['carr_site_code']][] = $v;
            $pretreatmentpiteNewdata[] = $v;
        }
        //站完整性校验
        $siterulesResult = $this->siteRules($pretreatmentsiteData,$pretreatmentpiteData,$type);
        if($siterulesResult['status'] == 2)
        {
            $failModel->saveErrMsg($siterulesResult['errmsg'], $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $siterulesResult['errmsg']]);
        }else{
            $pretreatmentsiteData = $siterulesResult['sitedata'];
        }

        //桩完整性校验
        $pilerulesResult = $this->pileRules($pretreatmentpiteNewdata,$type);
        if($pilerulesResult['status'] == 2)
        {
            $failModel->saveErrMsg($pilerulesResult['errmsg'], $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $pilerulesResult['errmsg']]);
        }else{
            $pretreatmentpiteNewdata = $pilerulesResult['piledata'];
        }

        //整改校验唯一性
        $siteModel = new Site() ;
        $pileModel = new Pile();
        $num = 0;
        $siteIds_data = [];
        foreach ($pretreatmentsiteData as $k=>$v)
        {
            $siteIds_data[] = $v['site_id'];
            //该运营商下的站编码
            $carr_site_code_arr=$siteModel->getCarrSiteCode($carr_id,$v['site_id']);
            $carr_site_code_db=array_flip($carr_site_code_arr);
            //该运营商下的站名称
            $carr_site_name_arr=$siteModel->getCarrSiteName($carr_id,$v['site_id']);
            $carr_site_name_db=array_flip($carr_site_name_arr);

            $num++;
            if(isset($carr_site_code_db[$v['carr_site_code']]))
            {
                $filedName = self::SITE_FIELD['carr_site_code'];
                $err_msg[] = "站信息表第{$num}行{$filedName}已存在";

            }
            if(isset($carr_site_name_db[$v['site_name']]))
            {
                $filedName = self::SITE_FIELD['site_name'];
                $err_msg[] = "站信息表第{$num}行{$filedName}已存在";

            }
        }
        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
        }
        //该运营商桩与枪信息
        $pile_code_db = $pileModel->getPilecode($carr_id,$siteIds_data);
        $gun_code = $failModel->getGuncode($carr_id,$siteIds_data);
        $charge_url = $failModel->getChargeUrl($carr_id,$siteIds_data);
        $connector_id = $failModel->getConnectorId($carr_id,$siteIds_data);
        $gun_code_db = array_flip($gun_code);
        $charge_url_db = array_flip($charge_url);
        $connector_id_db = array_flip($connector_id);
        $num = 0;

        foreach ($pretreatmentpiteNewdata as $k=>$v)
        {
            $num++;
            if(isset($pile_code_db[$v['carr_pile_code']]))
            {
                $filedName = self::PILE_FILED['carr_pile_code'];
                $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";

            }
            if($v['type']<5)  //
            {
                $guncodeKey = 'gun_code';
                $gunchargeurlKey = 'gun_charge_url';
                $connectoridKey = 'connector_id';
                $gun_num = $pretreatmentpiteNewdata[$k]['gun_num'];
                for($i=1;$i<=$gun_num;$i++)
                {
                    $tmpguncode = $guncodeKey.$i;
                    $tmpurl = $gunchargeurlKey.$i;
                    $tmpconnectorid = $connectoridKey.$i;
                    if(isset($gun_code_db[$v[$tmpguncode]]))
                    {
                        $filedName = self::PILE_FILED[$tmpguncode];
                        $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                    }
                    if($type == 1|| $type ==2)
                    {
                        if(isset($charge_url_db[$v[$tmpurl]]))
                        {
                            $filedName = self::PILE_FILED[$tmpurl];
                            $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                        }
                    }
                    if(isset($connector_id_db[$v[$tmpconnectorid]]))
                    {
                        $filedName = self::PILE_FILED[$tmpconnectorid];
                        $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                    }
                }
            }else {
                if (isset($gun_code_db[$v['integer_pile_ac_guncode']])) {
                    $filedName = self::PILE_FILED['integer_pile_ac_guncode'];
                    $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                }
                if($type == 1|| $type == 2)
                {
                    if (isset($charge_url_db[$v['integer_pile_ac_guncode']])) {
                        $filedName = self::PILE_FILED['integer_pile_ac_guncode'];
                        $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                    }
                    if (isset($charge_url_db[$v['integer_pile_dc_qrcode']])) {
                        $filedName = self::PILE_FILED['integer_pile_ac_qrcode'];
                        $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                    }
                }
                if (isset($connector_id_db[$v['connector_id5']])) {
                    $filedName = self::PILE_FILED['connector_id5'];
                    $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                }
                if (isset($gun_code_db[$v['integer_pile_dc_guncode']])) {
                    $filedName = self::PILE_FILED['integer_pile_dc_guncode'];
                    $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                }
                if (isset($connector_id_db[$v['connector_id6']])) {
                    $filedName = self::PILE_FILED['connector_id6'];
                    $err_msg[] = "桩信息表第{$num}行{$filedName}已存在";
                }
            }

        }
        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
        }

        $resultPiledata = [];
        foreach ($pretreatmentpiteNewdata as $k=>$v)
        {
            $resultPiledata[$v['carr_site_code']][] = $v;
        }
        $connection = yii::$app->elaid_im_db;
        $transaction = $connection->beginTransaction();
        $inser_siteid = array_column($pretreatmentsiteData,'site_id');
        //删除之前桩
        $tmpPileinfo = $pileModel->getPile('pile_id',['site_id'=>$inser_siteid])->asArray()->all();
        $tmpPileid = array_column($tmpPileinfo,'pile_id');
        $result = $pileModel->deletePile(['pile_id'=>$tmpPileid]);
        if(!$result)
        {
            $msg = '桩更新失败';
            //存入错误信息
            $err_msg[] = $msg;
        }
        if (!empty($err_msg)) {
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
        }
        $result = $siteModel->uploadUpdate($pretreatmentsiteData,$resultPiledata,$carr_id);
        if($carr_id == 52)
        {
            $transaction->commit();
            Common::outJsonResult(ErrorCode::SUCCESS);
        }
        if($result)
        {
            if($type == 1)
            {
                $result =   $failModel->transSitePileInfo($inser_siteid,$carr_id,2,1);
                $res_arr=json_decode($result['app_result'],true);
                $siteid_name=$result['siteid_name'];
            }else{
                $result=$failModel->transSitePileInfo($inser_siteid,$carr_id,1,2);
                $res_arr = [];
            }
            if(!empty($res_arr['data']['fail']))
            {
                $err_msg = "系统更新失败";
                $data['err_msg'] = $err_msg;
                Common::Log('update_site fail:'.json_encode($res_arr), Logger::LEVEL_ERROR);
                $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
                $transaction->rollBack();
                Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
            }else{
                $transaction->commit();
                Common::outJsonResult(ErrorCode::SUCCESS);
            }

        }else{
            $err_msg = "更新失败";
            $data['err_msg'] = $err_msg;
            Common::Log('update_site db fail:', Logger::LEVEL_ERROR);
            $failModel->saveErrMsg($err_msg, $esiteid, $epileid,2);
            $transaction->rollBack();
            Common::outJsonResult(ErrorCode::PARAM_WRONG, ['err_msg' => $err_msg]);
        }

    }

    public function getPark($a, $type)
    {
        //单位内部
        $arr_pub = array('全天开放' => 1, '分时段开放' => 2);
        $arr_private = array('全天开放' => 1, '分时段开放' => 2, '不开放' => 3);

        $Arr = ($type == 4 || $type == 3) ? $arr_private : $arr_pub;
        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }

    public function getBearing($a, $type)
    {
        //单位内部
        $arr_private = array('地面' => 5, '地下' => 6, '地面和地下' => 7);
        $arr_pub = array('地面' => 5, '地下' => 6, '地面和地下' => 7);

        $Arr = ($type == 4 || $type == 3) ? $arr_private : $arr_pub;
        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }


    public function getPayType($a)
    {
        $Arr = array('仅电卡' => 1, '仅移动支付' => 2, '电卡和移动支付' => 3, '现金' => 4, '银联标示银行卡' => 5, '城市一卡通' => 6, '其他' => 7,);

        //return $Arr[$a];
        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }


    public function getPileType($a)
    {
        $Arr = array('直流单枪桩' => 1, '交流单枪桩' => 2, '直流多枪桩' => 3, '交流多枪桩' => 4, '交直流一体桩' => 5, '特斯拉专用桩' => 6);
        //return $Arr[$a];
        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }

    //单位内部 运行状态
    public function getState($a)
    {
        $Arr = array('正常运营' => 1, '停运维护' => 6, '建设中' => 3, '调试中' => 4, '试运行' => 5, '已撤站' => 7);
        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }

    //社会公用 站点建设状态
    public function getBuildState($a)
    {
        $Arr = array('已投运' => 1, '建设中' => 2, '规划中' => 3);
        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }

    public function getType($a)
    {
        $Arr = array('未知' => 0, '社会公用' => 1, '行业专用' => 2, '个人自用' => 3, '单位内部' => 4);

        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }

    public function getPriceType($a)
    {
        $Arr = array('商业用电' => 1, '普通工业用电' => 2, '大工业用电' => 3, '非工业用电' => 4, '居民生活用电' => 5, '非居民照明用电' => 6, '农业生产用电' => 7, '其他用电' => 10);

        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }

    //域范围
    public function getRegionRange($a)
    {
        $Arr = array('二环内' => 1, '二环至三环' => 2, '三环至四环' => 3, '四环至五环' => 4, '五环至六环' => 5, '六环外' => 6);

        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }

    //所属功能区
    public function getFunctionArea($a)
    {
        $Arr = array('居民区' => 1, '工业园区/厂区' => 2, '科技园区' => 3, '风景区' => 4, '商业区' => 5, '公共停车场' => 6,
            '村镇' => 7, '桥下空间' => 8, '科研院所' => 9, '车企4S店' => 10, '酒店宾馆' => 11,
            '企事业单位' => 12, '政府机构' => 13, '金融机构' => 14, '高速服务区' => 15, '地铁站' => 16, '机场' => 17,
            '火车站' => 18, '公交车场站' => 19, '出租车场站' => 20, '环卫车场站' => 21, '医疗机构' => 22, '教育院校' => 23,
            '体育场馆' => 24, '城市公园绿地' => 25, '经营性场所' => 26, '商务办公区' => 27, '产业园区' => 28, '交通枢纽' => 29, '其他' => 0,
        );

        $ret = $this->checkField($Arr, $a);
        return $ret ? $Arr[$a] : false;
    }
    public function checkField($arr, $a)
    {
        return isset($arr[$a]);
    }

}