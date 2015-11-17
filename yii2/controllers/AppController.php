<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\EntryForm;
use app\models\Search;
use  yii\web\Session;

header("content-type:text/html;charset=utf8");
class AppController extends Controller
{
	//职位搜索
	public function actionSearch(){
		$s_type = $_REQUEST['search_type'];
		$kd = $_REQUEST['kd'];
		$key = "zhaopin200"  ;
		//echo $s_type.$kd;
		if(empty($s_type) || empty($kd)){

			$datas = Array(
				'status'=>	100,
				'msg'	=>	'Landing failure!',
				'data'	=>	Array(
								'code'	=>	100028,
								'content'	=> "Product information does not exist"
							)
			);
		}

		$page = empty($_GET['page'])?1:$_GET['page'];//当前页

		$otherCondition = array();//其它搜索条件

		//处理城市条件
		$city = !empty($_GET['city'])?$_GET['city']:'';
		//echo $city;die;
		if($city && $city != '全国'){
			
			$otherCondition['work_city'] = $city;

		}

		//处理工作条件
		$gj = !empty($_GET['gj'])?$_GET['gj']:'';
		if($gj){
			
			$otherCondition['work_year'] = $gj;

		}

		//处理学历条件
		$xl = !empty($_GET['xl'])?$_GET['xl']:'';
		if($xl){
			
			$otherCondition['education'] = $xl;

		}

		//处理工作性质条件
		$gx = !empty($_GET['gx'])?$_GET['gx']:'';
		if($gx){
			
			$otherCondition['work_type'] = $gx;

		}

		//处理发布时间条件
		$st = !empty($_GET['st'])?$_GET['st']:'';
		if($st){
			$today = strtotime(date('Y-m-d'));
			$treeday = strtotime("now")-3*3600*24;
			$weekday = strtotime("now")-7*3600*24;
			$monthday = strtotime("now")-30*3600*24;
			
			if($st == "今天"){
				
				$otherCondition['addtime'] = $today;
			}elseif($st == '一周内'){
				$otherCondition['addtime'] = $weekday;
			}elseif($st == '一月内'){
				$otherCondition['addtime'] = $monthday;
			}else{
				$otherCondition['addtime'] = $treeday;
			}

		}

		

		//处理月薪条件
		$yx = !empty($_GET['yx'])?$_GET['yx']:'';
		if($yx){
			
			if($yx == '2k以下'){

				$otherCondition['salary']['max_salary'] = '2';

			}elseif($yx == '50k以上'){

				$otherCondition['salary']['min_salary'] = '50';

			}else{

				$reg = "#^(\d*)k-(\d*)k#";

				preg_match_all($reg,$yx,$salary);

				$otherCondition['salary']['min_salary'] = $salary[1][0];
				$otherCondition['salary']['max_salary'] = $salary[2][0];

			}


		}

		if($s_type == 1 && !empty($_GET['kd'])){

			$condition = " where positiontype like '%".$_GET['kd']."%' OR rj_name like '%".$_GET['kd']."%'";

		}elseif($s_type == 2  && !empty($_GET['kd'])){
			
			$condition = $_GET['kd'];

		}else{
			$condition = NULL;
		}
		//var_dump($otherCondition);
		$model = new Search();
		$content = $model->get_where_job($condition,$page,$s_type,$otherCondition);
		$count = Yii::$app->db->createCommand($content['sql'])->queryScalar();
		$zhong = $model->get_job($count,$page,$content['condition']);

		$job_list = Yii::$app->db->createCommand($zhong['sql'])->queryAll();
		$data = $model->get_jb($job_list,$zhong['total_page']);
	
		$data['page'] = $page;
		 
		$data['kd'] = $_GET['kd'];

		$data['city'] = $city;
		//print_r($data);die;
		$datas = Array(
				'status'=>	200,
				'msg'	=>	'Landing success!',
				'data'	=>	Array(
								'content'	=> $data
							)
			);

		echo json_encode($datas);
	}


	//创建简历
	public function actionJianli(){
		$jianli = $_REQUEST['jianli'];
		if(empty($jianli)){
			$datas = Array(
				'status'=>	100,
				'msg'	=>	'Landing failure!',
				'data'	=>	Array(
								'code'	=>	100028,
								'content'	=> "error"
							)
			);
		}else{
			//接收session值，判断用户
      		$session = Yii::$app->session;
			$email = $session->get('email');
  			
			if(isset($_REQUEST['email'])){
				$email = $_REQUEST['email'];
			}
			//查找会员表
			$sql = "select * from member where email='$email'";
			$arr= Yii::$app->db->createCommand($sql)->queryAll();
			$aa = "";
			foreach($arr as $k=>$v){
				$aa=$v;
			}
			$info["member"]=$aa; 
			print_r($info);die;
			$member_id=$info['member']['member_id'];
			
			//查找个人简历表
			$sql = "select * from resume where member_id=$member_id";
			$arr2= Yii::$app->db->createCommand($sql)->queryAll();
			$bb="";
			foreach($arr2 as $k=>$v){
				$bb=$v;
			}
			$info["resume"]=$bb;
			if($email){
				if($info['resume']){
					//查找工作经历表
					$sql = "select * from work_experience where member_id=$member_id";
					$arr3= Yii::$app->db->createCommand($sql)->queryAll();
					foreach($arr3 as $k=>$v){
						$cc=$v;
					}
					$info["work"]=$cc;
					//查找教育经历表
					$sql = "select * from edcucation_experience where member_id=$member_id";
					$arr4= Yii::$app->db->createCommand($sql)->queryAll();
					foreach($arr4 as $k=>$v){
						$dd=$v;
					}
					$info["edcucation"]=$dd;
					//展示到简历页面
					$session->set('member_id', $member_id);
	  				//print_r($info);die; 

					//$content = $this->output->get_output();
					//write_file($file_name,$content);
					$datas = Array(
						'status'=>	200,
						'msg'	=>	'Landing success!',
						'data'	=>	Array(
									'content'	=> $info
									)
						);
				}else{
					$datas = Array(
					'status'=>	200,
					'msg'	=>	'Landing success!',
					'data'	=>	Array(
								'content'	=> $info
								)
					);
				}	
			}else{
				$datas = Array(
				'status'=>	100,
				'msg'	=>	'Landing failure!',
				'data'	=>	Array(
								'code'	=>	100028,
								'content'	=> "must login"
							)
			);
			}
			
		}
		//print_r($datas);die;
		echo json_encode($datas);
	}

	//信息完善页面二
	public function actionBasic1(){   
	     $basic = $_REQUEST['basic'];
		if(empty($basic)){
			$datas = Array(
				'status'=>	100,
				'msg'	=>	'Landing failure!',
				'data'	=>	Array(
								'code'	=>	100028,
								'content'	=> "error"
							)
			);
		}else{
        $member_name = $_REQUEST['name'];
        $member_phone = $_REQUEST['phone'];
        $member_id = $_REQUEST['iid'] ;  
    	//条件修改
    	$sql = "update member set member_name='$member_name',$member_phone='$member_phone' where member_id=$member_id";
       	$data = Yii::$app->db->createCommand($sql)->execute();
        
        $education = $_REQUEST['topDegree'];
        $work_years =  $_REQUEST['wokrYear'];
        $city = $_REQUEST['workCity'];
        //数据添加
        $sql = "insert into resume(education,work_years,city)values('$education','$work_years','$city')";
        $data2 = Yii::$app->db->createCommand($sql)->execute();
       	$datas = Array(
					'status'=>	200,
					'msg'	=>	'Landing success!',
					'data'	=>	Array(
								'content'	=> "success"
								)
					);
       	}
		echo json_encode($datas);
    }
        
	//信息完善页面三
    public function actionBasic2(){  
        $basic1 = $_REQUEST['basic1'];
		if(empty($basic1)){
			$datas = Array(
				'status'=>	100,
				'msg'	=>	'Landing failure!',
				'data'	=>	Array(
								'code'	=>	100028,
								'content'	=> "error"
							)
			);
		}else{
            $work_company = $_REQUEST['companyName'];
            $work_position = $_REQUEST['yourPosition'];
            $work_begin = $_REQUEST['startTime'];
            $work_end = $_REQUEST['endTime'];
            $member_id = $_REQUEST['iid'];    
           
		    //数据添加
	        $sql = "insert into work_experience(work_company,work_position,work_begin,work_end,member_id)values('$work_company','$work_position','$work_begin','$work_end',$member_id)";
        	$data = Yii::$app->db->createCommand($sql)->execute();
		    $datas = Array(
					'status'=>	200,
					'msg'	=>	'Landing success!',
					'data'	=>	Array(
								'content'	=> "success"
								)
					);
		}
			echo json_encode($datas);
}
    //信息完善页面四
    public function actionBasic3(){
     	$basic2 = $_REQUEST['basic2'];
		if(empty($basic2)){
			$datas = Array(
				'status'=>	100,
				'msg'	=>	'Landing failure!',
				'data'	=>	Array(
								'code'	=>	100028,
								'content'	=> "error"
							)
			);
		}else{
		    $e_name       = $_REQUEST['schoolName'];
		    $e_xueli      = $_REQUEST['degree'];
		    $e_jineng     = $_REQUEST['yourMajor'];
		    $e_time_end   = $_REQUEST['schoolEnd'];
		    $member_id    = $_REQUEST['iid'];
    	
		    //数据添加
		    $sql = "insert into edcucation_experience(e_name,e_xueli,e_jineng,e_time_end,member_id)values('$e_name','$e_xueli','$e_jineng','$e_time_end',$member_id)";
        	$data = Yii::$app->db->createCommand($sql)->execute();
		    $datas = Array(
					'status'=>	200,
					'msg'	=>	'Landing success!',
					'data'	=>	Array(
								'content'	=> "success"
								)
					);
		}
		echo json_encode($datas);
	}


	//最后保存
	public function actionBasic(){    
	    $basic3 = $_REQUEST['basic3'];
		if(empty($basic3)){
			$datas = Array(
				'status'=>	100,
				'msg'	=>	'Landing failure!',
				'data'	=>	Array(
								'code'	=>	100028,
								'content'	=> "error"
							)
			);
		}else{   
	        //数据修改
	        $self   = $_REQUEST['self'];
		    $member_id    = $_REQUEST['iid'];
		    $sql = "update resume set self='$self' where member_id=$member_id";
       		$data = Yii::$app->db->createCommand($sql)->execute();
   		}
   		echo json_encode($datas);
    }
}
?>