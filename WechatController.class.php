<?php
namespace Home\Controller;
use Think\Controller;
class WechatController extends Controller{

    private $appid = 'wx259ee7b4ec195882';
    private $appsecret = '42265c13e6ea92d3fdf5ec85142b710f';
    //第一步：用户同意授权，获取code
    function accept(){
        //这个链接是获取code的链接 链接会带上code参数
        $REDIRECT_URI = "http://www.51yiqixiao.com/index.php/home/wechat/getCode";
        $REDIRECT_URI = urlencode($REDIRECT_URI);
        //dump($REDIRECT_URI);die;
        $scope = "snsapi_userinfo";
        $state = md5(mktime());
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appid}&redirect_uri=".$REDIRECT_URI."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
        //dump($url);die;
		echo "<script>location.href='".$url."'</script>";
        //header("location:$url");
    }
    //用户同意之后就获取code  通过获取code可以获取
    function getCode(){
        $code = $_GET["code"];
        //dump($code);die;
        //用code获取access_token
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appid}&secret={$this->appsecret}&code=".$code."&grant_type=authorization_code";
        //dump($url);die;
        //这里可以获取全部的东西  access_token openid scope
        $res = $this->https_request($url);
        $res  = json_decode($res,true);
        //dump($res);die;
        $openid = $res["openid"];
        $access_token = $res["access_token"];
        //这里是获取用户信息
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        //dump($url);die;
        $res = $this->https_request($url);
        //dump($res);die;
        $res = json_decode($res,true);
        //dump($res);die;
        //写入session
        //把用户的信息写入session 以备查用
        $open_id = $res["openid"];
		//dump($res);die;
        $user = M('y_user');
        $result = $user->where("wechat_id = '".$open_id."'")->find();
        if($result){
			if(isset($result['real_name'])){
				$_SESSION['phone'] = $result['phone'];
				$_SESSION['user_id'] = $result['id'];
				$_SESSION['user_name'] = $result['real_name'];
				$this->display('Index/index');
				exit;
			}else{
				$_SESSION['user_id'] = $result['id'];
				$this->assign('wechatid',$open_id);
				$this->display('person/wxperson');
				exit;
			}
            
        }else{
			$data['wechat_id'] = $open_id;
			$user->add($data);
			$add = $user->where("wechat_id = '".$open_id."'")->find();
			$_SESSION['user_id'] = $add['id'];
			$this->assign('wechatid',$open_id);
			$this->display('person/wxperson');
			exit;
        }
        //header("location:http://www.51yiqixiao.com/index.php/Home/Wechat/accept");
    }
    function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

}




