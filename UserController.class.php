<?php
namespace Home\Controller;
use Think\Controller;

class UserController extends Controller {
    //学生、老师注册页面
    public function regist(){
        
        $this->display('regist/regist');
    }
    
    //企业注册页面
    public function companyregist(){
        $this->display('companyregist/companyregist');
    }
    
    //登录页面
    public function login(){
        $this->display('login/login');
    }
    
    //注册
    public function registdo(){
        if(!isset($_POST['password'])){

          //发送短信
        $tpl_id = I("post.tplid"); // 短信模板id：注册 30316 找回密码 30479 
        $mobile = I("post.mobile"); // 手机号码
        $code = I("post.code");
        //$imgcode = I("post.imgcode");
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
            'key'       => C("SEND_SMS_KEY"), //您申请的APPKEY
            'mobile'    => $mobile, //接受短信的用户手机号码
            'tpl_id'    => '30316', //您申请的短信模板ID，根据实际情况修改
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
                $Record->where("phone=" . $mobile)->delete();
                    $data = array(
                        'phone' => $mobile,
                        'tpl_id'=> $tpl_id,
                        'code'=>$code,
                        'time'=>time()
                    );
                    $Record->add($data);
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
        exit;     
        }
        $user = M('y_user');
        $record = M('smsrecord');
        $code = $_POST['send-sms-code'];
        $imgcode = $_POST['verify'];
        $data = I('post.');
        $phone = $data['phone'];
        $res = $record->where("phone=$phone and code=$code ")->find();
        if(!$res){
            echo"<script>alert('您输入的验证码有误！')</script>";
            $this->display('regist/regist');
            exit;
        }
        $result = $user->where("phone=$phone")->find();
        if($result){
            echo"<script>alert('您的手机号已注册，请登录！');</script>";
            $this->display('login/login');
            exit;
        }
        $data['password'] = md5($data['password']);
        
        $data['created_at'] = date("Y-m-d H:i:s",time());
//        var_dump($data['created_at']);
//        exit;
        $user->add($data);
        echo"<script>alert('注册成功，请登录！');</script>";
        $this->display('login/login');
    }
    
    //登录
    public function logindo(){
        $user = M('y_user');
        $data = I('post.');
        $phone = $data['phone'];
        $password = md5($data['password']);
        $result = $user->where("phone=$phone")->find();
       
       if($password ===$result['password']){
           $_SESSION['user_id'] = $result['id'];
           $_SESSION['user_name'] = $result['real_name'];
           if(!isset($_SESSION['user_name']) || empty($_SESSION['user_name'])){
            $this->display("person/person");exit;
           }
           $this->display("index/index");
       }else{
           echo"<script>alert('密码不正确！');</script>";
           $this->display('login/login');
       }
    }

    /**
    * +--------------------------------------------------------------------------
    * 请求发送短信接口，60秒后才能重新发送
    *
    * @param int $get.tplid 短信模板id
    * @param string $get.mobile 手机号码
    * @return json
    * +--------------------------------------------------------------------------
    */
    public function sendSMS(){
        $tpl_id = I("post.tplid"); // 短信模板id：注册 30316 找回密码 30479
        $mobile = I("post.mobile"); // 手机号码
        $code = I("post.code");
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
                $Record->where("mobile=" . $mobile)->delete();
                    $data = array(
                        'mobile' => $mobile,
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
        $verify = I("post.verify");
        $tpl_id = I("post.tplid"); // 短信模板id：注册 30316 找回密码 30479
        $mobile = I("post.phone"); // 手机号码
        $code = I("post.code"); // 手机收到的验证码
        if(!$this->check_verify($verify)){
            $content = array('resultcode'=>1000, 'reason'=>'验证码填写错误');
            echo json_encode($content);
            exit();
        }
        // 检查数据库记录，输入的手机验证码是否和之前通过短信 API 发送到手机的一致
        $Record = M("smsrecord");
        $where = array(
            'mobile' => $mobile,
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


    //个人资料页面
    public function person(){
        $user = M('y_user');
        $picture = M('y_picture');
        $id = $_SESSION['user_id'];
        $data = $user->where("id=$id")->find();
//        dump($data);die;
        $picid = $data['headpic'];
        $url = $picture->where("id=$picid")->field('url')->find();
//        dump($url);die;
        $data['url'] = $url['url'];
        $this->assign('data',$data);
        $this->display('person/person');
    }
    public function personal()
    {
        define('ROOT_PATH', dirname(THINK_PATH) .'/' ); 
            if(!empty($_FILES['imgfile']['tmp_name'])){  
                $root_path=ROOT_PATH.'Public/upload';
                //echo $root_path;exit;  
                if(!is_dir($root_path)){  
                    mkdir($root_path);  
                }
                //上传图片  
                $upload= new \Think\Upload();// 实例化上传类  
                $upload->maxSize=100048000;// 设置附件上传大小 100M  
                $upload->exts=array();// 设置附件上传类型  
                $upload->rootPath=$root_path.'/avatar'; // 设置附件上传根目录  
                $upload->savePath=''; // 设置附件上传（子）目录  
                $upload->autoSub = true;
                $upload->subName='/';
                $num = mt_rand(0,10000000000).time();
                $upload->saveName="$num";  
                $model = M('y_picture');// 取得成功上传的文件信息
                $user = M('y_user');
                $id = $_SESSION['user_id'];
                $headid = $user->where("id=$id")->field('headPic')->find();
                $head = $headid['headpic'];
                $oldurl = $model->where("id=$head")->field('url')->find();
                // dump($oldurl);die;
                if($oldurl){
                    unlink("./Public/upload/avatar/".$oldurl['url']);
                }
                $info = $upload->upload();
                     //执行对象的上传方法
                //dump($info);die;
                if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
                }else{// 上传成功 获取上传文件信息
                echo $info['savepath'].$info['savename'];
                }
                if($info['imgfile']['savename']!=''){  
                    $infourl='./Public/upload/avatar/'.$info['imgfile']['savename'];
                    $image = new \Think\Image();
                    //dump($info);
                    //dump($infourl);die;
                    $image->open($infourl);//将图片裁剪为400x400并保存为corp.jpg
                    $width = $image->width(); // 返回图片的宽度
                    $height = $image->height(); // 返回图片的高度
                    $iw = $ih = 100;
                    if($iw>$width){
                           $iw = $width;
                       }
                    if($ih>$height){
                         $ih = $height;
                      }
                    if($width>100 || $height>100){
                        $image->thumb($iw, $ih,\Think\Image::IMAGE_THUMB_CENTER)->save($infourl);
                    }
                    //dump($info);die;
                    $_POST['imgurl']='./Public/upload/avatar/'.$info['imgfile']['savename'];
                                $data['url'] = $info['imgfile']['savename'];
                                $imgurl = $data['url'];
                                $data['created_at'] = NOW_TIME;
                                $res =$model->add($data);
                                //$this->success("上传成功");
                            } 
                    //dump($res);die; 
                        }
                    
                    $t['real_name']=I('post.real_name');
                    $t['introduce']=I('post.introduce');
                    $t['email']=I('post.email');
                    $t['headPic'] = $res;
                    //dump($t);die;
                    
                    $res = $user->where("id = $id")->save($t);
                    //dump($t);die;
                    if($res){
                        $this->success('保存成功');
                    }else{
                        $this->error('保存失败');
                    }
    }
        
    public function check_verif(){
        $imgval = $_POST;
        $verify = new \Think\Verify();
        if(!$verify->check($_POST['imgval']))
        {
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }
}


