<?php

namespace Openid;

	/**
	 * @brief MySQL provider for OpenID implementation.
	 *
	 */
	
class MysqlProvider extends \LightOpenIDProvider {
	
	// See http://openid.net/specs/openid-attribute-properties-list-1_0-01.html
	// This list contains a few variations of these attributes to maintain 
	// compatibility with legacy clients
	
	private $attrFieldMap = [
		'namePerson/first'       => 'firstName',
		'namePerson/last'        => 'lastName',
		'namePerson/friendly'    => 'channel_address',
		'namePerson'             => 'namePerson',
		'contact/internet/email' => 'email',
		'contact/email'          => 'email',
		'media/image/aspect11'   => 'pphoto',
		'media/image'            => 'pphoto',
		'media/image/default'    => 'pphoto',
		'media/image/16x16'      => 'pphoto16',
		'media/image/32x32'      => 'pphoto32',
		'media/image/48x48'      => 'pphoto48',
		'media/image/64x64'      => 'pphoto64',
		'media/image/80x80'      => 'pphoto80',
		'media/image/128x128'    => 'pphoto128',
		'timezone'               => 'timezone',
		'contact/web/default'    => 'url',
		'language/pref'          => 'language',
		'birthDate/birthYear'    => 'birthyear',
		'birthDate/birthMonth'   => 'birthmonth',
		'birthDate/birthday'     => 'birthday',
		'birthDate'              => 'birthdate',
		'gender'                 => 'gender',
	];
	

	// $attrMap is populated in the setup function since it contains functions
	// which cannot be used in a class constructor.

	private $attrMap;


	function setup($identity, $realm, $assoc_handle, $attributes) {

		$this->attrMap = [
			'namePerson/first'       => t('First Name'),
			'namePerson/last'        => t('Last Name'),
			'namePerson/friendly'    => t('Nickname'),
			'namePerson'             => t('Full Name'),
			'contact/internet/email' => t('Email'),
			'contact/email'          => t('Email'),
			'media/image/aspect11'   => t('Profile Photo'),
			'media/image'            => t('Profile Photo'),
			'media/image/default'    => t('Profile Photo'),
			'media/image/16x16'      => t('Profile Photo 16px'),
			'media/image/32x32'      => t('Profile Photo 32px'),
			'media/image/48x48'      => t('Profile Photo 48px'),
			'media/image/64x64'      => t('Profile Photo 64px'),
			'media/image/80x80'      => t('Profile Photo 80px'),
			'media/image/128x128'    => t('Profile Photo 128px'),
			'timezone'               => t('Timezone'),
			'contact/web/default'    => t('Homepage URL'),
			'language/pref'          => t('Language'),
			'birthDate/birthYear'    => t('Birth Year'),
			'birthDate/birthMonth'   => t('Birth Month'),
			'birthDate/birthday'     => t('Birth Day'),
			'birthDate'              => t('Birthdate'),
			'gender'                 => t('Gender')
		];
	
//		logger('identity: ' . $identity);
//		logger('realm: ' . $realm);
//		logger('assoc_handle: ' . $assoc_handle);
//		logger('attributes: ' . print_r($attributes,true));
	
		$data = \Zotlabs\Module\Id::getUserData($assoc_handle);
	
	
		/** @FIXME this needs to be a template with localised strings */
	
        $o .= '<form action="" method="post">'
           . '<input type="hidden" name="openid.assoc_handle" value="' . $assoc_handle . '">'
           . '<input type="hidden" name="login" value="' . $_POST['login'] .'">'
           . '<input type="hidden" name="password" value="' . $_POST['password'] .'">'
           . "<b>$realm</b> wishes to authenticate you.";
        if($attributes['required'] || $attributes['optional']) {
            $o .= " It also requests following information (required fields marked with *):"
               . '<ul>';

            foreach($attributes['required'] as $attr) {
                if(isset($this->attrMap[$attr])) {
                    $o .= '<li>'
                       . '<input type="checkbox" name="attributes[' . $attr . ']"> '
                       . $this->attrMap[$attr] . ' <span class="required">*</span></li>';
                }
            }
	
            foreach($attributes['optional'] as $attr) {
                if(isset($this->attrMap[$attr])) {
                    $o .= '<li>'
                       . '<input type="checkbox" name="attributes[' . $attr . ']"> '
                       . $this->attrMap[$attr] . '</li>';
                }
            }
            $o .= '</ul>';
        }
        $o .= '<br>'
           . '<button name="once">Allow once</button> '
           . '<button name="always">Always allow</button> '
           . '<button name="cancel">cancel</button> '
           . '</form>';
	
		\App::$page['content'] .= $o;
	}
	
	function checkid($realm, &$attributes) {
	
		logger('checkid: ' . $realm);
		logger('checkid attrs: ' . print_r($attributes,true));

		if(isset($_POST['cancel'])) {
			$this->cancel();
		}

		$data = \Zotlabs\Module\Id::getUserData();
		if(! $data) {
			return false;
		}
	
		$q = get_pconfig(local_channel(), 'openid', $realm);

		$attrs = array();
		if($q) {
			$attrs = $q;
        } elseif(isset($_POST['attributes'])) {
            $attrs = array_keys($_POST['attributes']);
        } elseif(!isset($_POST['once']) && !isset($_POST['always'])) {
            return false;
        }

        $attributes = array();
        foreach($attrs as $attr) {
            if(isset($this->attrFieldMap[$attr])) {
                $attributes[$attr] = $data[$this->attrFieldMap[$attr]];
            }
        }
	
		if(isset($_POST['always'])) {
			set_pconfig(local_channel(),'openid',$realm,array_keys($attributes));
		}

		return z_root() . '/id/' . $data['channel_address'];
	}
	
	function assoc_handle() {
		logger('assoc_handle');
		$channel = \App::get_channel();

		return z_root() . '/channel/' . $channel['channel_address']; 
	}
	
	function setAssoc($handle, $data) {
		logger('setAssoc');
		$channel = channelx_by_nick(basename($handle));
		if($channel)
			set_pconfig($channel['channel_id'],'openid','associate',$data);
	}
	
	function getAssoc($handle) {
		logger('getAssoc: ' . $handle);
	
		$channel = channelx_by_nick(basename($handle));
		if($channel)
			return get_pconfig($channel['channel_id'], 'openid', 'associate');
	
		return false;
	}
	
	function delAssoc($handle) {
		logger('delAssoc');
		$channel = channelx_by_nick(basename($handle));
		if($channel)
			return del_pconfig($channel['channel_id'], 'openid', 'associate');
	}

}
	
