<?php
namespace Zotlabs\Module;
/**
 * @package		Friendica\modules
 * @subpackage	FileBrowser
 * @author		Fabio Comuni <fabrixxm@kirgroup.com>
 */

require_once('include/photo/photo_driver.php');

/**
 * @param App $a
 */

class Fbrowser extends \Zotlabs\Web\Controller {

	function get(){
		
		if (!local_channel())
			killme();
	
		if (\App::$argc==1)
			killme();
		
		//echo "<pre>"; var_dump(\App::$argv); killme();	
		
		switch(\App::$argv[1]){
			case "image":
				$path = array( array(z_root()."/fbrowser/image/", t("Photos")));
				$albums = false;
				$sql_extra = "";
				$sql_extra2 = " ORDER BY created DESC LIMIT 0, 10";
				
				if (\App::$argc==2){
					$albums = q("SELECT distinct(album) AS album FROM photo WHERE uid = %d ",
						intval(local_channel())
					);
					// anon functions only from 5.3.0... meglio tardi che mai..
					$albums = array_map( "self::folder1" , $albums);
					
				}
				
				$album = "";
				if (\App::$argc==3){
					$album = hex2bin(\App::$argv[2]);
					$sql_extra = sprintf("AND album = '%s' ",dbesc($album));
					$sql_extra2 = "";
					$path[]=array(z_root() . "/fbrowser/image/" . \App::$argv[2] . "/", $album);
				}
					
				$r = q("SELECT resource_id, id, filename, type, min(imgscale) AS hiq,max(imgscale) AS loq, description  
						FROM photo WHERE uid = %d $sql_extra
						GROUP BY resource_id $sql_extra2",
					intval(local_channel())					
				);
				
				$files = array_map("self::files1", $r);
				
				$tpl = get_markup_template("filebrowser.tpl");
				echo replace_macros($tpl, array(
					'$type' => 'image',
					'$baseurl' => z_root(),
					'$path' => $path,
					'$folders' => $albums,
					'$files' =>$files,
					'$cancel' => t('Cancel'),
				));
					
					
				break;
			case "file":
				if (\App::$argc==2){
					$files = q("SELECT id, filename, filetype FROM attach WHERE uid = %d ",
						intval(local_channel())
					);
					
					$files = array_map("self::files2", $files);
					//echo "<pre>"; var_dump($files); killme();
				
								
					$tpl = get_markup_template("filebrowser.tpl");
					echo replace_macros($tpl, array(
						'$type' => 'file',
						'$baseurl' => z_root(),
						'$path' => array( array(z_root()."/fbrowser/image/", t("Files")) ),
						'$folders' => false,
						'$files' =>$files,
						'$cancel' => t('Cancel'),
					));
					
				}
			
				break;
		}
		
	
		killme();
		
	}

	private static function folder1($el){
		return array(bin2hex($el['album']),$el['album']);
	}	


	private static function files1($rr){ 

		$ph = photo_factory('');
		$types = $ph->supportedTypes();
		$ext = $types[$rr['type']];
	
		$filename_e = $rr['filename'];
			
		return array( 
			z_root() . '/photo/' . $rr['resource_id'] . '-' . $rr['hiq'] . '.' .$ext, 
			$filename_e, 
			z_root() . '/photo/' . $rr['resource_id'] . '-' . $rr['loq'] . '.'. $ext
		);
	}

	private static function files2($rr){
		list($m1,$m2) = explode("/",$rr['filetype']);
		$filetype = ( (file_exists("images/icons/$m1.png"))?$m1:"zip");
	
		if(\App::get_template_engine() === 'internal') {
			$filename_e = template_escape($rr['filename']);
		}
		else {
			$filename_e = $rr['filename'];
		}
	
		return array( z_root() . '/attach/' . $rr['id'], $filename_e, z_root() . '/images/icons/16/' . $filetype . '.png'); 
	}

	
}
