<?php

namespace app\controllers;


use app\models\app\db\BillR;
use app\models\Bill;
use app\models\Carr;
use app\models\Pile;
use app\models\Site;
use app\modules\facilities\models\Car;
use Yii;
use app\models\common\Common;
use app\models\common\ErrorCode;
use app\models\Reward;
use yii\base\DynamicModel;
use yii\db\Exception;
use app\models\Upload;
use app\models\Certificate;



/**
 * 运营奖补
 * @package app\controllers
 * @author zhangkai
 */

class RewardController extends BaseController
{

    public $noNeedLoginAction = [
    ];
    //不许要安全校验的接口
    public $noValidateAction = [
    ];


    private $objReader;

    //列表
    public function actionApplylist()
    {
        $request = yii::$app->request;
        $userInfo = $this->userInfo;
        $requestData = $request->get();
        $rewardModel = new Reward();

        $rewardList = $rewardModel->getSiteinfo($requestData,$userInfo);
        Common::outJsonResult(ErrorCode::SUCCESS,$rewardList);
    }
    //申报请求
    public function actionApply()
    {
        $request = yii::$app->request;
        $site_ids = $request->post('site_ids');
        $model = DynamicModel::validateData(compact('site_ids'),[
            ['site_ids','required','message'=>'站点id不能为空'],
        ]);
        if($model->hasErrors()) {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        $userInfo = $this->userInfo;
        $rewardModel = new Reward();
        $result = $rewardModel->applySite($site_ids,$userInfo,2);
        if(is_array($result)) {
            Common::outJsonResult(ErrorCode::SUCCESS,$result);
        }else{
            Common::outJsonResult(ErrorCode::EXCEPTION_OCCURE);
        }
    }
    //申报批复与已补贴录入
    public function actionReply()
    {
        $request = yii::$app->request;
        $type = $request->post('type');
        $reply_time = $request->post('reply_time');
        $file_url = $request->post('file_url');

        $model = DynamicModel::validateData(compact('type','reply_time','file_url'),[
            ['type','required','message'=>'类型不能为空'],
            ['reply_time','required','message'=>'批复日期	不能为空'],
            ['file_url','required','message'=>'场站文件url不能为空'],
        ]);
        if($model->hasErrors()) {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        if($type == 1)
        {
            $subsidy_apply = 6;
        }elseif ($type == 2)
        {
            $subsidy_apply = 4;
        }else{
            $subsidy_apply = 3;
        }
        $userInfo = $this->userInfo;

        $site_excel = file_get_contents($file_url);
        $path = yii::$app->basePath;
        file_put_contents($path . "/runtime/upload_excel/" . basename($file_url), $site_excel);

        $site_excel = $path . "/runtime/upload_excel/" . basename($file_url);

        ini_set('max_execution_time', '0');

        $this->excelStatic($site_excel);
        $objReader = $this->objReader;

        try {
            //判断文件是否能正常读取
            $arr_zip=$this->getArrZip($site_excel);
            $obj = $objReader->load($site_excel);
            if (empty($arr_zip['filename'])){
                $err_msg[] = "读取站信息excel文件失败,请检查表格填写格式 ";
                $sitedata['err_msg'] = $err_msg;
                Common::outJsonResult(ErrorCode::EXCEPTION_OCCURE);
            }
        } catch (Exception $e) {
            $err_msg[] = "读取站信息excel文件失败 ";
        }

        $sheet = $obj->getActiveSheet();
        $allRow = $sheet->getHighestRow();
        $allCol = $sheet->getHighestColumn();
        $checkClo = ['A'=>'carr_name','B'=>'site_name','C'=>'carr_site_code',]; //所需字段
        $excelArr = [];
        $num=0;
        for ($j = 2; $j <= $allRow; $j++) {
            foreach ($checkClo as $k=>$v){
                $value = $sheet->getCell($k . $j)->getValue();
                $excelArr[$j][$v] = $value;
            }
            $num++;
        }
        $siteModel = new Site();
        $site_ids = '';
        $modelResult = ['fail'=>0];
        foreach ($excelArr as $k=>$v)
        {
            $siteInfo = $siteModel->getAllSite('site_id',['site_name'=>$v['site_name'],'carr_site_code'=>$v['carr_site_code']])->asArray()->one();
            if(!empty($siteInfo))
            {
                $site_ids = $site_ids . $siteInfo['site_id'] . ',';
            }else{
                $modelResult['fail']++;
            }
        }
        $site_ids = trim($site_ids,',');
        //全部都不存在直接返回
        if(empty($site_ids))
        {
            $result =  ['successNum'=>0,'failNum'=>$num];
            Common::outJsonResult(ErrorCode::SUCCESS,$result);
        }

        $rewardModel = new Reward();
        $result = $rewardModel->applySite($site_ids,$userInfo,$subsidy_apply,$file_url,$reply_time,[],$modelResult);
        if(is_array($result)) {
            Common::outJsonResult(ErrorCode::SUCCESS,$result);
        }else{
            Common::outJsonResult(ErrorCode::EXCEPTION_OCCURE);
        }

    }
    //调价
    public function actionChangeprice()
    {
        $request = yii::$app->request;
        $site_ids = $request->post('site_ids','');
        $type = $request->post('type',1);
        $service_price = $request->post('service_price','');

        $price = $request->post('price','');
        $tip_price = $request->post('tip_price','');
        $peak_price = $request->post('peak_price','');
        $flat_price = $request->post('flat_price','');
        $valley_price = $request->post('valley_price','');

        $total_price = $request->post('total_price','');

        $totaltip_price = $request->post('totaltip_price','');
        $totalpeak_price = $request->post('totalpeak_price','');
        $totalflat_price = $request->post('totalflat_price','');
        $totalvalley_price =  $request->post('totalvalley_price','');
        //共同的必填项
        $rule = [
            ['site_ids','required','message'=>'站点id不能为空'],
            ['type','required','message'=>'调价类型不能为空'],
            ['type','in','range' => ['1','2','3'],'message'=>'type参数错误'],
        ];
        //平价必填项
        $rule1 = [
            ['total_price','required','message'=>'总充电总价不能为空'],
//            ['total_price', 'compare', 'compareValue' => 1.3, 'operator' => '<=','message'=>'服务费+电费 最高总单价不得大于1.3元/度'],
        ];
        //分时必填项
        $rule2 = [
            ['totaltip_price','required','message'=>'尖时总价不能为空'],
//            ['totaltip_price', 'compare', 'compareValue' => 1.3, 'operator' => '<=','message'=>'服务费+电费 最高总单价不得大于1.3元/度'],
            ['totalpeak_price','required','message'=>'峰时总价不能为空'],
//            ['totalpeak_price', 'compare', 'compareValue' => 1.3, 'operator' => '<=','message'=>'服务费+电费 最高总单价不得大于1.3元/度'],
            ['totalflat_price','required','message'=>'平时总价不能为空'],
//            ['totalflat_price', 'compare', 'compareValue' => 1.3, 'operator' => '<=','message'=>'服务费+电费 最高总单价不得大于1.3元/度'],
            ['totalvalley_price','required','message'=>'谷时总价不能为空'],
//            ['totalvalley_price', 'compare', 'compareValue' => 1.3, 'operator' => '<=','message'=>'服务费+电费 最高总单价不得大于1.3元/度'],
        ];
        if($type == 1)
        {
            foreach ($rule1 as $k=>$v)
            {
                $rule [] = $v;
            }
        }elseif ($type == 2)
        {
            foreach ($rule2 as $k=>$v)
            {
                $rule [] = $v;
            }
        }else{
            foreach ($rule1 as $k=>$v)
            {
                $rule [] = $v;
            }
            foreach ($rule2 as $k=>$v)
            {
                $rule [] = $v;
            }
        }

        $model = DynamicModel::validateData(compact('site_ids','type','service_price','price','tip_price','peak_price','flat_price','valley_price'
            ,'total_price','totaltip_price','totalpeak_price','totalflat_price','totalvalley_price'),$rule);

        if($model->hasErrors())
        {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        $site_ids = explode(',',$site_ids);
        $siteModel = new Site();
        $siteList = $siteModel->getAllSite('',['site_id'=>$site_ids])->asArray()->all();
        $rewardModel = new Reward();
        $userInfo = $this->userInfo;
        $changeResult = ['successNum'=>0,'failNum'=>0];
        foreach ($siteList as $k=>$v)
        {
            $site_ids = $v['site_id'];
            if($type == 1)
            {
                $data['price_method']  = 1;
                $data['service_price'] = $service_price;
                $data['price'] = $price;
                $data['total_price'] = $total_price;
                $result = $rewardModel->applySite($site_ids,$userInfo,5,'','',$data);
            }else
            {
                $data['price_method']  = 2;
                $data['service_price'] = $service_price;
                $price_arr['AG'] = '11:00-13:00/16:00-17:00';
                $price_arr['AH'] = $tip_price;
                $price_arr['AI'] = '10:00-15:00/18:00-21:00';
                $price_arr['AJ'] = $peak_price;
                $price_arr['AK'] = '07:00-10:00/15:00-18:00/21:00-23:00';
                $price_arr['AL'] = $flat_price;
                $price_arr['AM'] = '23:00-07:00';
                $price_arr['AN'] = $valley_price;
                if(!empty($tip_price) && !empty($peak_price) && !empty($flat_price) && !empty($flat_price))
                {
                    $price_json = $this->getJsonPrice($price_arr);
                }else{
                    $price_json = '';
                }
                $price_arr['AH'] = $totaltip_price;
                $price_arr['AJ'] = $totalpeak_price;
                $price_arr['AL'] = $totalflat_price;
                $price_arr['AN'] = $totalvalley_price;
                $totalprice_json = $this->getJsonPrice($price_arr);
                $data['price'] = $price_json;
                $data['total_price'] = $totalprice_json;
                $result = $rewardModel->applySite($site_ids,$userInfo,5,'','',$data);
            }
            $changeResult['successNum'] = $changeResult['successNum'] + $result['successNum'];
            $changeResult['failNum'] = $changeResult['failNum'] + $result['failNum'];
        }

        Common::outJsonResult(ErrorCode::SUCCESS,$changeResult);
    }
    //导出充电设施明细表
    public function actionExplordetail()
    {
        $request = yii::$app->request;
        $access_id = $request->get('access_id','');
        $site_ids = $request->get('site_ids','');
        $is_button = $request->get('is_read',1);
        $model = DynamicModel::validateData(compact('access_id'),[
            ['access_id','required','message'=>'接入证明id不能为空'],
            ['access_id','exist','targetClass' => '\app\models\db\AccessproveR','message'=>'接入证明id不存在'],
        ]);
        if($model->hasErrors()) {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        $pileModel = new Pile();
        $rewardModel = new Reward();
        $accessDetail = $rewardModel->getAccessdetail($access_id);
        if(empty($site_ids))
        {
            $site_id  = $accessDetail['finish'];
        }else{
            $site_id = explode(',',$site_ids);
        }
        $pileList = $pileModel->getPdfdata($site_id,$is_button);
        if(empty($pileList))
        {
            Common::outJsonResult(ErrorCode::EXCEPTION_OCCURE);
        }
        $baseDir = yii::$app->basePath;
        require_once($baseDir . '/models/tcpdf/tcpdf.php');
        $pdf = new \MYPDF('L', \PDF_UNIT, 'A3', true, 'UTF-8', false);
        $userInfo = $this->userInfo;
        $carr_name = '';
        if($userInfo['role'] == 1)
        {
            $carrModel = new Carr();
            $carrInfo = $carrModel->getCarrinfo($userInfo['role_depid']);
            $carr_name = empty($carrInfo) ? '' : $carrInfo['carr_name'];    //后续取全称
        }else{
            $carrModel = new Carr();
            $carrInfo = $carrModel->getCarrinfo($accessDetail['uid']);
            $carr_name = empty($carrInfo) ? '' : $carrInfo['carr_name'];    //后续取全称
        }
        $pdf->is_button = $is_button;
        $pdf->SetCreator(\PDF_CREATOR);
        $pdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
        $pdf->SetMargins(\PDF_MARGIN_LEFT, \PDF_MARGIN_TOP, \PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(\PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(\PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, \PDF_MARGIN_BOTTOM);
        $pdf->SetTitle($carr_name.'充电设施建设明细表');
        $pdf->SetSubject($carr_name.'充电设施建设明细表');
        $pdf->SetKeywords('充电, 明细, 充电明细');
        $pdf->SetFont('stsongstdlight', 'B', 10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();
        $pdf->setPageMark();

        $dataStr = $rewardModel->getdataStr($accessDetail['finished_time'],$carr_name,$pileList);
        $html = <<<EOD
        $dataStr
EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->lastPage();
        $pdf->Output('充电设施建设明细表.pdf', 'D');

    }
    //接入证明申请
    public function actionCreateaccess()
    {
        $request = yii::$app->request;
        $userInfo = $this->userInfo;
        $site_ids = $request->post('site_ids','');
        $type = $request->post('type',1);
        $model = DynamicModel::validateData(compact('site_ids'),[
            ['site_ids','required','message'=>'站点id不能为空'],
        ]);
        if($model->hasErrors()) {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        $rewardModel = new Reward();
        $result = $rewardModel->createAsscess($site_ids,$userInfo,$type);
        if(is_array($result))
        {
            Common::outJsonResult(ErrorCode::SUCCESS,$result);
        }else{
            Common::outJsonResult(ErrorCode::EXCEPTION_OCCURE);
        }
    }
    //申请接入证明历史
    public function actionAccesslist()
    {
        $request = yii::$app->request;
        $access_type = $request->get('access_type',3);
        $access_no = $request->get('access_no','');
        $page = $request->get('page',1);
        $page_size = $request->get('page_size',10);
        $userInfo = $this->userInfo;
        $carr_id = $request->get('carr_id',0);
        $start_time = $request->get('start_time','');
        $end_time = $request->get('end_time','');
        $type = $request->get('type',1);

        $rule = [
            ['type','in','range' => ['1','2'],'message'=>'type参数错误'],
        ];
        $model = DynamicModel::validateData(compact('type'),$rule);
        if($model->hasErrors())
        {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        $rewardModel = new Reward();
        $accessList = $rewardModel->getAccesslist(1,$access_type,$access_no,$page,$page_size,$userInfo,$carr_id,$start_time,$end_time,$type);
        Common::outJsonResult(ErrorCode::SUCCESS,$accessList);

    }
    //接入证明详情
    public function actionAccessdetail()
    {
        $request = yii::$app->request;
        $userInfo = $this->userInfo;
        $access_id = $request->post('access_id','');
        $model = DynamicModel::validateData(compact('access_id'),[
            ['access_id','required','message'=>'接入证明id不能为空'],
            ['access_id','exist','targetClass' => '\app\models\db\AccessproveR','message'=>'接入证明id不存在'],
        ]);
        if($model->hasErrors()) {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        $rewardModel = new Reward();
        $accessDetail = $rewardModel->getAccessdetail($access_id);
        $data['input_type'] = $request->post('input_type','');
        $data['input_value'] = $request->post('input_value','');
        $data['access_state'] = $request->post('access_state','');
        $data['page'] = $request->post('page','');
        $data['page_size'] = $request->post('page_size','');
        $data['type']= 6;
        $data['site_id'] = $accessDetail['all_siteid'];
        //审核状态按接入证明的状态搜索
        if(!empty($data['access_state']))
        {
            if($data['access_state'] == 1)
            {
                $data['site_id'] = $accessDetail['already'];
            }
            else if ($data['access_state'] == 2)
            {
                $data['site_id'] = $accessDetail['finish'];
            }else if($data['access_state'] == 3)
            {
                $data['site_id'] = $accessDetail['refused'];
            }
        }
        unset($data['access_state']);
        $siteList = $rewardModel->getSiteinfo($data,$userInfo);
        //不读站点最新开具状态 读历史
        foreach ($siteList['list'] as $k=>$v)
        {
            if(in_array($v['site_id'],$accessDetail['already']))
            {
                $siteList['list'][$k]['access_state'] = 1;
            }else if (in_array($v['site_id'],$accessDetail['finish']))
            {
                $siteList['list'][$k]['access_state'] = 2;
            }else if(in_array($v['site_id'],$accessDetail['refused']))
            {
                $siteList['list'][$k]['access_state'] = 3;
            }
        }
        $accessDetail['detail'] = $accessDetail;
        $accessDetail['site_info'] = $siteList;
        Common::outJsonResult(ErrorCode::SUCCESS,$accessDetail);
    }
    //不达标与开具证明操作
    public function actionAccessoperation()
    {
        $request = yii::$app->request;
        $type = $request->post('type','');
        $access_id = $request->post('access_id','');
        $site_ids = $request->post('site_ids','');
        $userInfo = $this->userInfo;
        $model = DynamicModel::validateData(compact('access_id','site_ids','type'),[
            ['access_id','required','message'=>'接入证明id不能为空'],
            ['site_ids','required','message'=>'站点不能为空'],
            ['type','required','message'=>'操作类型不能为空'],
            ['type','in','range' => ['2','3'],'message'=>'操作类型不正确'],
            ['access_id','exist','targetClass' => '\app\models\db\AccessproveR','message'=>'接入证明id不存在'],
        ]);
        if($model->hasErrors()) {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }

        $rewardModel = new Reward();
        $access_info = $rewardModel->getAccessdetail($access_id);
        if(intval($access_info['type']) == 4){
            $result = $rewardModel->oprJsbtAccessdetail($userInfo,$type,$access_id,$site_ids);
        }else{
            $result = $rewardModel->oprAccessdetail($userInfo,$type,$access_id,$site_ids);
        }
        if(is_array($result))
        {
            Common::outJsonResult(ErrorCode::SUCCESS,$result);
        }else if($result){
            Common::outJsonResult(ErrorCode::SUCCESS);
        }else{
            Common::outJsonResult(ErrorCode::EXCEPTION_OCCURE);
        }
    }
    //接入下载
    public function actionAccessexplor()
    {
        $request = yii::$app->request;
        $access_id = $request->get('access_id','');
        $site_ids = $request->get('site_ids','');
        $type = $request->get('type',1);
        $is_button = $request->get('is_read',1);
        $model = DynamicModel::validateData(compact('access_id'),[
            ['access_id','required','message'=>'接入证明id不能为空'],
            ['access_id','exist','targetClass' => '\app\models\db\AccessproveR','message'=>'接入证明id不存在'],
        ]);
        if($model->hasErrors()) {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        $userInfo = $this->userInfo;
        $rewardModel = new Reward();
        $accessDetail = $rewardModel->getAccessdetail($access_id);
        if($type==1){
            $data['type'] = 6;
            if(empty($site_ids))
            {
                $data['site_id'] = $accessDetail['finish'];
            }else{
                $data['site_id'] = explode(',',$site_ids);
            }
            $siteList = $rewardModel->getSiteinfo($data,$userInfo,1);
            $this->publicView($accessDetail,$siteList,$is_button);
        }elseif($type == 2){
            if(empty($site_ids))
            {
                $site_id = $accessDetail['finish'];
            }else{
                $site_id = explode(',',$site_ids);
            }
            $siteModel = new Site();
            $siteList = $siteModel->getsitePile($site_id);
            $this->internalView($accessDetail,$siteList,$is_button);
        }elseif($type == 4){
            if(empty($site_ids))
            {
                $site_id = $accessDetail['finish'];
            }else{
                $site_id = explode(',',$site_ids);
            }
            $siteModel = new Site();
            $siteList = $siteModel->getsitePileOrderByPower($site_id);
            $this->jsbtView($accessDetail,$siteList,$is_button);
        }
        exit();
    }

    //单位内部接入证明下载
    private function jsbtView($accessDetail,$siteData,$is_button)
    {
        $baseDir = yii::$app->basePath;
        require_once($baseDir . '/models/tcpdf/tcpdf.php');
        $pdf = new \JSBTPDF('P', \PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->writeText = $accessDetail['access_no'];
        $pdf->is_button = $is_button;
        $pdf->SetCreator(\PDF_CREATOR);
        $pdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
        $pdf->SetMargins(\PDF_MARGIN_LEFT, 15, \PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(\PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(\PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, \PDF_MARGIN_BOTTOM);
        $pdf->SetTitle('确  认  函');
        $pdf->SetSubject('确  认  函');
        $pdf->SetKeywords('接入证明, 接入, 证明');
        $pdf->SetFont('droidsansfallback', '', 14);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();
        $pdf->setPageMark();

        $carrModel = new Carr();
        $pileCount = 0;
        $siteCount = count($siteData);
        $pilekwSum = 0;
        foreach ($siteData as $k=>$v) {
            if ($k == 0) {
                $carrInfo = $carrModel->getCarrinfo($v['carr_id']);
            }
            /*foreach ($v['pile_info'] as $key=>$val)
            {
                $pileCount = $pileCount + 1;
                $pilekwSum = $pilekwSum + $val['rated_power'];
            }*/
        }

        $data = [
            'finished_time'=>date('Y年m月d日',$accessDetail['finished_time']),
            'carr_name' =>empty($carrInfo)? '' :$carrInfo['carr_name'],
            'company_name' =>empty($carrInfo)? '' :$carrInfo['company_name'], //全称
            'userinfo' => $this->userInfo,
            'site_list' => $siteData,
            'pile_count' => $pileCount,
            'site_count' => $siteCount,
            'pilekw_sum' => $pilekwSum
        ];
        $dataStr = $this->renderPartial('jsbtlist',['data'=>$data],true);

        $html = <<<EOD
            $dataStr
EOD;

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->lastPage();
        $pdf->Output('确认函.pdf', 'I');
    }

    //单位内部导出项目申报表
    public function actionExplortable()
    {
        $request = yii::$app->request;
        $access_id = $request->get('access_id','');
        $site_ids = $request->get('site_ids','');
        $is_button = $request->get('is_read',1);
        //导出并记录
        $model = DynamicModel::validateData(compact('access_id'),[
            ['access_id','required','message'=>'接入证明id不能为空'],
            ['access_id','exist','targetClass' => '\app\models\db\AccessproveR','message'=>'接入证明id不存在'],
        ]);
        if($model->hasErrors()) {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        $rewardModel = new Reward();
        $accessDetail = $rewardModel->getAccessdetail($access_id);
        if(empty($site_ids))
        {
            $site_id  = $accessDetail['finish'];
        }else{
            $site_id = explode(',',$site_ids);
        }
        $pileModel = new Pile();
        $pileData = $pileModel->pilesiteData($site_id);
        //记录时间
        $rewardModel = new Reward();
        $rewardModel->addInternal($site_id);
        $this->projectView($accessDetail['finished_time'],$pileData,$is_button);
    }

    //运营补贴接入证明下载
    private function publicView($accessDetail,$siteList,$is_button)
    {
        $baseDir = yii::$app->basePath;
        require_once($baseDir . '/models/tcpdf/tcpdf.php');
        $pdf = new \ACCESSPDF('P', \PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->writeText = $accessDetail['access_no'];
        $pdf->is_button = $is_button;
        $pdf->SetCreator(\PDF_CREATOR);
        $pdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
        $pdf->SetMargins(\PDF_MARGIN_LEFT, 15, \PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(\PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(\PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, \PDF_MARGIN_BOTTOM);
        $pdf->SetTitle('关于北京市申领运营奖补社会公用充电设施接入市级平台的证明');
        $pdf->SetSubject('关于北京市申领运营奖补社会公用充电设施接入市级平台的证明');
        $pdf->SetKeywords('接入证明, 接入, 证明');
        $pdf->SetFont('droidsansfallback', '', 14);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();
        $pdf->setPageMark();
        $site_num = count($siteList['list']);
        $pile_num = 0;
        $ac_single_pile_num = 0;
        $dc_single_pile_num = 0;
        $ac_more_pile_num = 0;
        $dc_more_pile_num = 0;
        $adc_pile_num = 0;
        $carrInfo = [];
        $carrModel = new Carr();
        foreach ($siteList['list'] as $k=>$v)
        {
            if($k == 0){
                $carrInfo = $carrModel->getCarrinfo($v['carr_id']);
            }
            $ac_single_pile_num+=$v['ac_single_pile_num'];
            $dc_single_pile_num+=$v['dc_single_pile_num'];
            $ac_more_pile_num+=$v['ac_more_pile_num'];
            $dc_more_pile_num+=$v['dc_more_pile_num'];
            $adc_pile_num+=$v['adc_pile_num'];
        }
        $pile_num = $pile_num = $ac_single_pile_num +  $dc_single_pile_num + $ac_more_pile_num + $dc_more_pile_num + $adc_pile_num;
        $data = [
            'finished_time'=>date('Y年m月d日',$accessDetail['finished_time']),
            'site_num'=>$site_num,
            'ac_single_pile_num'=>$ac_single_pile_num,
            'dc_single_pile_num'=>$dc_single_pile_num,
            'ac_more_pile_num'=>$ac_more_pile_num,
            'dc_more_pile_num'=>$dc_more_pile_num,
            'adc_pile_num'=>$adc_pile_num,
//            'year'=>date('Y'),
//            'month'=>date('m'),
//            'day'=>date('d'),
            'pile_num'=> $pile_num,
            'carr_name' =>empty($carrInfo)? '' :$carrInfo['carr_name'],
            'company_name' =>empty($carrInfo)? '' :$carrInfo['company_name'], //全城
            'site_list' => $siteList['list']
        ];
        $dataStr = $this->renderPartial('list',['data'=>$data],true);

        $html = <<<EOD
            $dataStr
EOD;

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->lastPage();
        $pdf->Output('关于北京市申领运营奖补社会公用充电设施接入市级平台的证明.pdf', 'I');
    }

    //导出项目申报表
    private function projectView($finished_time,$pileData,$is_button)
    {
        $baseDir = yii::$app->basePath;
        require_once($baseDir . '/models/tcpdf/tcpdf.php');
        $pdf = new \PROJECTPDF('L', \PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(\PDF_CREATOR);
        $pdf->is_button = $is_button;
        $pdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
        $pdf->SetMargins(\PDF_MARGIN_LEFT, 15, \PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(\PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(\PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, \PDF_MARGIN_BOTTOM);
        $pdf->SetTitle('项目申报表');
        $pdf->SetSubject('项目申报表');
        $pdf->SetKeywords('项目申报表, 项目, 申报表');
        $pdf->SetFont('droidsansfallback', '', 11);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();
        $pdf->setPageMark();

        $dataStr = $this->renderPartial('projectlist',['data'=>$pileData,'finished_time'=>$finished_time],true);

        $html = <<<EOD
            $dataStr
EOD;

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->lastPage();
        $pdf->Output('关于北京市申领运营奖补社会公用充电设施接入市级平台的证明.pdf', 'I');
    }

    //单位内部接入证明下载
    private function internalView($accessDetail,$siteData,$is_button)
    {
        $baseDir = yii::$app->basePath;
        require_once($baseDir . '/models/tcpdf/tcpdf.php');
        $pdf = new \INTERNALACCESSPDF('P', \PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->writeText = $accessDetail['access_no'];
        $pdf->is_button = $is_button;
        $pdf->SetCreator(\PDF_CREATOR);
        $pdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
        $pdf->SetMargins(\PDF_MARGIN_LEFT, 15, \PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(\PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(\PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, \PDF_MARGIN_BOTTOM);
        $pdf->SetTitle('关于北京市充电设施运营企业单位内部公用充电设施接入市级平台的证明');
        $pdf->SetSubject('关于北京市充电设施运营企业单位内部公用充电设施接入市级平台的证明');
        $pdf->SetKeywords('接入证明, 接入, 证明');
        $pdf->SetFont('droidsansfallback', '', 14);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();
        $pdf->setPageMark();

        $carrModel = new Carr();
        $pileCount = 0;
        $siteCount = count($siteData);
        $pilekwSum = 0;
        foreach ($siteData as $k=>$v) {
            if ($k == 0) {
                $carrInfo = $carrModel->getCarrinfo($v['carr_id']);
            }
            foreach ($v['pile_info'] as $key=>$val)
            {
                $pileCount = $pileCount + 1;
                $pilekwSum = $pilekwSum + $val['rated_power'];
            }
        }
        $data = [
            'finished_time'=>date('Y年m月d日',$accessDetail['finished_time']),
            'carr_name' =>empty($carrInfo)? '' :$carrInfo['carr_name'],
            'company_name' =>empty($carrInfo)? '' :$carrInfo['company_name'], //全称
            'site_list' => $siteData,
            'pile_count' => $pileCount,
            'site_count' => $siteCount,
            'pilekw_sum' => $pilekwSum
        ];
        $dataStr = $this->renderPartial('internallist',['data'=>$data],true);

        $html = <<<EOD
            $dataStr
EOD;

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->lastPage();
        $pdf->Output('关于北京市申领运营奖补社会公用充电设施接入市级平台的证明.pdf', 'I');
    }

    //返回某批次时间段
    public function actionGetbatchtime()
    {
        $request = yii::$app->request;
        $batch_type = $request->post('batch_type',1);


        //导出并记录
        $model = DynamicModel::validateData(compact('batch_type'),[
            ['batch_type', 'in', 'range' => [0,1],'message'=>'批次类型有误']


        ]);
        if($model->hasErrors()) {
            $errors = $model->getErrors();
            Common::outJsonResult(ErrorCode::PARAM_WRONG,$errors);
        }
        $rewardModel = new Reward();
        $rewardBatchInfo = $rewardModel->getBatchTime($batch_type);
        if(!empty($rewardBatchInfo)) {
            Common::outJsonResult(ErrorCode::SUCCESS,$rewardBatchInfo);
        }else{
            Common::outJsonResult(ErrorCode::EXCEPTION_OCCURE,['error'=>'']);
        }



    }

    //拼接尖峰平谷价格数组
    private function getJsonPrice($price_arr)
    {
        //var_dump($price_arr);die;
        $price_new = [];
        if (!empty($price_arr['AG'])) {
            $section = '尖';
            $sharp_price = round($price_arr['AH'], 4);
            $sharp_price_arr = $this->getPriceMember($price_arr['AG'], $section, $sharp_price);
            $price_new['prices'][] = $sharp_price_arr;
        }
        if (!empty($price_arr['AI'])) {
            $section = '峰';
            $peak_price = round($price_arr['AJ'], 4);
            $peak_price_arr = $this->getPriceMember($price_arr['AI'], $section, $peak_price);
            $price_new['prices'][] = $peak_price_arr;
        }
        if (!empty($price_arr['AK'])) {
            $section = '平';
            $flat_price = round($price_arr['AL'], 4);
            $flat_price_arr = $this->getPriceMember($price_arr['AK'], $section, $flat_price);
            $price_new['prices'][] = $flat_price_arr;
        }
        if (!empty($price_arr['AM'])) {
            $section = '谷';
            $valley_price = round($price_arr['AN'], 4);
            $valley_price_arr = $this->getPriceMember($price_arr['AM'], $section, $valley_price);
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

    //导出phpexcel库
    private function excelStatic($path)
    {

        $ext = mb_substr($path, mb_strrpos($path, '.') + 1);
        $baseDir = yii::$app->basePath;
        require_once($baseDir . '/models/php_excel/PHPExcel.php');
        require_once($baseDir . '/models/php_excel/PHPExcel/Reader/Excel2007.php');
        if ($ext == 'xlsx') {
            $phpExcel = new \PHPExcel();
            $objReader = new \PHPExcel_Reader_Excel2007($phpExcel);
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            if(!$objReader->canRead($path)){
                $objReader = \PHPExcel_IOFactory::createReader('Excel5');
            }
            $this->objReader = $objReader;
        } else if ($ext == 'xls') {
            $phpExcel = new \PHPExcel();
            $this->objReader = $phpExcel;
        }
    }
    //判断上传的excel文件是否可以正常读取
    private function getArrZip($uploadUrl){
        $zipClass = \PHPExcel_Settings::getZipClass();
        $zip = new $zipClass;
        $zip->open($uploadUrl);
        $arr_zip=(array) $zip;
        return $arr_zip;
    }

    //异步生成订单pdf
    public function actionCertificatepdf()
    {

        $data = Yii::$app->redis->rpop('billpdf_generate');
        if(empty($data))
        {
            exit();
        }
        $firstData = unserialize($data);

        $cert_ids = $firstData['cert_id'];
        $carr_id = $firstData['carr_id'];
        $certModel = new Certificate();
        $list = $certModel->download($cert_ids);


        //定义下载内容
        $baseDir = yii::$app->basePath;
        require_once($baseDir . '/models/tcpdf/tcpdf.php');
        $pdf = new \MYPDF('L', \PDF_UNIT, 'A3', true, 'UTF-8', false);

        $pdf->SetCreator(\PDF_CREATOR);
        $pdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
        $pdf->SetMargins(\PDF_MARGIN_LEFT, \PDF_MARGIN_TOP, \PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(\PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(\PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, \PDF_MARGIN_BOTTOM);
        $pdf->SetTitle('日常考核奖励项目申请明细表');
        $pdf->SetSubject('日常考核奖励项目申请明细表');
        $pdf->SetKeywords('充电, 明细, 充电明细');
        $pdf->SetFont('stsongstdlight', 'B', 10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->AddPage();
        $pdf->setPageMark();


        $table = "<br/>
        <h3 align=\"center\">日常考核奖励项目申请明细表</h3>
        <br/>
        <table border=\"1\" align=\"center\">
        <tr>
          <th>序号</th>
          <th>申报单位名称</th>
          <th>充电订单号</th>
          <th>充电站名称</th>
          <th>充电站编码</th>
          <th>设备编码</th>
          <th>充电设备接口编码</th>
          <th>额定功率</th>
          <th>充电开始时间</th>
          <th>充电结束时间</th>
          <th>充电时长</th>
          <th>电表总起值</th>
          <th>电表总止值</th>
          <th>累计充电量</th>
          <th>有效充电量</th>
          <th>总电费</th>
          <th>总服务费</th>
          <th>累计总金额</th>
          <th>电费凭证编号</th>
        </tr>";

        $dataStr = $certModel->getdataStr($list);
        $table = $table.$dataStr.'</table>';
        $html = <<<EOD
        <style>
	td {
		text-align: center;
	}
</style>
        $table
EOD;
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->lastPage();


        $dirPath = $baseDir.'/runtime/upload_pdf/';

        if(!is_dir($dirPath))
        {
            mkdir($dirPath,0777,true);
        }
        $filename = uniqid().time().'.'.'pdf';
        //保存到本地
        $localPath = $dirPath.$filename;
        $pdf->Output($localPath, 'F');
        //上传oss
        $uploadModel = new Upload();
        $ossUrl = $uploadModel->uploadLocalFile($localPath,$filename);
        //记录文件生成状态与文件地址
        $certModel->saveFilestate($cert_ids,$ossUrl);
        exit;
    }


}
