<?php
namespace Zotlabs\Module;

/**
 * @file mod/id.php
 * @brief OpenID implementation
 */

require 'library/openid/provider/provider.php';



/**
 * @brief Entrypoint for the OpenID implementation.
 *
 * @param App &$a
 */

class Id extends \Zotlabs\Web\Controller {



	function init() {
	
		logger('id: ' . print_r($_REQUEST, true));
	
		if(argc() > 1) {
			$which = argv(1);
		} else {
			\App::$error = 404;
			return;
		}
	
		$profile = '';
		$channel = \App::get_channel();
		profile_load($which,$profile);
	
		$op = new \Openid\MysqlProvider;
		$op->server();
	}
	
	/**
	 * @brief Returns user data needed for OpenID.
	 *
	 * If no $handle is provided we will use local_channel() by default.
	 *
	 * @param string $handle (default null)
	 * @return boolean|array
	 */

	static public function getUserData($handle = null) {
		if (! local_channel()) {
			notice( t('Permission denied.') . EOL);
			\App::$page['content'] =  login();
	
			return false;
		}
	
	//	logger('handle: ' . $handle);
	
		if ($handle) {
			$r = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_address = '%s' limit 1",
				dbesc($handle)
			);
		} else {
			$r = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_id = %d",
				intval(local_channel())
			);
		}
	
		if (! r)
			return false;
	
		$x = q("select * from account where account_id = %d limit 1", 
			intval($r[0]['channel_account_id'])
		);
		if ($x)
			$r[0]['email'] = $x[0]['account_email'];
	
		$p = q("select * from profile where is_default = 1 and uid = %d limit 1",
			intval($r[0]['channel_account_id'])
		);
	
		$gender = '';
		if ($p[0]['gender'] == t('Male'))
			$gender = 'M';
		if ($p[0]['gender'] == t('Female'))
			$gender = 'F';
	
		$r[0]['firstName'] = ((strpos($r[0]['channel_name'],' ')) ? substr($r[0]['channel_name'],0,strpos($r[0]['channel_name'],' ')) : $r[0]['channel_name']);
		$r[0]['lastName'] = ((strpos($r[0]['channel_name'],' ')) ? substr($r[0]['channel_name'],strpos($r[0]['channel_name'],' ')+1) : '');
		$r[0]['namePerson'] = $r[0]['channel_name'];
		$r[0]['pphoto'] = $r[0]['xchan_photo_l'];
		$r[0]['pphoto16'] = z_root() . '/photo/profile/16/' . $r[0]['channel_id'] . '.jpg';
		$r[0]['pphoto32'] = z_root() . '/photo/profile/32/' . $r[0]['channel_id'] . '.jpg';
		$r[0]['pphoto48'] = z_root() . '/photo/profile/48/' . $r[0]['channel_id'] . '.jpg';
		$r[0]['pphoto64'] = z_root() . '/photo/profile/64/' . $r[0]['channel_id'] . '.jpg';
		$r[0]['pphoto80'] = z_root() . '/photo/profile/80/' . $r[0]['channel_id'] . '.jpg';
		$r[0]['pphoto128'] = z_root() . '/photo/profile/128/' . $r[0]['channel_id'] . '.jpg';
		$r[0]['timezone'] = $r[0]['channel_timezone'];
		$r[0]['url'] = $r[0]['xchan_url'];
		$r[0]['language'] = (($x[0]['account_language']) ? $x[0]['account_language'] : 'en');
		$r[0]['birthyear'] = ((intval(substr($p[0]['dob'],0,4))) ? intval(substr($p[0]['dob'],0,4)) : '');
		$r[0]['birthmonth'] = ((intval(substr($p[0]['dob'],5,2))) ? intval(substr($p[0]['dob'],5,2)) : '');
		$r[0]['birthday'] = ((intval(substr($p[0]['dob'],8,2))) ? intval(substr($p[0]['dob'],8,2)) : '');
		$r[0]['birthdate'] = (($r[0]['birthyear'] && $r[0]['birthmonth'] && $r[0]['birthday']) ? $p[0]['dob'] : '');
		$r[0]['gender'] = $gender;
	
		return $r[0];
	
	/*
	*    if(isset($_POST['login'],$_POST['password'])) {
	*        $login = mysql_real_escape_string($_POST['login']);
	*        $password = sha1($_POST['password']);
	*        $q = mysql_query("SELECT * FROM Users WHERE login = '$login' AND password = '$password'");
	*        if($data = mysql_fetch_assoc($q)) {
	*            return $data;
	*        }
	*        if($handle) {
	*            echo 'Wrong login/password.';
	*        }
	*    }
	*    if($handle) {
	*    ?>
	*    <form action="" method="post">
	*    <input type="hidden" name="openid.assoc_handle" value="<?php
namespace Zotlabs\Module; echo $handle?>">
	*    Login: <input type="text" name="login"><br>
	*    Password: <input type="password" name="password"><br>
	*    <button>Submit</button>
	*    </form>
	*    <?php
namespace Zotlabs\Module;
	*    die();
	*    }
	*/
	
	}
}
	
	
