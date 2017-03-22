<?php
namespace Home\Controller;
use Think\Controller;

class ForgetpwdController extends Controller {
	public function index(){
		$this->display('forgetpwd/forgetpwd');
	}

	//AJAX检查手机号码是否正确
    public function checkphone(){
         $phone = $_POST['phone'];
         $Record = M("smsrecord");
         $res = $Record->where("phone=$phone")->find();
         if(!$res){
         	$this->ajaxReturn(0);
         }else{
         	$this->ajaxReturn(1);
         }

    }
    //ajax检查手机号码和验证码是否正确
    public function updatepwd(){
    	$phone = $_POST['phone'];
    	$code = $_POST['code'];
        $Record = M("smsrecord");
        $res = $Record->where("phone=$phone and code=$code")->find();
        if(!$res){
         	$this->ajaxReturn(0);
        }else{
         	$this->ajaxReturn(1);
        }

    }

    //忘记密码流程
    public function find(){
		$user = M('y_user');
	    $record = M('smsrecord');
	    $data = I('post.');
	    if($data['password'] != $data['password1']){
	    	$this->error("您两次输入的密码不一致");
	    	exit;
	    }
	    $arr['password'] =md5($data['password']);
        //$arr['updated_at'] = date("Y-m-d H:i:s",time());
//        var_dump($data['created_at']);
//        exit;
        $phone = $data['phone'];
        //dump($arr);die;
        $res =$user->where("phone=$phone")->save($arr);
        if($res){
        	echo"<script>alert('密码修改成功，请登录！');</script>";
        	$this->display('login/login');
        }
        
	}
	//发送短信验证码
	public function sendSMS(){
        $tpl_id = I("post.tplid"); // 短信模板id：注册 30316 找回密码 30479
        $mobile = I("post.mobile"); // 手机号码
        $data['phone']=$mobile;
        $data['tpl_id']=$tpl_id;
        //  echo $code;
        // die;
        // 检查数据库记录 ,是否在 60 秒内已经发送过一次
        $Record = M("smsrecord");
        $where = array(
            'mobile' => $mobile,
            'tpl_id' => $tpl_id,
        );
        $sms_record = $Record->where($where)->find();
        if( $sms_record && ( (time() - $sms_record['time']) < 60 ) ){
            echo json_encode(array('reason'=>'60秒内不能多次发送'));
            exit();
        }
        // 如果60秒内没有发过，则发送验证码短信（6位随机数字）
        $code = mt_rand(100000, 999999);
        $smsConf = array(
            'key'   => C("SEND_SMS_KEY"), //您申请的APPKEY
            'mobile'    => $mobile, //接受短信的用户手机号码
            'tpl_id'    => $tpl_id, //您申请的短信模板ID，根据实际情况修改
            'tpl_value' =>'#code#=' . $code //您设置的模板变量，根据实际情况修改 '#code#=1234&#company#=聚合数据'
        );
         
        //测试阶段，不发短信，直接设置一个“发送成功” json 字符串
        $content = $this->juhecurl(C("SEND_SMS_URL") ,$smsConf, 1); //请求发送短信
        //$content = json_encode(array('error_code'=>0, 'reason'=>'发送成功'));
        //dump($content);die;
        if($content){
            $result = json_decode($content,true);
            $error_code = $result['error_code'];
            if($error_code == 0){
                // 状态为0，说明短信发送成功
                // 数据库存储发送记录,用于处理倒计时和输入验证，首先要删除旧记录
                $Record->where("phone=$mobile" )->delete();
                //dump($res);die;
                    $data = array(
                        'phone' => $mobile,
                        'tpl_id'=> $tpl_id,
                        'code'=>$code,
                        'time'=>time()
                    );
                    $Record->data($data)->add();
                //echo "短信发送成功,短信ID：".$result['result']['sid'];
            }else{
                //状态非0，说明失败
                echo "短信发送失败(".$error_code.")：".$msg;
            }
        }else{
            //返回内容异常，以下可根据业务逻辑自行修改
            $result['reason'] = '短信发送失败';
        }
        echo $content;
    }
    /**
    * +--------------------------------------------------------------------------
    * 检查填写的手机验证码是否填写正确
    * 可以添加更多字段改造成注册、登录等表单
    *
    * @param string $get.verify 验证码
    * @param string $get.mobile 手机号码
    * @param int $get.tplid 短信模板ID
    * @param int $get.code 手机接收到的验证码
    * +--------------------------------------------------------------------------
    */
    public function checkSmsCode(){
        $tpl_id = I("post.tplid"); // 短信模板id：注册 30316 找回密码 30479
        $mobile = I("post.phone"); // 手机号码
        $code = I("post.code"); // 手机收到的验证码
        // 检查数据库记录，输入的手机验证码是否和之前通过短信 API 发送到手机的一致
        $Record = M("smsrecord");
        $where = array(
            'phone' => $mobile,
            'tpl_id' => $tpl_id,
            'code' => $code,
        );
        $sms_record = $Record->where($where)->find();
        if($sms_record){
            echo json_encode(array('reason'=>'短信验证码核对成功'));
            // 处理后面的程序（如继续登录、注册等）
        }else{
            echo json_encode(array('reason'=>'短信验证码错误'));
        }
    }
    //调用聚合数据接口
    public function juhecurl($url,$params=false,$ispost=0){
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
        curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
        curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
        if( $ispost )
        {
            curl_setopt( $ch , CURLOPT_POST , true );
            curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
            curl_setopt( $ch , CURLOPT_URL , $url );
        }
        else
        {
            if($params){
                curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
            }else{
                curl_setopt( $ch , CURLOPT_URL , $url);
            }
        }
        $response = curl_exec( $ch );
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
        $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
        curl_close( $ch );
        return $response;
    }
}