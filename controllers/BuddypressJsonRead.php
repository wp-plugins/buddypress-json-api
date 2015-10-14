<?php
/*
  Controller name: Buddypress Read
  Controller description: Buddypress controller for reading actions
 */

require_once BUDDYPRESS_JSON_API_HOME . '/library/functions.class.php';

$upload_dir = wp_upload_dir();

class JSON_API_BuddypressRead_Controller {

	function __construct() {
	   header("Access-Control-Allow-Origin: *");
		$userid = 0;
		if($_GET['userid']){
			$userid = $_GET['userid'];
		}else if($_POST['userid']){
			$userid = $_POST['userid'];
		}else if($this->userid){
			$userid = $this->userid;
		}
		if($userid>0){
			bp_update_user_last_activity($userid);
		}
	}
	
	/************************************************
	Change Password
	************************************************/
	 public function profile_change_pw() {		
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if(!$_POST){$oReturn->error = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->error = __('Wrong User ID.','aheadzen'); return $oReturn;}
		if(!$_POST['email']){$oReturn->error = __('Email address is a required field.','aheadzen'); return $oReturn;}
		if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){$oReturn->error = __('Invalid Email.','aheadzen'); return $oReturn;}
		if(!$_POST['pw']){$oReturn->error = __('Current password is wrong.','aheadzen'); return $oReturn;}
		if(!$_POST['npw']){$oReturn->error = __('New password is wrong.','aheadzen'); return $oReturn;}
		if(!$_POST['confirmpw']){$oReturn->error = __('New confirm password is wrong.','aheadzen'); return $oReturn;}
		if($_POST['confirmpw']!=$_POST['npw']){$oReturn->error = __('Password should be same.','aheadzen'); return $oReturn;}		
				
		$userid = $_POST['userid'];
		$pw = trim($_POST['pw']);
		$user_email = trim($_POST['email']);
		
		$user_id = wp_update_user(array('ID' =>$userid,'user_email'=> $user_email));
		
		if ( !empty( $pw ) )
			wp_set_password($pw,$userid );
		
		$oReturn->success->id = $userid;
		$oReturn->success->pw = $pw;
		$oReturn->success->email = $user_email;
		$oReturn->success->message = __('Password Updated Successfully.','aheadzen');
		return  $oReturn;
	}
	
	/************************************************
	Change Password
	************************************************/
	 public function user_profile_gallery() {		
		
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if(!$_GET['userid']){$oReturn->error = __('Wrong User ID.','aheadzen'); return $oReturn;}
		$files = array();
		$bp_upload = xprofile_avatar_upload_dir('',$_GET['userid']);		
		$basedir = $bp_upload['path'];
		$baseurl = $bp_upload['url'];
		$dh  = opendir($basedir);
		$counter=0;
		while (false !== ($filename = readdir($dh))) {
			if($filename=='.' || $filename=='..'){				
			}else{
				if(file_exists($basedir.'/'.$filename)){
					$oReturn->images[$counter]->src = $baseurl.'/'.$filename;
					$oReturn->images[$counter]->sub = '';
					//$files[] = $baseurl.'/'.$filename;
					$counter++;
				}
			}
		}
		return  $oReturn;
	}
	
	function upload_image_to_user()
	{
		header("Access-Control-Allow-Origin: *");
		$post_data = array();
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if($_GET['image']==''){$oReturn->error = __('Wrong image URL','aheadzen'); return $oReturn;}
		if($_GET['userid']==''){$oReturn->error = __('Wrong User ID','aheadzen'); return $oReturn;}
		$user_id = $_GET['userid'];
		$image = $_GET['image'];
		$ext = pathinfo($image, PATHINFO_EXTENSION);
		$imageFilename = basename($image,'.'.$ext);		
		
		$bp_upload = xprofile_avatar_upload_dir('',$user_id);		
		$basedir = $bp_upload['path'];
		$baseurl = $bp_upload['url'];
		if(!file_exists($basedir)){@wp_mkdir_p( $basedir );}
		$filename = 'avatar_'.$user_id.'.jpg';
		$outputFile = $basedir.'/'.$filename;
		$imageurl = $baseurl.'/'.$filename;
		$cp = copy($image, $outputFile);

		$imgdata = @getimagesize( $outputFile );
		$img_width = $imgdata[0];
		$img_height = $imgdata[1];
		$upload_dir = wp_upload_dir();
		$existing_avatar_path = str_replace( $upload_dir['basedir'], '', $outputFile );
		$args = array(
			'item_id'       => $user_id,
			'original_file' => $existing_avatar_path,
			'crop_x'        => 0,
			'crop_y'        => 0,
			'crop_w'        => $img_width,
			'crop_h'        => $img_height
		);
		
		if (bp_core_avatar_handle_crop( $args ) ) {
			$imageurl = bp_core_fetch_avatar( array( 'item_id' => $user_id,'html'=>false,'type' => 'thumb'));
			$oReturn->success->msg = 'Image uploaded successfully.';
			$oReturn->success->url = $imageurl;
		}else{
			$oReturn->error = 'Upload error';
		}
		if(file_exists($outputFile)){@unlink($outputFile);}
		return $oReturn;
		
	}
	/*
	Share to Users -- http://localhost/api/buddypressread/share_the_link/?id=19&ptype=post&userid=1&shareto=user&sharetouser=@buyer1,@chynna,@testuser5
	Share to Activity -- http://localhost/api/buddypressread/share_the_link/?id=19&ptype=post&userid=1
	Share to Group -- http://localhost/api/buddypressread/share_the_link/?id=19&ptype=post&userid=1&shareto=group&sharetogroup=1
	id = post id, page id, forum topic id.....
	ptype = post type like post, page,forum topic 
	userid = poster user id/current logged user id
	shareto = 
		keep blank -- for activity share
		group -- for share in group activity
		user -- for share in users mention list
	sharetouser = user mention id like 	:: @buyer1,@chynna,@testuser5
	sharetogroup = group id to which group user want to share	
	*/
	function share_activity_data()
	{
		header("Access-Control-Allow-Origin: *");
		$post_data = array();
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if($_GET['id']==''){$oReturn->error = __('Wrong activity ID','aheadzen'); return $oReturn;}
		if($_GET['userid']==''){$oReturn->error = __('Wrong User ID','aheadzen'); return $oReturn;}
		
		$post_data['aid'] = $_GET['id'];
		$post_data['userid'] = $_GET['userid'];
		$post_data['sharetogroup']=$post_data['mentions']='';
		$post_data['shareto'] = $_GET['shareto'];
		if($_GET['shareto']=='user'){
			if($_GET['sharetouser']==''){$oReturn->error = __('Wrong User ID','aheadzen'); return $oReturn;}
			$post_data['mentions'] = $_GET['sharetouser'];
		}elseif($_GET['shareto']=='group'){
			if($_GET['sharetogroup']==''){$oReturn->error = __('Wrong Group ID','aheadzen'); return $oReturn;}
			$post_data['sharetogroup'] = $_GET['sharetogroup'];
		}
		
		$activitys = bp_activity_get(array('in'	=> $post_data['aid']));
		if(!$activitys){$oReturn->error = __('Wrong activity ID','aheadzen'); return $oReturn;}
		$activitie = $activitys['activities'][0];
		$post_data['activity_user_id'] = $activitie->user_id;
		$activity_content = $post_data['mentions'];
		$activity_action = '';		
		$display_name = bp_core_get_user_displaynames($post_data['userid']);
		$add_primary_link     = bp_core_get_userlink($post_data['userid'], false, true );		
		$author_display_name = bp_core_get_user_displaynames($post_data['activity_user_id']);
		$author_display_name = $author_display_name[$post_data['activity_user_id']];
		$author_primary_link     = bp_core_get_userlink($post_data['activity_user_id'], false, true );		
		$activity_action = '<a href="'.$add_primary_link.'">'.$display_name[$post_data['userid']].'</a> shared <a href="'.$author_primary_link.'">'.$author_display_name.'</a>\'s activity';
		
		if($post_data['sharetogroup']){
			$bp = buddypress();
			$bp->groups->current_group = groups_get_group(array('group_id' =>$post_data['sharetogroup']));
			if(groups_is_user_member($post_data['userid'],$post_data['sharetogroup'])){
				//$activity_action  = bp_core_get_userlink($post_data['userid']).' shared <a href="'.$author_primary_link.'">'.$author_display_name.'</a>\'s activity in the group <a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . esc_attr( $bp->groups->current_group->name ) . '</a>';
				$content_filtered = apply_filters( 'groups_activity_new_update_content', $activity_content );
				
				$activity_id = groups_record_activity(array(
					'user_id' => $post_data['userid'],
					'action'  => $activity_action,
					'content' => $content_filtered,
					'type'    => 'activityshare',
					'item_id' => $post_data['sharetogroup'],
					'secondary_item_id' => $post_data['aid']
				) );

				groups_update_groupmeta($post_data['sharetogroup'], 'last_activity', bp_core_current_time());
				$oReturn->success->id = $activity_id;
				$oReturn->success->msg = __('Activity shared in group successfully.','aheadzen');
			}else{
				$oReturn->error = __('User is not member of group.','aheadzen'); return $oReturn;
			}
		}else{
			//$activity_action = '<a href="'.$add_primary_link.'">'.$display_name[$post_data['userid']].'</a> shared <a href="'.$author_primary_link.'">'.$author_display_name.'</a>\'s activity';
			$add_content = apply_filters( 'bp_activity_new_update_content', $activity_content );
			$activity_id = bp_activity_add( array(
						'user_id'      => $post_data['userid'],
						'content'      => $add_content,
						'primary_link' => $add_primary_link,
						'component'    => buddypress()->activity->id,
						'type'         => 'activityshare',
						'action'       => $activity_action,
						'item_id'	   => $post_data['aid']
					) );
			if($activity_id){
				bp_update_user_meta($post_data['userid'], 'bp_latest_update', array(
					'id'      => $activity_id,
					'content' => $activity_content
				));
				$oReturn->success->id = $activity_id;
				if($post_data['mentions']){
					$oReturn->success->msg = __('Activity shared with users successfully.','aheadzen');
				}else{
					$oReturn->success->msg = __('Activity shared successfully.','aheadzen');
				}
			}else{
				$oReturn->error = __('Activity added error.','aheadzen');
			}
		}
		return $oReturn;
	}
	/*
	http://localhost/api/buddypressread/share_the_link/?id=19&ptype=post&userid=1&shareto=user&sharetouser=@buyer1,@chynna,@testuser5
	http://localhost/api/buddypressread/share_the_link/?id=19&ptype=post&userid=1
	http://localhost/api/buddypressread/share_the_link/?id=19&ptype=post&userid=1&shareto=group&sharetogroup=1
	id = post id, page id, forum topic id.....
	ptype = post type like post, page,forum topic 
	userid = poster user id/current logged user id
	sharteto = 
		keep blank -- for activity share
		group -- for share in group activity
		user -- for share in users mention list
	sharetouser = user mention id like 	:: @buyer1,@chynna,@testuser5
	sharetogroup = group id to which group user want to share
	
	*/
	function share_the_link(){
		$pid = $_GET['id'];
		$post_data = array();
		$post_data['sharetogroup']=$post_data['mentions']='';
		if($_GET['shareto']=='user' && $_GET['sharetouser']){
			$post_data['mentions'] = $_GET['sharetouser'];
		}elseif($_GET['shareto']=='group' && $_GET['sharetogroup']){
			$post_data['sharetogroup'] = $_GET['sharetogroup'];
		}
		
		$post_data['userid'] = $_GET['userid'];
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if($pid==''){$oReturn->error = __('Wrong ID','aheadzen'); return $oReturn;}
		if($_GET['userid']==''){$oReturn->error = __('Wrong User ID','aheadzen'); return $oReturn;}
		
		$post = array();		
		$arg = array('p'=>$pid);
		if($_GET['ptype']){
			$arg['post_type']=$_GET['ptype'];
		}
		
		query_posts($arg);
		if(have_posts()){
			while ( have_posts() ) : the_post();
				$post_data['title'] = get_the_title();
				$post_data['text'] = get_the_excerpt();
				$post_data['bpfb_url'] = get_permalink();
				$post_data['author_id'] = get_the_author_meta('ID');
				$post_data['image'] = '';
				$image_src = '';
				preg_match('/<img.+src=[\'"](?P<src>.+)[\'"].*>/i', get_the_content(), $image);
				if($image['src']){
					$imgarr = explode('"',$image['src']);
					$post_data['image'] = $imgarr[0];
				}
				if($post_data['image']==''){
					if(has_post_thumbnail($pid)){
						$image = wp_get_attachment_image_src(get_post_thumbnail_id($pid),'single-post-thumbnail');
						$post_data['image'] = $image[0];
					}else{
						$images = get_children( array( 'post_parent' => $pid, 'post_status' => 'inherit', 'numberposts' => 1, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'ID' ) );
						if ( $images ) {
							$image = array_shift( $images );
							$image_id = $image->ID;
						}
						if($image_id>0){
							$adthumbarray = wp_get_attachment_image_src( $image_id, 'medium' );
							if ( $adthumbarray ) {
								$post_data['image'] = $adthumbarray[0];
							} else {
								$post_data['image'] = wp_get_attachment_image_src($image_id, 'thumbnail');
							}
						}
					}
				}
				
				$BpfbCodec = new BpfbCodec();
				$activity_content = $post_data['mentions'].$BpfbCodec->create_link_tag($post_data['bpfb_url'],$post_data['title'],$post_data['text'],$post_data['image']);		
				$display_name = bp_core_get_user_displaynames($post_data['userid']);
				$primary_link     = bp_core_get_userlink($post_data['userid'], false, true );
				$add_primary_link = apply_filters( 'bp_activity_new_update_primary_link', $primary_link );
				$author_display_name = bp_core_get_user_displaynames($post_data['author_id']);
				$author_primary_link     = bp_core_get_userlink($post_data['author_id'], false, true );
				$author_display_name = $author_display_name[$post_data['author_id']];
				
				if($post_data['sharetogroup']){ /*share to group*/
					$bp = buddypress();
					$bp->groups->current_group = groups_get_group(array('group_id' =>$post_data['sharetogroup']));
					if(groups_is_user_member($post_data['userid'],$post_data['sharetogroup'])){
						$activity_action  = bp_core_get_userlink($post_data['userid']).' shared <a href="'.$author_primary_link.'">'.$author_display_name.'</a>\'s post in the group <a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . esc_attr( $bp->groups->current_group->name ) . '</a>';
						//$activity_action  = sprintf( __( '%1$s shared in the group %2$s', 'buddypress'), bp_core_get_userlink($post_data['userid']), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . esc_attr( $bp->groups->current_group->name ) . '</a>' );
						$content_filtered = apply_filters( 'groups_activity_new_update_content', $activity_content );
						
						$activity_id = groups_record_activity(array(
							'user_id' => $post_data['userid'],
							'action'  => $activity_action,
							'content' => $content_filtered,
							'type'    => 'activityshare',
							'item_id' => $post_data['sharetogroup']
						) );

						groups_update_groupmeta($post_data['sharetogroup'], 'last_activity', bp_core_current_time());
						$oReturn->success->id = $activity_id;
						$oReturn->success->msg = __('Activity added in group successfully.','aheadzen');
					}else{
						$oReturn->error = __('User is not member of group.','aheadzen'); return $oReturn;
					}
					$oReturn->success->msg = __('Shared in group successfully.','aheadzen');
				}else{ /*share to activity*/
					// Record this on the user's profile
					$activity_action = '<a href="'.$add_primary_link.'">'.$display_name[$post_data['userid']].'</a> shared <a href="'.$author_primary_link.'">'.$author_display_name.'</a>\'s post';
					$add_content = apply_filters( 'bp_activity_new_update_content', $activity_content );
					// Now write the values
					$activity_id = bp_activity_add( array(
						'user_id'      => $post_data['userid'],
						'content'      => $add_content,
						'primary_link' => $add_primary_link,
						'component'    => buddypress()->activity->id,
						'type'         => 'activityshare',
						'action'       => $activity_action,
					) );
					$activity_content = apply_filters( 'bp_activity_latest_update_content', $post_data['text'], $activity_content );
					bp_update_user_meta($post_data['userid'], 'bp_latest_update', array(
						'id'      => $activity_id,
						'content' => $activity_content
					));	
					if($post_data['mentions']){
						$oReturn->success->msg = __('Shared to users successfully.','aheadzen');
					}else{
						$oReturn->success->msg = __('Shared in activity successfully.','aheadzen');
					}
				}				
				$oReturn->success->id = $activity_id;
				
			endwhile;
			wp_reset_query();
		}else{
			$oReturn->error = __('No data available.','aheadzen');
		}		
		return $oReturn;
	}
	
	function share_activity(){
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->message = __('Wrong User try.','aheadzen'); return $oReturn;}
		print_r($_POST);
		return $oReturn;
	}
	
	function follow_unfollow_set()
	{
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->message = __('Wrong User try.','aheadzen'); return $oReturn;}
		if(!$_POST['leader_id']){$oReturn->message = __('Wrong Leader id.','aheadzen'); return $oReturn;}
		
		if(function_exists('bp_follow_is_following') && bp_follow_is_following(array('leader_id'=>$_POST['leader_id'],'follower_id'=>$_POST['userid'])))
		{
			if(function_exists('bp_follow_stop_following')){
				if(bp_follow_stop_following(array('leader_id' => $_POST['leader_id'], 'follower_id' => $_POST['userid']))){
					$oReturn->success = __('Unhallowed added successfully.','aheadzen');
					$oReturn->is_following = 0;
					if(function_exists('bp_follow_total_follow_counts')){
						$oReturn->follow_counts  = bp_follow_total_follow_counts( array( 'user_id' =>$_POST['leader_id'] ) );
					}
				}else{
					$oReturn->error = __('Error while unhallowed.','aheadzen');
					$oReturn->is_following = 1;
				}
			}
		}else{
			if(function_exists('bp_follow_start_following')){
				if(bp_follow_start_following(array('leader_id' => $_POST['leader_id'], 'follower_id' => $_POST['userid']))){
					$oReturn->success = __('Follower added successfully.','aheadzen');
					$oReturn->is_following = 1;
					if(function_exists('bp_follow_total_follow_counts')){
						$oReturn->follow_counts  = bp_follow_total_follow_counts( array( 'user_id' =>$_POST['leader_id'] ) );
					}
				}else{
					$oReturn->error = __('Error while adding follower.','aheadzen');
					$oReturn->is_following = 0;
				}				
			}
		}
		
		return $oReturn;
	}
	
	function set_push_notification_device_token () {
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if(!$_GET['token']){$oReturn->error = __('Wrong token.','aheadzen'); return $oReturn;}
		if(!$_GET['userid']){$oReturn->error = __('Wrong User ID.','aheadzen'); return $oReturn;}
		$user_id = $_GET['userid'];
		$token = $_GET['token'];
		if($user_id && $token){
			update_user_meta( $user_id, 'ionic_push_device_token', $token);
		}
		$oReturn->success = __('Ionic Push Token added successfully.','aheadzen');
		return $oReturn;
	}
	/**
	 * Handles link preview requests.
	 */
	function activity_preview_link () {
		$_POST['data']=urldecode($_GET['data']);
		$BpfbBinder = new BpfbBinder();
		return $BpfbBinder->ajax_preview_link();
	}
	
	public function activity_set_bpfb_url()
	{
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['title']){$oReturn->message = __('No title added.','aheadzen'); return $oReturn;}
		
		$user_id = $_POST['userid'];
		$title = $_POST['title'];
		$text = $_POST['text'];
		$url = $_POST['url'];
		$image = $_POST['image'];
		
		$images = explode(',',$imagesfile);		
		$BpfbCodec = new BpfbCodec();
		
		
		$bpfb_code = $BpfbCodec->create_images_tag($images);
		$bpfb_code = apply_filters('bpfb_code_before_save', $bpfb_code);
		if(function_exists('bp_activity_post_update')){
			//$activity_id = bp_activity_post_update(array('content' => $bpfb_code,'user_id' => $user_id));
			$primary_link = '';
			if(function_exists('bp_core_get_userlink')){
				$primary_link     = bp_core_get_userlink($user_id, false, true );
			}
			//$activity_id = bp_activity_post_update(array('content' => $bpfb_code,'user_id' => $user_id));
			$activity_id = bp_activity_add( array(
				'user_id'      => $user_id,
				'content'      => $bpfb_code,
				'primary_link' => $primary_link,
				'component'    => buddypress()->activity->id,
				'type'         => 'activity_photo',
			) );
			bp_update_user_meta($user_id, 'bp_latest_update', array(
				'id'      => $activity_id,
				'content' => $bpfb_code
			));
			if($activity_id){
				global $blog_id;
				bp_activity_update_meta($activity_id, 'bpfb_blog_id', $blog_id);
			}
			
		}else{
			$oReturn->error = __('Add activity error. Something wrong.','aheadzen');
		}
		$oReturn->success->id = $activity_id;
		$oReturn->success->msg = __('Activity added successfully.','aheadzen');
		return $oReturn;
	}
	
	public function activity_set_bpfb()
	{
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		
		$user_id = $_POST['userid'];
		
		$BpfbCodec = new BpfbCodec();
		if (!empty($_POST['bpfb_video_url'])) {
			$bpfb_code = $BpfbCodec->create_video_tag($_POST['bpfb_video_url']);
		}
		if (!empty($_POST['bpfb_url'])) {
			$bpfb_code = $BpfbCodec->create_link_tag($_POST['bpfb_url'],$_POST['title'],$_POST['text'],$_POST['image']);
		}
		if (!empty($_POST['imagesfile'])) {
			$imagesfile = $_POST['imagesfile'];		
			$images = explode(',',$imagesfile);	
			$bpfb_code = $BpfbCodec->create_images_tag($images);
		}
		
		$bpfb_code = apply_filters('bpfb_code_before_save', $bpfb_code);
		if(!$bpfb_code){
			$oReturn->error = __('bpfb code - activity error. Something wrong.','aheadzen');return $oReturn;
		}
		if(trim($_POST['content'])){
			$bpfb_code = $_POST['content'] .'<br>'. $bpfb_code;
		}
		
		if(function_exists('bp_activity_post_update')){
			//$activity_id = bp_activity_post_update(array('content' => $bpfb_code,'user_id' => $user_id));
			$primary_link = '';
			if(function_exists('bp_core_get_userlink')){
				$primary_link     = bp_core_get_userlink($user_id, false, true );
			}
			//$activity_id = bp_activity_post_update(array('content' => $bpfb_code,'user_id' => $user_id));
			$activity_id = bp_activity_add( array(
				'user_id'      => $user_id,
				'content'      => $bpfb_code,
				'primary_link' => $primary_link,
				'component'    => buddypress()->activity->id,
				'type'         => 'activity_photo',
			) );
			bp_update_user_meta($user_id, 'bp_latest_update', array(
				'id'      => $activity_id,
				'content' => $bpfb_code
			));
			if($activity_id){
				global $blog_id;
				bp_activity_update_meta($activity_id, 'bpfb_blog_id', $blog_id);
				$oReturn->success->id = $activity_id;
				$oReturn->success->msg = __('Activity added successfully.','aheadzen');
			}else{
				$oReturn->error = __('Add activity error. Something wrong.','aheadzen');
			}
		}else{
			$oReturn->error = __('Add activity Buddypress function error. Something wrong.','aheadzen');
		}
		
		return $oReturn;
	}
	
	public function activity_upload_image()
	{
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_FILES){$oReturn->message = __('Wrong picture.','aheadzen'); return $oReturn;}
		$oReturn = $this->upload_image_activity();
		
		return $oReturn;
	}
	
	function upload_image_activity(){
		//$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		global $bp;
		
		if($_FILES && $_FILES['file'] && $_FILES['file']['name'] && $_FILES['file']['size']>0 && $_FILES['file']['error']==0)
		{
			$tmp_name = $_FILES['file']['tmp_name'];
			$filename = $_FILES['file']['name'];
			$type = $_FILES['file']['type'];
			$size = $_FILES['file']['size'];
			
			$basedir = BPFB_BASE_IMAGE_DIR;
			$user_id = $_GET['user_id'];
			if(!file_exists($basedir)){@wp_mkdir_p( $basedir );}
			if(!file_exists($basedir.$user_id.'/')){@wp_mkdir_p($basedir.$user_id.'/');}
			$srch = array(' '," ",'"',"'",'-','`','~','!','@','#','$','%','^','&','*','(',')','+','=','|','\\','[',']','{','}',',','/','<','>');
			$repl = array('_','_','','','_','','','','','','','','','','','','','','','','','','','','','','','','');
			$filename = preg_replace('/[^0-9]/', '-', microtime()).'-'.rand(1,1000).'-'.str_replace($srch,$repl,$filename);			
			
			$filename = $user_id.'/'.$filename;
			$targetFile = $basedir.$filename;
			$targetFileURL = BPFB_BASE_IMAGE_URL.$filename;
			$uploadOk = 1;
			$imageFileType = pathinfo($targetFile,PATHINFO_EXTENSION);
			// Check if image file is a actual image or fake image
			$check = getimagesize($tmp_name);
			if($check == false) {			
				$oReturn->error = __('File is not an image.','aheadzen');				
			}/*elseif ($size > 500000) { // Check file size
				$oReturn->error = __('Sorry, your file is too large.','aheadzen');
			}*/
			else // Allow certain file formats
			if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
				$oReturn->error = __('Sorry, only JPG, JPEG, PNG & GIF files are allowed.','aheadzen');
			}else{
				if (move_uploaded_file($tmp_name, $targetFile)) {
					if($targetFile){
						if (function_exists('wp_get_image_editor')) { // New way of resizing the image
							$image = wp_get_image_editor($targetFile);
							if (!is_wp_error($image)) {
								list($thumb_w,$thumb_h) = Bpfb_Data::get_thumbnail_size();
								$thumb_filename  = $image->generate_filename('bpfbt');
								$image->resize($thumb_w, $thumb_h, false);
								
								// Alright, now let's rotate if we can
								if (function_exists('exif_read_data')) {
									$exif = exif_read_data($targetFile); // Okay, we now have the data
									if (!empty($exif['Orientation']) && 3 === (int)$exif['Orientation']) $image->rotate(180);
									else if (!empty($exif['Orientation']) && 6 === (int)$exif['Orientation']) $image->rotate(-90);
									else if (!empty($exif['Orientation']) && 8 === (int)$exif['Orientation']) $image->rotate(90);
								}
								$image->save($thumb_filename);
							}
						} else {
							image_resize($targetFile, $thumb_w, $thumb_h, false, 'bpfbt');
						}						
					}
					//$oReturn->success->filenurl = $targetFileURL;
					$oReturn = $filename;
					//$oReturn->success->msg = __('The file has been uploaded.','aheadzen');
					
				} else {
					//$oReturn->success->outputFile = $outputFile;
					//$oReturn->success->filename = $filename;
					//$oReturn->error = __('Sorry, there was an error uploading file.','aheadzen');
				}
			}
		}	
		
		return $oReturn;
	}
	
	public function members_get_short() 
	 {
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		global $wpdb,$table_prefix;
		
		$limit = 10;
		$keyword = trim($_GET['keyword']);
		if($_GET['limit']){$limit = trim($_GET['limit']);}
		if($keyword){
			$sql = "select ID,display_name,user_login from ".$table_prefix."users where user_login like \"$keyword%\" OR display_name like \"$keyword%\" order by display_name limit $limit";
			$members = $wpdb->get_results($sql);
			$counter = 0;
			if($members){
				foreach($members as $membersobj){
					$oReturn->members[$counter]->id = $membersobj->ID;
					$oReturn->members[$counter]->user_login = $membersobj->user_login;
					$oReturn->members[$counter]->display_name = $membersobj->display_name;
					$counter++;
				}
			}			
		}else{
			$oReturn->members = array();
		}
		//echo '<pre>';print_r($oReturn);exit;
		return $oReturn;
	 }
	 
	public function members_get_nameonly() 
	 {
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		global $wpdb,$table_prefix;
		
		$keyword = trim($_GET['keyword']);
		if($keyword){
			$sql = "select user_login from ".$table_prefix."users where user_login not like \"%@%\" and user_login like \"$keyword%\" order by user_login limit 10";
			$oReturn->members = $wpdb->get_col($sql);
			
		}else{
			$oReturn->members = array();
		}
		//echo '<pre>';print_r($oReturn);exit;
		return $oReturn;
	 }
	 
	public function members_get_members() 
	 {
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		$oReturn->total = 0;
		$bp_members = array();
		$member_data = array();
		global $wpdb,$table_prefix;
		
		$keyword = trim($_GET['keyword']);
		if($keyword==''){ $oReturn->error = __('Please enter keyword to search.','aheadzen'); return $oReturn;}
		
		$sql = "select DISTINCT(user_id) from ".$table_prefix."bp_xprofile_data where MATCH (value) AGAINST('".$keyword."*' IN BOOLEAN MODE) limit 10";
		$members = $wpdb->get_col($sql);
		if($members){
			$counter = 0;
			for($m=0;$m<count($members);$m++){
				$uid = $members[$m];
				$user = new BP_Core_User($uid);
				
				if($user){
					$username = $avatar_big = $avatar_thumb = '';
					if($user->user_url){
						$username = str_replace('/','',str_replace(site_url('/members/'),'',$user->user_url));
					}
					if($user->avatar){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar, $user_avatar_result);
						$avatar_big = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_big && !strstr($avatar_big,'http:')){ $avatar_big = 'http:'.$avatar_big;}
					}
					if($user->avatar_thumb){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
						$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_thumb && !strstr($avatar_thumb,'http:')){ $avatar_thumb = 'http:'.$avatar_thumb;}
					}					
					$oReturn->members[$counter]->id 		= $user->id;
					$oReturn->members[$counter]->username 	= $username;
					$oReturn->members[$counter]->fullname 	= $user->fullname;
					$oReturn->members[$counter]->email 		= $user->email;
					//$oReturn->members[$counter]->user_url 	= $user->user_url;
					$oReturn->members[$counter]->last_active= $user->last_active;
					//$oReturn->members[$counter]->avatar_big = $avatar_big;
					$oReturn->members[$counter]->avatar_thumb = $avatar_thumb;
					
					$profile_data = $user->profile_data;
					if($profile_data){
						foreach($profile_data as $sFieldName => $val){
							if(is_array($val)){
								$oReturn->members[$counter]->$sFieldName = $val['field_data'];
							}
						}
					}
					if(function_exists('bp_follow_total_follow_counts')){
						$oReturn->members[$counter]->follow_counts  = bp_follow_total_follow_counts( array( 'user_id' => $user->id ) );
					}
					$oReturn->members[$counter]->is_following = 0;
					if(function_exists('bp_follow_is_following') && bp_follow_is_following(array('leader_id'=>$user->id,'follower_id'=>$_GET['userid']))){
						$oReturn->members[$counter]->is_following = 1;
					}
					$counter++;
				}
			}			
			
		}else{$oReturn->error = __('No Members Available To Display.','aheadzen');}
		
		//echo '<pre>';print_r($oReturn);exit;
		return $oReturn;
	 }
	 
   /**
     * Returns an Array with all mentions
     * @param int pages: number of pages to display (default 1)
     * @param int maxlimit: number of maximum results (default 20)
	 * @param String sort: sort ASC or DESC (default DESC)
     * @param String username: username to filter on, comma-separated for more than one ID (default unset)
     * @return array mentions: an array containing the mentions
     */
    public function activity_get_mentions() {
        header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		
		if(!$_GET['username']){$oReturn->error = __('Missing parameter username.','aheadzen'); return $oReturn;}
		
		$username = $_GET['username'];
		$maxlimit = $_GET['maxlimit'];
		$page = $_GET['pages'];
		$orderby = $_GET['sort'];
		
		if(!$page){$page=1;}
		if(!$maxlimit){$maxlimit=20;}
		if(!$orderby){$orderby='DESC';}
		if(!$username){$oReturn->error = __('Wrong User Name.','aheadzen'); return $oReturn;}
		if(!username_exists($username)){return $this->error('xprofile', 1);}
		
		$start = $maxlimit*($page-1);
		$end = $maxlimit;
		global $wpdb,$table_prefix;
		$total_count = $wpdb->get_var("select count(id) from ".$table_prefix."bp_activity where content like \"%@".$username."%\"");
		$sql = "select id,user_id,component,type,content,date_recorded from ".$table_prefix."bp_activity where content like \"%@".$username."%\" order by date_recorded $orderby limit $start,$end";
		$res = $wpdb->get_results($sql);
		 $oReturn->total_count = $total_count;
		 $oReturn->total_pages = ceil($total_count/$maxlimit);
		if($res){
			$counter=0;
			foreach($res as $oMentions){
				$user = new BP_Core_User($oMentions->user_id);
				if($user && $user->avatar){
					$oMentions->fullname = $user->fullname;
					$oMentions->email = $user->email;
					$oMentions->user_url = $user->user_url;
					if($user->user_url){
						$oMentions->username = str_replace('/','',str_replace(site_url('/members/'),'',$user->user_url));
					}
					if($user->avatar){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar, $user_avatar_result);
						$avatar_big = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_big && !strstr($avatar_big,'http:')){ $avatar_big = 'http:'.$avatar_big;}
						$oMentions->avatar_big = $avatar_big;
					}
					if($user->avatar_thumb){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
						$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_thumb && !strstr($avatar_thumb,'http:')){ $avatar_thumb = 'http:'.$avatar_thumb;}
						$oMentions->avatar_thumb = $avatar_thumb;						
					}
				}
				
				$oReturn->mentions[$counter]->id = $oMentions->id;
				$oReturn->mentions[$counter]->component = $oMentions->component;
				$oReturn->mentions[$counter]->type = $oMentions->type;
				$oReturn->mentions[$counter]->content = $oMentions->content;
				$oReturn->mentions[$counter]->time = $oMentions->date_recorded;
				$oReturn->mentions[$counter]->user->id = $oMentions->user_id;
				$oReturn->mentions[$counter]->user->fullname = $oMentions->fullname;
				$oReturn->mentions[$counter]->user->email = $oMentions->email;
				$oReturn->mentions[$counter]->user->username = $oMentions->username;
				$oReturn->mentions[$counter]->user->user_url = $oMentions->user_url;
				$oReturn->mentions[$counter]->user->avatar_thumb = $oMentions->avatar_thumb;
				$oReturn->mentions[$counter]->user->avatar_big = $oMentions->avatar_big;
				
				$counter++;
			}
		}else{
			$oReturn->msg = __('No Mentions Available To Display.','aheadzen');
		}
		
		return $oReturn;
    }
	
	
	public function activity_comments_delete()
	{
		$error = '';
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->error = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['commentid']){$oReturn->error = __('Wrong Comment Id.','aheadzen'); return $oReturn;}
		if(!$_POST['activityid']){$oReturn->error = __('Wrong Activity Id.','aheadzen'); return $oReturn;}
		
		$comment_id = (int)$_POST['commentid'];
		$activity_id = (int)$_POST['activityid'];
		
		if(bp_activity_delete_comment( $activity_id, $comment_id ))
		{
			$oReturn->success->message = __('Activity comment deleted successfully.','aheadzen');			
		}else{
			$error = __('Something wrong to delete activity comment.','aheadzen');
		}
		
		$oReturn->error = $error;
		return  $oReturn;
	}
	
	/**
     * Supply post data
     * @param int userid: User ID
     * @param String content: Activity comment content
	 * @param int activityid: Activity Id for which you want to add comments
     * @return array message: success or error message & added activity comment ID
     */
	public function activity_comments_add_edit()
	{		
		header("Access-Control-Allow-Origin: *");
		/*//The data only for testing purpose.
		$_POST['content'] = '123 HELLO THIS IS TEST ACTIVITY Comments FOR ME';
		$_POST['userid'] = 1;
		$_POST['activityid'] = 47;
		*/		
		$error = '';
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->error = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['content']){$oReturn->error = __('Please do not leave the comment area blank.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->error = __('Wrong User Id.','aheadzen'); return $oReturn;}
		if(!$_POST['activityid']){$oReturn->error = __('Wrong Activity Id.','aheadzen'); return $oReturn;}
		
		$content = $_POST['content'];
		$user_id = (int)$_POST['userid'];
		$activity_id = (int)$_POST['activityid'];
		$commentid = (int)$_POST['commentid'];
		
		$arg = array(
			'content'    	=> $content,
			'activity_id' 	=> $activity_id,
			'user_id' 		=> $user_id,
			'parent_id'   => false
		);
		
		if($commentid){$arg['id'] = $commentid;} //update activity comment
		if($comment_id = bp_activity_new_comment($arg))
		{
			$oReturn->success->id = $comment_id;
			if($activityid){
				$oReturn->success->message = __('Activity comments updated successfully.','aheadzen');
			}else{
				$oReturn->success->message = __('Activity comments added successfully.','aheadzen');
			}
		}else{
			$error = __('Something wrong to updated activity comments.','aheadzen');
		}
		$oReturn->error = $error;
		return  $oReturn;
	}
	
	/**
     * Supply post data
     * @param int userid: User ID
     * @param String content: Activity content
	 * @param int activityid: Activity Id for update
     * @return array message: success or error message
     */
	public function activity_add_edit()
	{
		/*
		//The data only for testing purpose.
		$_POST['content'] = '123 HELLO THIS IS TEST ACTIVITY FOR ME 456';
		$_POST['userid'] = 1;
		$_POST['activityid'] = 48;
		*/
		$error = '';
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->error = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['content']){$oReturn->error = __('Empty content.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->error = __('Wrong User Id.','aheadzen'); return $oReturn;}
		$content = $_POST['content'];
		$user_id = $_POST['userid'];
		$activityid = (int)$_POST['activityid'];
		
		$arg = array(
					'user_id'   => $user_id,
					'component' => 'activity',
					'type'      => 'activity_update',
					'content'   => $content
				);
		if($activityid){$arg['id'] = $activityid;} //update activity
		if($activity_id = bp_activity_add($arg)){
			$oReturn->success->id = $activity_id;
			if($activityid){
				$oReturn->success->message = __('Activity updated successfully.','aheadzen');
			}else{
				$oReturn->success->message = __('Activity added successfully.','aheadzen');
			}
		}else{
			if($activityid){
				$error = __('Something wrong to add activity.','aheadzen');
			}else{
				$error = __('Something wrong to updated activity.','aheadzen');
			}
		}
		$oReturn->error = $error;
		return  $oReturn;
	}
	
	/**
     * Supply post data
     * @param int userid: User ID
     * @param int activityid: Activity Id for update
     * @return array message: success or error message
     */
	public function activity_delete()
	{
		/*
		//The data only for testing purpose.
		$_POST['userid'] = 1;
		$_POST['activityid'] = 47;
		*/
		
		$error = '';
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->error = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['activityid']){$oReturn->error = __('Wrong activity Id.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->error = __('Wrong user Id.','aheadzen'); return $oReturn;}
		$user_id = $_POST['userid'];
		$activityid = (int)$_POST['activityid'];
		
		$arg = array(
					'id'  		 => $activityid,
					'user_id' 	=> $user_id
				);
		if ( bp_activity_delete($arg)){
			$oReturn->success->message = __( 'Activity deleted successfully', 'aheadzen');
		}else{
			$error =  __( 'There was an error when deleting that activity', 'aheadzen' );
		}
		$oReturn->error = $error;
		return  $oReturn;
	}
	
	public function profile_ionic_upload_photo()
	{
		/*
		//below details are only for testing purpose.
		$_POST['clicked_pic'] = 'profile_pic'; //'profile_pic'; //'cover_pic';
		$_POST['user_id'] = 1;
		$imageDataEncoded = base64_encode(file_get_contents('http://localhost/profile_pic_192063.jpg'));
		$_POST['picture_code']=$imageDataEncoded;
		*/	
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if($_FILES && $_FILES['file'] && $_FILES['file']['name']){ }else{$oReturn->message = __('Wrong picture.','aheadzen'); return $oReturn;}
		
		$clicked_pic = $_GET['clicked_pic'];
		$user_id = $_GET['user_id'];
		$bp_upload = xprofile_avatar_upload_dir('',$user_id);		
		$basedir = $bp_upload['path'];
		$baseurl = $bp_upload['url'];
		
		if($_FILES && $_FILES['file'] && $_FILES['file']['name'] && $_FILES['file']['size']>0 && $_FILES['file']['error']==0)
		{
			$tmp_name = $_FILES['file']['tmp_name'];
			$filename = $_FILES['file']['name'];
			$type = $_FILES['file']['type'];
			$size = $_FILES['file']['size'];
			
			$targetFile = $basedir.$filename;
			$targetFileURL = $baseurl.$filename;
			$uploadOk = 1;
			$imageFileType = pathinfo($targetFile,PATHINFO_EXTENSION);
			
			if(!file_exists($basedir)){@wp_mkdir_p( $basedir );}
			$filename = $clicked_pic.'_'.$user_id.'.'.$imageFileType;
			$outputFile = $basedir.'/'.$filename;
			$imageurl = $outputFileURL = $baseurl.'/'.$filename;
			
			// Check if image file is a actual image or fake image
			$check = getimagesize($tmp_name);
			if($check == false) {			
				$oReturn->error = __('File is not an image.','aheadzen');				
			}/*elseif ($size > 500000) { // Check file size
				$oReturn->error = __('Sorry, your file is too large.','aheadzen');
			}*/
			else // Allow certain file formats
			if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
				$oReturn->error = __('Sorry, only JPG, JPEG, PNG & GIF files are allowed.','aheadzen');
			}else{
				if (move_uploaded_file($tmp_name, $outputFile)) {
					if($outputFile){
						if($outputFile && $clicked_pic=='cover_pic'){
							update_user_meta( $user_id, 'bbp_cover_pic', $imageurl);
							$imageurl1 = $imageurl;
						}elseif($outputFile && $clicked_pic=='profile_pic'){
							$imgdata = @getimagesize( $outputFile );
							$img_width = $imgdata[0];
							$img_height = $imgdata[1];
							$upload_dir = wp_upload_dir();
							$existing_avatar_path = str_replace( $upload_dir['basedir'], '', $outputFile );
							$args = array(
								'item_id'       => $user_id,
								'original_file' => $existing_avatar_path,
								'crop_x'        => 0,
								'crop_y'        => 0,
								'crop_w'        => $img_width,
								'crop_h'        => $img_height
							);
							if (bp_core_avatar_handle_crop( $args ) ) {
								$imageurl1 = bp_core_fetch_avatar( array( 'item_id' => $user_id,'html'=>false,'type' => 'full'));
								// Add the activity
								if(function_exists('bp_activity_add')){
									bp_activity_add( array(
										'user_id'   => $user_id,
										'component' => 'profile',
										'type'      => 'new_avatar'
									));
								}
								$oReturn->success->image = $imageurl1;
								$oReturn->success->msg = 'Image uploaded successfully.';
							}else{
								$oReturn->error = 'Upload error';
							}
						}						
					}
					return $oReturn = $imageurl1;					
				}
			}
		}		
		
		$oReturn->imageurl = $outputFileURL;
		$oReturn->error = $error;
		return  $oReturn;
	
	}
	
	public function profile_upload_photo()
	{
		
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['picture_code']){$oReturn->message = __('Wrong picture.','aheadzen'); return $oReturn;}
		
		$clicked_pic = $_POST['clicked_pic'];
		$user_id = $_POST['user_id'];
		$picture_code = $_POST['picture_code'];	
		$bp_upload = xprofile_avatar_upload_dir('',$user_id);	
		
		$basedir = $bp_upload['path'];
		$baseurl = $bp_upload['url'];
		if(!file_exists($basedir)){@wp_mkdir_p( $basedir );}
		$filename = $clicked_pic.'_'.$user_id.'.jpg';
		$outputFile = $basedir.'/'.$filename;
		$imageurl = $outputFileURL = $baseurl.'/'.$filename;
		
		if(strstr($picture_code,'data:image/')){
			 $picture_code_arr = explode(',', $picture_code);
			$picture_code = $picture_code_arr[1];
		}
		
		$quality = 70;
		if(file_exists($outputFile)){@unlink($outputFile);}
		$data = base64_decode($picture_code);
		$image = imagecreatefromstring($data);
		$imageSave = imagejpeg($image, $outputFile, $quality);
		imagedestroy($image);
		if(!$imageSave){$oReturn->error = 'Image Save Error'; return  $oReturn;}
		if($outputFile && $clicked_pic=='cover_pic'){
			update_user_meta( $user_id, 'bbp_cover_pic', $imageurl);
		}elseif($outputFile && $clicked_pic=='profile_pic'){
			$imgdata = @getimagesize( $outputFile );
			$img_width = $imgdata[0];
			$img_height = $imgdata[1];
			$upload_dir = wp_upload_dir();
			$existing_avatar_path = str_replace( $upload_dir['basedir'], '', $outputFile );
			$args = array(
				'item_id'       => $user_id,
				'original_file' => $existing_avatar_path,
				'crop_x'        => 0,
				'crop_y'        => 0,
				'crop_w'        => $img_width,
				'crop_h'        => $img_height
			);
			
			if (bp_core_avatar_handle_crop( $args ) ) {
				$imageurl = bp_core_fetch_avatar( array( 'item_id' => $user_id,'html'=>false,'type' => 'full'));
				// Add the activity
				bp_activity_add( array(
					'user_id'   => $user_id,
					'component' => 'profile',
					'type'      => 'new_avatar'
				) );
				$oReturn->success->msg = 'Image uploaded successfully.';
			}else{
				$error = 'Upload error';
			}
		}
		$oReturn->imageurl = $imageurl;
		$oReturn->error = $error;
		return  $oReturn;
	
	}
	/************************************************
	EDIT PROFILE API
	The filed name should be like thefieldid_1, thefieldid_2,thefieldid_3,thefieldid_4.........
	where "thefieldid_" == is prefix variable and 1,2,3.... are the field id to store in buddypress db.
	api url : http://siteurl.com/api/buddypressread/profile_set_profile/
	************************************************/
	 public function profile_set_profile() {		
		
		//The data only for testing purpose.
		//$_POST['data']='{"1":"Test UserName","5":"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#039;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.\n","2":"Male","3":"Native American","4":"Average","21":"Fit","32":"Kosher","39":"Sometimes","43":"Sometimes","47":"English","6":"Afghanistan","7":"Surat"}';
		//$_POST['userid'] = 1;
		
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['data']){$oReturn->message = __('Wrong post data.','aheadzen'); return $oReturn;}
		$userid = $_POST['userid'];
		if(!$userid){$oReturn->message = 'Wrong user ID.'; return $oReturn;}
		if (!bp_has_profile(array('user_id' => $userid))) {
			return $this->error('xprofile', 0);
		}
		$data = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $_POST['data'] );
		$data = json_decode( stripslashes($data) );		
		
		foreach($data as $fieldid=>$val)
		{
			if($fieldid && $fieldid >0){
				$field_updated = xprofile_set_field_data( $fieldid, $userid, $val);
			}
		}
		
		// Add the activity
		bp_activity_add( array(
			'user_id'   => $userid,
			'component' => 'xprofile',
			'type'      => 'updated_profile'
		) );
		$oReturn->success->id = $userid;
		$oReturn->success->message = __('User Profile Updated Successfully.','aheadzen');
		return  $oReturn;
	 }
	 
	 public function activity_get_activity() {
		header("Access-Control-Allow-Origin: *");
        $oReturn = new stdClass();
		$oReturn->success = '';
        
		$activity_id = $_GET['activity_id'];
		global $table_prefix,$wpdb;
		if($activity_id){
			$res = $wpdb->get_results("select id,action,content,user_id,item_id,secondary_item_id,date_recorded from ".$table_prefix."bp_activity where id=\"$activity_id\"");
			if($res){
				$oActivity = $res[0];
				$oReturn->activitiy->id = $oActivity->id;
				$oReturn->activitiy->action = $oActivity->action;
				$oReturn->activitiy->content = $oActivity->content;
				$oReturn->activitiy->user_id = $oActivity->user_id;
				$oReturn->activitiy->item_id = $oActivity->item_id;
				$oReturn->activitiy->secondary_item_id = $oActivity->secondary_item_id;
				$oReturn->activitiy->date_recorded = $oActivity->date_recorded;
				
			}else{
				$oReturn->error = __('Wrong Activity Id.','aheadzen'); 
			}
		}
		return  $oReturn;
	 }
	 /**
     * Returns an Array with all activities
     * @param int pages: number of pages to display (default unset)
     * @param int offset: number of entries per page (default 10 if pages is set, otherwise unset)
     * @param int limit: number of maximum results (default 0 for unlimited)
     * @param String sort: sort ASC or DESC (default DESC)
     * @param String comments: 'stream' for within stream display, 'threaded' for below each activity item (default unset)
     * @param Int userid: userID to filter on, comma-separated for more than one ID (default unset)
     * @param String component: object to filter on e.g. groups, profile, status, friends (default unset)
     * @param String type: action to filter on e.g. activity_update, profile_updated (default unset)
     * @param int itemid: object ID to filter on e.g. a group_id or forum_id or blog_id etc. (default unset)
     * @param int secondaryitemid: secondary object ID to filter on e.g. a post_id (default unset)
     * @return array activities: an array containing the activities
     */
	 public function activity_get_activities_grouped() {
		header("Access-Control-Allow-Origin: *");
        $oReturn = new stdClass();
		$oReturn->success = '';
        $this->init('activity', 'see_activity');
		
		global $table_prefix,$wpdb;
		if(!$this->userid && $_GET['username']){
			$oUser = get_user_by('login', $_GET['username']);
			if($oUser){$this->userid = $oUser->data->ID;}
		}
		
		//$this->userid='1';
		
		$mentionid = $_GET['mentionid'];
		
		if($mentionid){
			global $wpdb,$table_prefix;
			$parent_activity = $wpdb->get_var("select item_id from ".$table_prefix."bp_activity where id=\"$mentionid\"");
			if($parent_activity==0){
				$parent_activity = $mentionid;
			}
			$aParams = array();
			$aParams ['display_comments'] = true;
			$aParams['in'] = array($parent_activity);
			//$aTempActivities = bp_activity_get($aParams);
		}else{
			
			$aParams ['display_comments'] = $this->comments;
			$aParams ['sort'] = $this->sort;		
			
			//if($this->userid){
				$aParams ['filter'] ['user_id'] = $this->userid;
				$aParams ['filter'] ['object'] = $this->component;
				$aParams ['filter'] ['type'] = $this->type;
				$aParams ['filter'] ['primary_id'] = $this->itemid;
				$aParams ['filter'] ['secondary_id'] = $this->secondaryitemid;
			//}
			$iLimit = $this->limit;
			
			$page = $_GET['thepage'];
			if(!$page){$page=1;}
			$per_page = $_GET['per_page'];
			if(!$per_page){$per_page=50;}
			$count_total = $_GET['count_total'];
			if(!$count_total){$count_total=100;}
			
			$aParams['page']=$page;
			$aParams['per_page']=$per_page;
			$aParams['count_total']=$count_total;
			
			$activities = trim($_GET['activities']);
			if($activities){
				$aParams['in']=$activities;
			}
		}
		
		if (!bp_has_activities($aParams))
			return $this->error('activity');
		if ($this->pages !== 1) {
			$aParams ['max'] = true;
			$aParams ['per_page'] = $this->offset;
			$iPages = $this->pages;
		}
		$aTempActivities = bp_activity_get($aParams);
		
		$theActivityGroup = array();
		if (!empty($aTempActivities['activities'])) {
				$acounter=0;
                foreach ($aTempActivities['activities'] as $oActivity) {
					if($oActivity->component=='votes'){ }else{
						if($oActivity->type=='updated_profile' || $oActivity->type=='new_avatar'){
							$theActivityGroup[$oActivity->component][$oActivity->type][$oActivity->item_id][0] = $oActivity;
						}else{
							if($oActivity->type=='new_forum_post'){
								$randVar = time().rand(1,1000);
								$theActivityGroup[$oActivity->component][$oActivity->type][$randVar][] = $oActivity;
							}else{
								$theActivityGroup[$oActivity->component][$oActivity->type][$oActivity->item_id][] = $oActivity;
							}
						}
					}
				}
				
				$activityFinalArr = array();
				if($theActivityGroup){
					foreach($theActivityGroup as $activityCompArr){
						foreach($activityCompArr as $activityTypeArr){
							foreach($activityTypeArr as $activityUerArr){
								$theStrArr = array();
								$varGrpName = '';
								$spliterStr = '';
								$multiActivity = 0;
								$newMembersArr = array();
								$spliterStr2 = '';
								if(count($activityUerArr)>1){
									$i=0;
									foreach($activityUerArr as $theAct){
										if($theAct->component=='groups' && $theAct->type=='joined_group'){
											$spliterStr = 'joined the group';											
										}else if($theAct->component=='birth_chart' && $theAct->type=='save_chart'){
											$spliterStr = 'just received';											
										}else if($theAct->component=='members' && $theAct->type=='new_member'){
											$spliterStr = 'became a registered member';
											$spliterStr2 = 'just registered.';
											$newMembersArr[] = $theAct->user_id;
										}
										if($spliterStr){
											$expActionArr = explode($spliterStr,$theAct->action);
											$theStrArr[] = trim($expActionArr[0]);
											$varGrpName = trim($expActionArr[1]);
											$multiActivity=1;
										}
										if($i==2){
											$others = (count($activityUerArr)-3);
											if($spliterStr2){$spliterStr = $spliterStr2;}
											if($others>=1){
												if($others>1){
													$spliterStr = 'and '.$others.' others ' . $spliterStr;
												}elseif($others==1){
													$spliterStr = 'and '.$others.' other '. $spliterStr;
												}
											}
											break;
										}
										$i++;
									}
									$theActivityVar = $activityUerArr[0];
									if(count($theStrArr)==2){$theSep = ' & ';}else{$theSep = ', ';}
									if($spliterStr){$spliterStr = ' '.$spliterStr.' ';}
									$theActivityVar->action = implode($theSep,$theStrArr).$spliterStr.$varGrpName;
									$theActivityVar->multiActivity = $multiActivity;
								}else{
									$activityUerArr[0]->multiActivity = 0;
									$theActivityVar=$activityUerArr[0];									
								}
								if($theActivityVar->component=='groups' && $this->component==''){
									$aGroup = groups_get_group( array( 'group_id' => $theActivityVar->item_id ) );
									if($aGroup){
										$Gname = $aGroup->name;
										$Gdescription = $aGroup->description;
										$Gslug = $aGroup->slug;
										$Gpermalink = site_url('/') . 'groups/' . $Gslug . '/';
									}
									$avatar_url = bp_core_fetch_avatar(array('object'=>'group','item_id'=>$theActivityVar->item_id, 'html'=>false, 'type'=>'full'));
									if($avatar_url && !strstr($avatar_url,'http:')){ $avatar_url = 'http:'.$avatar_url;}
									$theActivityVar->content = '<a href="'.$Gpermalink.'"><img src="'.$avatar_url.'" alt="'.$Gname.'" class="full-image" style="max-width:250px;height:auto;"></a>';									
								}else if($theActivityVar->component=='birth_chart' && $theActivityVar->type=='save_chart'){
									$post_thumbnail = get_the_post_thumbnail(4089,'medium',array( 'class' => 'full-image', 'style' => 'max-width:250px;height:auto;'));
									$birthChartLink = get_permalink(4089);
									if($post_thumbnail){
										$theActivityVar->content = '<a href="'.$birthChartLink.'">'.$post_thumbnail.'</a>';
									}
								}else if($theActivityVar->component=='members' && $theActivityVar->type=='new_member'){
									$contentStr =  '<div class="row activityJoinUsers">';
									if($newMembersArr){
										for($m=0;$m<count($newMembersArr);$m++){
											$user = new BP_Core_User($newMembersArr[$m]);
											
											if($user && $user->avatar){
												$avatar_thumb = $user->avatar_thumb;
												preg_match_all('/(src)=("[^"]*")/i',$avatar_thumb, $avatar_thumb_result);
												$avatar_thumb_src = str_replace('"','',$avatar_thumb_result[2][0]);
												if($avatar_thumb_src && !strstr($avatar_thumb_src,'http:')){ $avatar_thumb_src = 'http:'.$avatar_thumb_src;}
												$contentStr .= '<div class="col col-30"><a href="'.$user->user_url.'"><img src="'.$avatar_thumb_src.'" alt=""></a></div>';
											}
										}
									}
									$contentStr .= '</div>';
									$theActivityVar->content = $contentStr;
								}
								$activityFinalArr[]=$theActivityVar;
							}
						}
					}
				}
				
				if(!$activityFinalArr){return $oReturn;}
				for($a=0;$a<count($activityFinalArr);$a++){
					$oActivity = $activityFinalArr[$a];
					if($oActivity->type=='activity_comment'){
						
					}else{
						$user = new BP_Core_User($oActivity->user_id);
						if($user && $user->avatar){
							if($user->avatar_thumb){
								preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
								$thumb = str_replace('"','',$user_avatar_result[2][0]);
								if($thumb && !strstr($thumb,'http:')){ $thumb = 'http:'.$thumb;}
								$oActivity->avatar_thumb = $thumb;
							}							
						}
						
						$oReturn->activities[$acounter]->id = $oActivity->id;
						$oReturn->activities[$acounter]->component = $oActivity->component;
						$oReturn->activities[$acounter]->type = $oActivity->type;
						$oReturn->activities[$acounter]->user->id = $oActivity->user_id;
						$oReturn->activities[$acounter]->user->username = $oActivity->user_login;
						$oReturn->activities[$acounter]->user->mail = $oActivity->user_email;
						$oReturn->activities[$acounter]->user->display_name = $oActivity->user_fullname;
						//$oReturn->activities[$acounter]->user->avatar_big = $oActivity->avatar_big;
						$oReturn->activities[$acounter]->user->avatar_thumb = $oActivity->avatar_thumb;
						$oReturn->activities[$acounter]->item_id = $oActivity->item_id;
						$oReturn->activities[$acounter]->secondary_item_id = $oActivity->secondary_item_id;
						$oReturn->activities[$acounter]->time = $oActivity->date_recorded;
						$oReturn->activities[$acounter]->multiActivity = $oActivity->multiActivity;
						
						if($oActivity->type=='new_avatar'){
							//$oActivity->action = '<a href="'.$oActivity->primary_link.'">'.$oActivity->user_fullname.'</a> changed their profile picture. <br /><img src="'.$oActivity->avatar_thumb.'" alt="" />';
							$oActivity->action = 'Changed their profile picture. <br /><img src="'.$oActivity->avatar_thumb.'" alt="" />';
						}else if($oActivity->type=='updated_profile'){
							if($oActivity->action=='' && $oActivity->content==''){
								//$oActivity->action = '<a href="'.$oActivity->primary_link.'">'.$oActivity->user_fullname.'</a> changed their profile';
								$oActivity->action = 'Changed their profile';
							}										
						}
						$oReturn->activities[$acounter]->action = $oActivity->action;
						if(strlen($oActivity->content)>10){
							$oActivity->content = do_shortcode($oActivity->content);
						}						
						$srch = array('&rdquo;','&rdquo; ');
						$repl = array('"','"');
						if($oActivity->type=='new_blog_comment'){
							$oActivity->content = str_replace($srch,$repl,nl2br(wp_specialchars_decode($oActivity->content)));
						}
						$oReturn->activities[$acounter]->content = stripcslashes($oActivity->content);
						$oReturn->activities[$acounter]->is_hidden = $oActivity->hide_sitewide === "0" ? false : true;
						$oReturn->activities[$acounter]->is_spam = $oActivity->is_spam === "0" ? false : true;
						
						$total_votes = $total_up = $total_down = 0;
						$uplink = $downlink = '#';
						$voteed_action = 'up';
						if(class_exists('VoterPluginClass'))
						{
							$arg = array(
								'item_id'=>$oActivity->id,
								'user_id'=>$oActivity->user_id,
								'type'=>'activity',
								);
							
							$votes_str = VoterPluginClass::aheadzen_get_post_all_vote_details($arg);
							if($votes_str){
							$votes = json_decode($votes_str);
							$total_votes = $votes->total_votes;
							$total_up = $votes->total_up;
							$total_down = $votes->total_down;
							$uplink = $votes->post_voter_links->up;
							$downlink = $votes->post_voter_links->down;
							}
							if($_GET['userid']){
								$user_id = $oActivity->user_id;
								$secondary_item_id = $oActivity->id;
								$type = 'activity';
								$item_id = 0;
								$component = 'buddypress';
								$voteed_action = $wpdb->get_var("SELECT action FROM `".$table_prefix."ask_votes` WHERE user_id=\"$user_id\" AND item_id=\"$item_id\" AND component=\"$component\" AND type=\"$type\" AND secondary_item_id=\"$secondary_item_id\"");
							}
						}
						
						$oReturn->activities[$acounter]->vote->total_votes = $total_votes;
						$oReturn->activities[$acounter]->vote->total_up = $total_up;
						$oReturn->activities[$acounter]->vote->total_down = $total_down;
						$oReturn->activities[$acounter]->vote->uplink = $uplink;
						$oReturn->activities[$acounter]->vote->downlink = $downlink;
						$oReturn->activities[$acounter]->vote->action = $voteed_action;
						
					
						if($oActivity->children){
							//children
							$counter=0;
							foreach($oActivity->children as $childoActivity){
							$childuser = new BP_Core_User($childoActivity->user_id);
							if($childuser && $childuser->avatar){
								if($childuser->avatar_thumb){
									preg_match_all('/(src)=("[^"]*")/i',$childuser->avatar_thumb, $user_avatar_result);
									$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
									if($avatar_thumb && !strstr($avatar_thumb,'http:')){ $avatar_thumb = 'http:'.$avatar_thumb;}
									$childoActivity->avatar_thumb = $avatar_thumb;
								}
							}
							$oReturn->activities[$acounter]->children->$counter->id = $childoActivity->id;
							$oReturn->activities[$acounter]->children->$counter->item_id = $childoActivity->item_id;
							$oReturn->activities[$acounter]->children->$counter->component = $childoActivity->component;
							$oReturn->activities[$acounter]->children->$counter->user->id = (int)$childoActivity->user_id;
							$oReturn->activities[$acounter]->children->$counter->user->username = $childoActivity->user_login;
							$oReturn->activities[$acounter]->children->$counter->user->mail = $childoActivity->user_email;
							$oReturn->activities[$acounter]->children->$counter->user->display_name = $childoActivity->display_name;
							//$oReturn->activities[$acounter]->children->$counter->user->avatar_big = $childoActivity->avatar_big;
							$oReturn->activities[$acounter]->children->$counter->user->avatar_thumb = $childoActivity->avatar_thumb;
							$oReturn->activities[$acounter]->children->$counter->type = $childoActivity->type;
							$oReturn->activities[$acounter]->children->$counter->time = $childoActivity->date_recorded;
							$oReturn->activities[$acounter]->children->$counter->action = $childoActivity->action;
							$oReturn->activities[$acounter]->children->$counter->content = stripcslashes($childoActivity->content);
							$oReturn->activities[$acounter]->children->$counter->is_hidden = $childoActivity->hide_sitewide === "0" ? false : true;
							$oReturn->activities[$acounter]->children->$counter->is_spam = $childoActivity->is_spam === "0" ? false : true;
							$user = new BP_Core_User($childoActivity->user_id);
							
							$total_votes = $total_up = $total_down = 0;
							$uplink = $downlink = '#';
							$voteed_action = '';
							if(class_exists('VoterPluginClass'))
							{
								$arg = array(
									'item_id'=>$childoActivity->id,
									'user_id'=>$childoActivity->user_id,
									'type'=>'activity',
									//'component'=>'buddypress',
									);					
								$votes_str = VoterPluginClass::aheadzen_get_post_all_vote_details($arg);
								$votes = json_decode($votes_str);
								
								$total_votes = $votes->total_votes;
								$total_up = $votes->total_up;
								$total_down = $votes->total_down;
								$uplink = $votes->post_voter_links->up;
								$downlink = $votes->post_voter_links->down;
								
								if($_GET['userid']){
									$user_id = $childoActivity->user_id;
									$secondary_item_id = $childoActivity->id;
									$type = $childoActivity->type;
									$item_id = 0;
									$component = 'buddypress';
									$voteed_action = $wpdb->get_var("SELECT action FROM `".$table_prefix."ask_votes` WHERE user_id=\"$user_id\" AND item_id=\"$item_id\" AND component=\"$component\" AND type=\"$type\" AND secondary_item_id=\"$secondary_item_id\"");
									
								}
							}
							
							$oReturn->activities[$acounter]->children->$counter->vote->total_votes = $total_votes;
							$oReturn->activities[$acounter]->children->$counter->vote->total_up = $total_up;
							$oReturn->activities[$acounter]->children->$counter->vote->total_down = $total_down;
							$oReturn->activities[$acounter]->children->$counter->vote->uplink = $uplink;
							$oReturn->activities[$acounter]->children->$counter->vote->downlink = $downlink;
							$oReturn->activities[$acounter]->children->$counter->vote->action = $voteed_action;
							
							$counter++;
							}
							
						}
						$acounter++;
					}	
				}
				//echo '<pre>';
				//print_r($oReturn);
				//print_r($activityFinalArr);
				//print_r($theActivityGroup);
				
				$oReturn->total_pages = ceil($aTempActivities['total']/$per_page);
				$oReturn->total_count = $aTempActivities['total'];
            } else {
                return $this->error('activity');
            }
			
			//echo '<pre>';print_r($oReturn);echo '</pre>';
            return $oReturn;
	}
	/**
     * Returns an Array with all activities
     * @param int pages: number of pages to display (default unset)
     * @param int offset: number of entries per page (default 10 if pages is set, otherwise unset)
     * @param int limit: number of maximum results (default 0 for unlimited)
     * @param String sort: sort ASC or DESC (default DESC)
     * @param String comments: 'stream' for within stream display, 'threaded' for below each activity item (default unset)
     * @param Int userid: userID to filter on, comma-separated for more than one ID (default unset)
     * @param String component: object to filter on e.g. groups, profile, status, friends (default unset)
     * @param String type: action to filter on e.g. activity_update, profile_updated (default unset)
     * @param int itemid: object ID to filter on e.g. a group_id or forum_id or blog_id etc. (default unset)
     * @param int secondaryitemid: secondary object ID to filter on e.g. a post_id (default unset)
     * @return array activities: an array containing the activities
     */
	 public function activity_get_activities() {
		header("Access-Control-Allow-Origin: *");
        $oReturn = new stdClass();
		$oReturn->success = '';
        $this->init('activity', 'see_activity');
		
		global $table_prefix,$wpdb;
		if(!$this->userid && $_GET['username']){
			$oUser = get_user_by('login', $_GET['username']);
			if($oUser){$this->userid = $oUser->data->ID;}
		}
		
		//$this->userid='1';
		
		$mentionid = $_GET['mentionid'];
		
		if($mentionid){
			global $wpdb,$table_prefix;
			$parent_activity = $wpdb->get_var("select item_id from ".$table_prefix."bp_activity where id=\"$mentionid\"");
			if($parent_activity==0){
				$parent_activity = $mentionid;
			}
			$aParams = array();
			$aParams ['display_comments'] = true;
			$aParams['in'] = array($parent_activity);
			//$aTempActivities = bp_activity_get($aParams);
		}else{
			if (!bp_has_activities())
				return $this->error('activity');
			if ($this->pages !== 1) {
				$aParams ['max'] = true;
				$aParams ['per_page'] = $this->offset;
				$iPages = $this->pages;
			}

			$aParams ['display_comments'] = $this->comments;
			$aParams ['sort'] = $this->sort;		
			
			if($this->userid){
				$aParams ['filter'] ['user_id'] = $this->userid;
				$aParams ['filter'] ['object'] = $this->component;
				$aParams ['filter'] ['type'] = $this->type;
				$aParams ['filter'] ['primary_id'] = $this->itemid;
				$aParams ['filter'] ['secondary_id'] = $this->secondaryitemid;
			}
			$iLimit = $this->limit;
			
			$page = $_GET['thepage'];
			if(!$page){$page=1;}
			$per_page = $_GET['per_page'];
			if(!$per_page){$per_page=50;}
			$count_total = $_GET['count_total'];
			if(!$count_total){$count_total=100;}
			
			$aParams['page']=$page;
			$aParams['per_page']=$per_page;
			$aParams['count_total']=$count_total;			
			
			$activities = trim($_GET['activities']);
			if($activities){
				$aParams['in']=$activities;
			}
		}
		
		$aTempActivities = bp_activity_get($aParams);
		
		if (!empty($aTempActivities['activities'])) {
				$acounter=0;
                foreach ($aTempActivities['activities'] as $oActivity) {
					
					if($oActivity->type=='activity_comment'){
						
					}else{
						$user = new BP_Core_User($oActivity->user_id);
						if($user && $user->avatar){
							/*if($user->avatar){
								preg_match_all('/(src)=("[^"]*")/i',$user->avatar, $user_avatar_result);
								$oActivity->avatar_big = str_replace('"','',$user_avatar_result[2][0]);
							}*/
							if($user->avatar_thumb){
								preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
								$thumb = str_replace('"','',$user_avatar_result[2][0]);
								if($thumb && !strstr($thumb,'http:')){ $thumb = 'http:'.$thumb;}
								$oActivity->avatar_thumb = $thumb;
							}
							//preg_match_all('/(src)=("[^"]*")/i',$user->avatar_mini, $user_avatar_result);
							//$oActivity->avatar_mini = str_replace('"','',$user_avatar_result[2][0]);
						}
						
						$oReturn->activities[$acounter]->id = $oActivity->id;
						$oReturn->activities[$acounter]->component = $oActivity->component;
						$oReturn->activities[$acounter]->user->id = $oActivity->user_id;
						$oReturn->activities[$acounter]->user->username = $oActivity->user_login;
						$oReturn->activities[$acounter]->user->mail = $oActivity->user_email;
						$oReturn->activities[$acounter]->user->display_name = $oActivity->user_fullname;
						//$oReturn->activities[$acounter]->user->avatar_big = $oActivity->avatar_big;
						$oReturn->activities[$acounter]->user->avatar_thumb = $oActivity->avatar_thumb;
						$oReturn->activities[$acounter]->item_id = $oActivity->item_id;
						$oReturn->activities[$acounter]->secondary_item_id = $oActivity->secondary_item_id;
						$oReturn->activities[$acounter]->type = $oActivity->type;
						$oReturn->activities[$acounter]->time = $oActivity->date_recorded;
						
						if($oActivity->type=='new_avatar'){
							//$oActivity->action = '<a href="'.$oActivity->primary_link.'">'.$oActivity->user_fullname.'</a> changed their profile picture. <br /><img src="'.$oActivity->avatar_thumb.'" alt="" />';
							$oActivity->action = 'Changed their profile picture. <br /><img src="'.$oActivity->avatar_thumb.'" alt="" />';
						}else if($oActivity->type=='updated_profile'){
							if($oActivity->action=='' && $oActivity->content==''){
								//$oActivity->action = '<a href="'.$oActivity->primary_link.'">'.$oActivity->user_fullname.'</a> changed their profile';
								$oActivity->action = 'Changed their profile';
							}										
						}
						$oReturn->activities[$acounter]->action = $oActivity->action;
						if(strlen($oActivity->content)>10){
							$oActivity->content = do_shortcode($oActivity->content);
						}
						$oReturn->activities[$acounter]->content = stripcslashes($oActivity->content);
						$oReturn->activities[$acounter]->is_hidden = $oActivity->hide_sitewide === "0" ? false : true;
						$oReturn->activities[$acounter]->is_spam = $oActivity->is_spam === "0" ? false : true;
						
						$total_votes = $total_up = $total_down = 0;
						$uplink = $downlink = '#';
						$voteed_action = 'up';
						if(class_exists('VoterPluginClass'))
						{
							$arg = array(
								'item_id'=>$oActivity->id,
								'user_id'=>$oActivity->user_id,
								'type'=>'activity',
								);
							
							$votes_str = VoterPluginClass::aheadzen_get_post_all_vote_details($arg);
							if($votes_str){
							$votes = json_decode($votes_str);
							$total_votes = $votes->total_votes;
							$total_up = $votes->total_up;
							$total_down = $votes->total_down;
							$uplink = $votes->post_voter_links->up;
							$downlink = $votes->post_voter_links->down;
							}
							if($_GET['userid']){
								$user_id = $oActivity->user_id;
								$secondary_item_id = $oActivity->id;
								$type = 'activity';
								$item_id = 0;
								$component = 'buddypress';
								$voteed_action = $wpdb->get_var("SELECT action FROM `".$table_prefix."ask_votes` WHERE user_id=\"$user_id\" AND item_id=\"$item_id\" AND component=\"$component\" AND type=\"$type\" AND secondary_item_id=\"$secondary_item_id\"");
							}
						}
						
						$oReturn->activities[$acounter]->vote->total_votes = $total_votes;
						$oReturn->activities[$acounter]->vote->total_up = $total_up;
						$oReturn->activities[$acounter]->vote->total_down = $total_down;
						$oReturn->activities[$acounter]->vote->uplink = $uplink;
						$oReturn->activities[$acounter]->vote->downlink = $downlink;
						$oReturn->activities[$acounter]->vote->action = $voteed_action;
						$oReturn->activities[$acounter]->multiActivity = 0;
					
						if($oActivity->children){
							/*children*/
							$counter=0;
							foreach($oActivity->children as $childoActivity){
							$childuser = new BP_Core_User($childoActivity->user_id);
							if($childuser && $childuser->avatar){
								/*if($childuser->avatar){
									preg_match_all('/(src)=("[^"]*")/i',$childuser->avatar, $user_avatar_result);
									$childoActivity->avatar_big = str_replace('"','',$user_avatar_result[2][0]);
								}*/
								if($childuser->avatar_thumb){
									preg_match_all('/(src)=("[^"]*")/i',$childuser->avatar_thumb, $user_avatar_result);
									$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
									if($avatar_thumb && !strstr($avatar_thumb,'http:')){ $avatar_thumb = 'http:'.$avatar_thumb;}
									$childoActivity->avatar_thumb = $avatar_thumb;
								}
							}
							$oReturn->activities[$acounter]->children->$counter->id = $childoActivity->id;
							$oReturn->activities[$acounter]->children->$counter->item_id = $childoActivity->item_id;
							$oReturn->activities[$acounter]->children->$counter->component = $childoActivity->component;
							$oReturn->activities[$acounter]->children->$counter->user->id = (int)$childoActivity->user_id;
							$oReturn->activities[$acounter]->children->$counter->user->username = $childoActivity->user_login;
							$oReturn->activities[$acounter]->children->$counter->user->mail = $childoActivity->user_email;
							$oReturn->activities[$acounter]->children->$counter->user->display_name = $childoActivity->display_name;
							//$oReturn->activities[$acounter]->children->$counter->user->avatar_big = $childoActivity->avatar_big;
							$oReturn->activities[$acounter]->children->$counter->user->avatar_thumb = $childoActivity->avatar_thumb;
							$oReturn->activities[$acounter]->children->$counter->type = $childoActivity->type;
							$oReturn->activities[$acounter]->children->$counter->time = $childoActivity->date_recorded;
							$oReturn->activities[$acounter]->children->$counter->action = $childoActivity->action;
							$oReturn->activities[$acounter]->children->$counter->content = stripcslashes($childoActivity->content);
							$oReturn->activities[$acounter]->children->$counter->is_hidden = $childoActivity->hide_sitewide === "0" ? false : true;
							$oReturn->activities[$acounter]->children->$counter->is_spam = $childoActivity->is_spam === "0" ? false : true;
							$user = new BP_Core_User($childoActivity->user_id);
							
							$total_votes = $total_up = $total_down = 0;
							$uplink = $downlink = '#';
							$voteed_action = '';
							if(class_exists('VoterPluginClass'))
							{
								$arg = array(
									'item_id'=>$childoActivity->id,
									'user_id'=>$childoActivity->user_id,
									'type'=>'activity',
									//'component'=>'buddypress',
									);					
								$votes_str = VoterPluginClass::aheadzen_get_post_all_vote_details($arg);
								$votes = json_decode($votes_str);
								
								$total_votes = $votes->total_votes;
								$total_up = $votes->total_up;
								$total_down = $votes->total_down;
								$uplink = $votes->post_voter_links->up;
								$downlink = $votes->post_voter_links->down;
								
								if($_GET['userid']){
									$user_id = $childoActivity->user_id;
									$secondary_item_id = $childoActivity->id;
									$type = $childoActivity->type;
									$item_id = 0;
									$component = 'buddypress';
									$voteed_action = $wpdb->get_var("SELECT action FROM `".$table_prefix."ask_votes` WHERE user_id=\"$user_id\" AND item_id=\"$item_id\" AND component=\"$component\" AND type=\"$type\" AND secondary_item_id=\"$secondary_item_id\"");
									
								}
							}
							
							$oReturn->activities[$acounter]->children->$counter->vote->total_votes = $total_votes;
							$oReturn->activities[$acounter]->children->$counter->vote->total_up = $total_up;
							$oReturn->activities[$acounter]->children->$counter->vote->total_down = $total_down;
							$oReturn->activities[$acounter]->children->$counter->vote->uplink = $uplink;
							$oReturn->activities[$acounter]->children->$counter->vote->downlink = $downlink;
							$oReturn->activities[$acounter]->children->$counter->vote->action = $voteed_action;
							
							$counter++;
							}
							
						}
						$acounter++;
					}
				}
				
				//echo '<pre>';print_r($oReturn);exit;
				$oReturn->total_pages = ceil($aTempActivities['total']/$per_page);
				$oReturn->total_count = $aTempActivities['total'];
            } else {
                return $this->error('activity');
            }
			
			//echo '<pre>';print_r($oReturn);echo '</pre>';
            return $oReturn;
	}
	
	public function activity_mark_spam()
	{
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		
		$activity_id = $_GET['activityid'];
		if(!$activity_id){$oReturn->error = __('No Activity Id.','aheadzen'); return $oReturn;}
		
		/*$activity_data = bp_activity_get(array('in'=>$activity_id));
		if(!$activity_data['activities']){$oReturn->error = __('Wrong Activity.','aheadzen'); return $oReturn;}
		
		$activity = $activity_data['activities'][0];
		bp_activity_mark_as_spam($activity);
		*/
		
		global $wpdb,$table_prefix;
		$res = $wpdb->query("update ".$table_prefix."bp_activity set is_spam=1 where id=\"$activity_id\"");
		if($res){
			$oReturn->success->msg = __('Activity marked as spam successfully.','aheadzen');
			$oReturn->success->id = $activity_id;
		}else{
			$oReturn->error = __('May be wrong activity Id or already spammed.','aheadzen');		
		}		
		return $oReturn;
	}
	
	/**
		 * Returns an array with the profile's fields
		 * @param String username: the username you want information from (required)
		 * @return array profilefields: an array containing the profilefields
		 */
		public function profile_get_profile() {
			header("Access-Control-Allow-Origin: *");
			$this->userid = $_GET['userid'];
			$this->username = $_GET['username'];
			$this->init('xprofile');
			$oReturn = new stdClass();
			$oReturn->success = '';
			$error=0;
			
			if(($this->userid=='' && $this->username === false) || ($this->username && !username_exists($this->username))) {
				return $this->error('xprofile', 1);
			}
			if($this->userid){
				$userid = $this->userid;
			}else{
				$oUser = get_user_by('login', $this->username);
				$userid = $oUser->data->ID;
			}
			
			if (!bp_has_profile(array('user_id' => $userid))) {
				return $this->error('xprofile', 0);
			}
			while (bp_profile_groups(array('user_id' => $userid))) {
				bp_the_profile_group();
				if (bp_profile_group_has_fields()) {
					$sGroupName = bp_get_the_profile_group_name();
					while (bp_profile_fields()) {
						bp_the_profile_field();
						$sFieldName = bp_get_the_profile_field_name();
						if (bp_field_has_data()) {
						   $sFieldValue = strip_tags(bp_get_the_profile_field_value());
						}
						$oReturn->profilefields->$sGroupName->$sFieldName = $sFieldValue;
					}
				}
			}
			/* CUstom changes VAJ - 09-06-2015*/
			$user = new BP_Core_User( $userid );
			if($user->avatar){
				$user_avatar = $user->avatar;
				$avatar_thumb = $user->avatar_thumb;
				$avatar_mini = $user->avatar_mini;
				preg_match_all('/(src)=("[^"]*")/i',$user_avatar, $user_avatar_result);
				$user_avatar_src = str_replace('"','',$user_avatar_result[2][0]);
				if($user_avatar_src && !strstr($user_avatar_src,'http:')){ $user_avatar_src = 'http:'.$user_avatar_src;}
				preg_match_all('/(src)=("[^"]*")/i',$avatar_mini, $avatar_mini_result);
				$avatar_mini_src = str_replace('"','',$avatar_mini_result[2][0]);
				if($avatar_mini_src && !strstr($avatar_mini_src,'http:')){ $avatar_mini_src = 'http:'.$avatar_mini_src;}
				preg_match_all('/(src)=("[^"]*")/i',$avatar_thumb, $avatar_thumb_result);
				$avatar_thumb_src = str_replace('"','',$avatar_thumb_result[2][0]);
				if($avatar_thumb_src && !strstr($avatar_thumb_src,'http:')){ $avatar_thumb_src = 'http:'.$avatar_thumb_src;}
				
				$bbp_cover_pic = get_user_meta( $userid, 'bbp_cover_pic',true);
				if(!$bbp_cover_pic){$bbp_cover_pic=$user_avatar_src;}
				$oReturn->profilefields->photo->avatar = $bbp_cover_pic;
				$oReturn->profilefields->photo->avatar_big = $bbp_cover_pic;
				$oReturn->profilefields->photo->avatar_thumb = $avatar_thumb_src;
				$oReturn->profilefields->photo->avatar_mini = $user_avatar_src;
				$oReturn->profilefields->user->username = $user->profile_data['user_login'];
				$oReturn->profilefields->user->userid = $userid;			
				
				if(function_exists('bp_follow_total_follow_counts')){
					$oReturn->profilefields->follow_counts  = bp_follow_total_follow_counts( array( 'user_id' => $userid ) );
				}
				$oReturn->profilefields->is_following = 0;
				if(function_exists('bp_follow_is_following') && bp_follow_is_following(array('leader_id'=>$userid,'follower_id'=>$_GET['cuserid']))){
					$oReturn->profilefields->is_following = 1;
				}
			}
			/* CUstom changes VAJ - 09-06-2015*/
			return $oReturn;
		}

    /**
     * Returns an array with messages for the current username
     * @param String box: the box you the messages are in (possible values are 'inbox', 'sentbox', 'notices', default is 'inbox')
     * @param int per_page: items to be displayed per page (default 10)
     * @param boolean limit: maximum numbers of emtries (default no limit)
     * @return array messages: contains the messages
     */
    public function messages_get_messages() {
		header("Access-Control-Allow-Origin: *");
	   $this->init('messages');
        $oReturn = new stdClass();

        $aParams ['box'] = $this->box;
        $aParams ['per_page'] = $this->per_page;
        $aParams ['max'] = $this->limit;
		$aParams ['user_id'] = $_GET['userid'];
		
		$counter = 0;
        if (bp_has_message_threads($aParams)) {
			global $messages_template;
            while (bp_message_threads()) {
                bp_message_thread();
				$aTemp = new stdClass();
				preg_match("#>(.*?)<#", bp_get_message_thread_from(), $aFrom);
				$oUser = get_user_by('login', $aFrom[1]);
				
				$aTemp->from->id = $oUser->data->ID;
				$aTemp->from->username = $aFrom[1];
                $aTemp->from->mail = $oUser->data->user_email;
                $aTemp->from->display_name = $oUser->data->display_name;
				
				preg_match_all('/(src)=("[^"]*")/i',bp_get_message_thread_avatar(), $user_avatar_result);
				$aTemp->from->avatar = str_replace('"','',$user_avatar_result[2][0]);
				preg_match("#>(.*?)<#", bp_get_message_thread_to(), $aTo);
				$oUser = get_user_by('login', $aTo[1]);
                $aTemp->to->id = $oUser->data->ID;
				$aTemp->to->username = $aTo[1];
                $aTemp->to->mail = $oUser->data->user_email;
                $aTemp->to->display_name = $oUser->data->display_name;
				
				$message_id =  bp_get_message_thread_id();
                $aTemp->message_id = $message_id;
				$aTemp->subject = bp_get_message_thread_subject();
                $aTemp->excerpt = bp_get_message_thread_excerpt();
                $aTemp->link = bp_get_message_thread_view_link();
				$aTemp->date = bp_get_message_thread_last_post_date_raw();
				$aTemp->unread = bp_message_thread_has_unread($_GET['userid']);				
				$aTemp->thread_total_count = bp_get_message_thread_total_count($message_id);
				$oReturn->messages [$counter] = $aTemp;
				$counter++;
            }
        } else {
            return $this->error('messages');
        }
		//echo '<pre>';print_r($oReturn);echo '</pre>';
		return $oReturn;
    }
	
	function messages_read_unread(){
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		$messageId = $_GET['messageId'];
		if(!$messageId){$oReturn->error = __('Wrong message ID.','aheadzen');}
		
		if($_GET['readUnread']=='read'){
			messages_mark_thread_read($messageId);
			$oReturn->success = __('Marked as read successfully.','aheadzen');
		}else if($_GET['readUnread']=='unread'){
			messages_mark_thread_unread($messageId);
			$oReturn->success = __('Marked as unread successfully.','aheadzen');
		}
		return $oReturn;	
	}
	
	function messages_delete_messages(){
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		$messageId = $_GET['messageId'];
		if(!$messageId){$oReturn->error = __('Wrong message ID.','aheadzen');}
		if(messages_delete_thread($messageId)){
			$oReturn->success = __('Message Deleted Successfully.','aheadzen');
		}else{
			$oReturn->success = __('Cannot delete the message, something wrong.','aheadzen');
		}		
		return $oReturn;	
	}
	
	function messages_get_detail(){
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		$messageId = $_GET['messageId'];
		if(!$messageId){$oReturn->error = __('Wrong message ID.','aheadzen');}
		
		if(bp_thread_has_messages(array('thread_id'=>$messageId))){
			$oReturn->message->id = $messageId;
			$oReturn->message->subject = bp_get_the_thread_subject();
			$oReturn->message->recipients_count = bp_get_thread_recipients_count();
			$oReturn->message->max_thread_recipients_to = bp_get_max_thread_recipients_to_list();
			if(bp_get_thread_recipients_count() <= 1){
				$oReturn->message->conversation = __( 'You are alone in this conversation.', 'buddypress' );
			}else if( bp_get_max_thread_recipients_to_list() <= bp_get_thread_recipients_count() ){
				$oReturn->message->conversation = sprintf( __( 'Conversation between %s recipients.', 'buddypress' ), number_format_i18n( bp_get_thread_recipients_count() ) );
			}else{
				$oReturn->message->conversation = sprintf( __( 'Conversation between %s and you.', 'buddypress' ), bp_get_thread_recipients_list() );
			}
			
			$counter = 0;
			while(bp_thread_messages()){
				bp_thread_the_message();
				preg_match_all('/(src)=("[^"]*")/i',bp_get_the_thread_message_sender_avatar_thumb(), $user_avatar_result);
				
				$avatar = str_replace('"','',$user_avatar_result[2][0]);
				if($avatar && !strstr($avatar,'http:')){ $avatar = 'http:'.$avatar;}				
				$oReturn->message->threads[$counter]->avatar = $avatar;
				$oReturn->message->threads[$counter]->sender_id = bp_get_the_thread_message_sender_id();
				$oReturn->message->threads[$counter]->sender_name = bp_get_the_thread_message_sender_name();
				$oReturn->message->threads[$counter]->time_since = bp_get_the_thread_message_time_since();
				$oReturn->message->threads[$counter]->content = bp_get_the_thread_message_content();
				
				$counter++;
			}
		}
		
		//echo '<pre>';print_r($oReturn);
		return $oReturn;	
	}
	
	function messages_set_reply(){
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if(!$_POST){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		if(!$_POST['text']){$oReturn->message = __('Please senter proper comments.','aheadzen'); return $oReturn;}
		if(!$_POST['userid']){$oReturn->message = __('Wrong user try.','aheadzen'); return $oReturn;}
		if(!$_POST['thread_id']){$oReturn->message = __('Wrong message trying.','aheadzen'); return $oReturn;}
		
		$result = messages_new_message( array('thread_id'=>(int)$_POST['thread_id'], 'content' => $_POST['text'], 'sender_id' => $_POST['userid'] ) );
		if(!empty( $result )){
			$oReturn->success->msg = __('Message reply added successfully.','aheadzen');
			$oReturn->success->id = $result;
		}else{
			$oReturn->error = __('Message reply add error.','aheadzen');
		}
		//echo '<pre>';print_r($oReturn);
		return $oReturn;
	}
    /**
     * Returns an array with notifications for the current user
     * @param none there are no parameters to be used
     * @return array notifications: the notifications as a link
     */
    public function notifications_get_notifications() {
        header("Access-Control-Allow-Origin: *");
		$this->init('notifications');
        $oReturn = new stdClass();
		$oReturn->msg = '';
		$oReturn->success = '';
		$oReturn->error = '';
		
		if(!$_GET['userid']){$oReturn->message = __('Not the post method.','aheadzen'); return $oReturn;}
		
		$aNotifications = bp_core_get_notifications_for_user($_GET['userid'],'object');
		
		if (empty($aNotifications)) {
            return $this->error('notifications');
        }
		
		$counter = 0;
		$userDataArr = array();
        foreach ($aNotifications as $sNotificationMessage) {
            $oReturn->notifications[$counter]->id = $sNotificationMessage->id;
			$oReturn->notifications[$counter]->item_id = $sNotificationMessage->item_id;
			$oReturn->notifications[$counter]->secondary_item_id = $sNotificationMessage->secondary_item_id;
			$oReturn->notifications[$counter]->content = $sNotificationMessage->content;
			$oReturn->notifications[$counter]->component_name = $sNotificationMessage->component_name;
			$oReturn->notifications[$counter]->component_action = $sNotificationMessage->component_action;
			$oReturn->notifications[$counter]->date_notified = $sNotificationMessage->date_notified;
			
			$userid = 0;
			$avatar_thumb_default = 'img/thumb_default.png';
			$activity_thumb = 'img/activity.png';
			$messages_thumb = 'img/messages.png';
			$vote_thumb = 'img/vote.png';
			$friend_thumb = 'img/friend.png';
			
			$avatar_thumb = '';
			if($sNotificationMessage->component_name=='follow' || $sNotificationMessage->component_name=='friends'){
				$userid = intval($sNotificationMessage->item_id);
				$avatar_thumb = $friend_thumb;
			}elseif($sNotificationMessage->component_name=='votes'){
				$component_action = $sNotificationMessage->component_action;
				$component_action_arr = explode('-+',$component_action);
				if(count($component_action_arr)<=1){
					$component_action_arr = explode('_',$component_action);	
				}
				$type = $component_action_arr[0];
				$userid = intval($component_action_arr[1]);
				$avatar_thumb = $vote_thumb;
			}elseif($sNotificationMessage->component_name=='activity'){
				$oReturn->notifications[$counter]->user->avatar_thumb = $activity_thumb;
			}elseif($sNotificationMessage->component_name=='messages'){
				$oReturn->notifications[$counter]->user->avatar_thumb = $messages_thumb;
			}
			
			if($userid && $userid>0){
				if($userDataArr && $userDataArr[$userid]){
					$user = $userDataArr[$userid];
				}else{
					$user = new BP_Core_User($userid);
					$userDataArr[$userid] = $user;
				}
				if($user){
					if($user->avatar_thumb){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
						$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_thumb && !strstr($avatar_thumb,'http:')){ $avatar_thumb = 'http:'.$avatar_thumb;}							
					}
					if(!$avatar_thumb){$avatar_thumb=$avatar_thumb_default;}
					$oReturn->notifications[$counter]->user->id = $user->id;
					$oReturn->notifications[$counter]->user->fullname = $user->fullname;
					$oReturn->notifications[$counter]->user->last_active = $user->last_active;
					$oReturn->notifications[$counter]->user->email = $user->email;
					$oReturn->notifications[$counter]->user->avatar_thumb = $avatar_thumb;
				}
			}
			$counter++;
        }
		//echo '<pre>';
		//print_r($aNotifications);
		//print_r($oReturn);exit;
        $oReturn->count = $counter;

        return $oReturn;
    }

    /**
     * Returns an array with friends for the given user
     * @param String username: the username you want information from (required)
     * @return array friends: array with the friends the user got
     */
    public function friends_get_friends() {
        $this->init('friends');
        $oReturn = new stdClass();
		if($_GET['userid']){
			$oUser = get_user_by('id',$_GET['userid']);
		}else{
			if ($this->username === false || !username_exists($this->username)) {
				return $this->error('friends', 0);
			}
			$oUser = get_user_by('login', $this->username);
		}

        

        $sFriends = bp_get_friend_ids($oUser->data->ID);
        $aFriends = explode(",", $sFriends);
        if ($aFriends[0] == "")
            return $this->error('friends', 1);
        foreach ($aFriends as $sFriendID) {
            $oUser = get_user_by('id', $sFriendID);
            $oReturn->friends [(int) $sFriendID]->username = $oUser->data->user_login;
            $oReturn->friends [(int) $sFriendID]->display_name = $oUser->data->display_name;
            $oReturn->friends [(int) $sFriendID]->mail = $oUser->data->user_email;
        }
        $oReturn->count = count($aFriends);
        return $oReturn;
    }

    /**
     * Returns an array with friendship requests for the given user
     * @params String username: the username you want information from (required)
     * @return array friends: an array containing friends with some mor info
     */
    public function friends_get_friendship_request() {
        $this->init('friends');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('friends', 0);
        }
        $oUser = get_user_by('login', $this->username);

        if (!is_user_logged_in() || get_current_user_id() != $oUser->data->ID)
            return $this->error('base', 0);

        $sFriends = bp_get_friendship_requests($oUser->data->ID);
        $aFriends = explode(",", $sFriends);

        if ($aFriends[0] == "0")
            return $this->error('friends', 2);
        foreach ($aFriends as $sFriendID) {
            $oUser = get_user_by('id', $sFriendID);
            $oReturn->friends [(int) $sFriendID]->username = $oUser->data->user_login;
            $oReturn->friends [(int) $sFriendID]->display_name = $oUser->data->display_name;
            $oReturn->friends [(int) $sFriendID]->mail = $oUser->data->user_email;
        }
        $oReturn->count = count($oReturn->friends);
        return $oReturn;
    }

    /**
     * Returns a string with the status of friendship of the two users
     * @param String username: the username you want information from (required)
     * @param String friendname: the name of the possible friend (required)
     * @return string friendshipstatus: 'is_friend', 'not_friends' or 'pending'
     */
    public function friends_get_friendship_status() {
        $this->init('friends');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('friends', 0);
        }

        if ($this->friendname === false || !username_exists($this->friendname)) {
            return $this->error('friends', 3);
        }

        $oUser = get_user_by('login', $this->username);
        $oUserFriend = get_user_by('login', $this->friendname);

        $oReturn->friendshipstatus = friends_check_friendship_status($oUser->data->ID, $oUserFriend->data->ID);
        return $oReturn;
    }
	
	function groups_get_groupdetail()
	{
		$this->init('forums');
		$oReturn = new stdClass();
		
		global $wpdb,$table_prefix;
		if($_GET['groupId']){
			$group_id = $_GET['groupId'];
		}elseif($_GET['groupSlug']){
			$groupSlug = $_GET['groupSlug'];
			$group = $wpdb->get_row("select id from ".$table_prefix."bp_groups where slug=\"$groupSlug\"");
			$group_id = $group->id;
		}
		
		if(!$group_id){ $oReturn->error = __('Wrong group id.','aheadzen'); return $oReturn;}
		$aGroup = groups_get_group( array( 'group_id' => $group_id ) );
		if($aGroup){
			$oReturn->groupfields->id = $aGroup->id;
			$oReturn->groupfields->name = $aGroup->name;
            $oReturn->groupfields->description = $aGroup->description;
            $oReturn->groupfields->status = $aGroup->status;
           
			$oUser = get_user_by('id', $aGroup->creator_id);
			$useravatar_url = bp_core_fetch_avatar(array('object'=>'user','item_id'=>$aGroup->creator_id, 'html'=>false, 'type'=>'full'));
            if($useravatar_url && !strstr($useravatar_url,'http:')){ $useravatar_url = 'http:'.$useravatar_url;}
			$oReturn->groupfields->creator->userid = $aGroup->creator_id;
			$oReturn->groupfields->creator->username = $oUser->data->user_login;
            $oReturn->groupfields->creator->mail = $oUser->data->user_email;
            $oReturn->groupfields->creator->display_name = $oUser->data->display_name;
			$oReturn->groupfields->creator->avatar = $useravatar_url;
            $oReturn->groupfields->slug = $aGroup->slug;
            $oReturn->groupfields->is_forum_enabled = $aGroup->enable_forum == "1" ? true : false;
            $oReturn->groupfields->date_created = $aGroup->date_created;
            $oReturn->groupfields->count_member = $aGroup->total_member_count;
			
			$avatar_url = bp_core_fetch_avatar(array('object'=>'group','item_id'=>$aGroup->id, 'html'=>false, 'type'=>'full'));
			if($avatar_url && !strstr($avatar_url,'http:')){ $avatar_url = 'http:'.$avatar_url;}
			$oReturn->groupfields->avatar = $avatar_url;
			
			if($iForumId = groups_get_groupmeta($aGroup->id, 'forum_id')){
				if(is_array($iForumId)){
					$iForumId = $iForumId[0];
				}
				if($iForumId){
					$oForum = bp_forums_get_forum((int) $iForumId);
					if($oForum){
						$oReturn->groupfields->forum->id = $oForum->forum_id;
						$oReturn->groupfields->forum->name = $oForum->forum_name;
						$oReturn->groupfields->forum->slug = $oForum->forum_slug;
						$oReturn->groupfields->forum->description = $oForum->forum_desc;
						$oReturn->groupfields->forum->topics_count = (int) $oForum->topics;
						$oReturn->groupfields->forum->post_count = (int) $oForum->posts;
					}
				}
			}
		}
		
		return $oReturn;
	}
	
	public function groups_get_nameonly() {
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';		
		$user_id = $_GET['userid'];
		if(!$user_id){ $oReturn->error = __('Wrong user id.','aheadzen'); return $oReturn;}
		$arg = array('user_id'=>$user_id,'orderby'=>'name','order'=>'ASC');
		$aGroups = groups_get_groups($arg);
		$counter=0;
		if($aGroups){
			foreach($aGroups['groups'] as $grpObj){
				$oReturn->group[$counter]->id = $grpObj->id;
				$oReturn->group[$counter]->name = $grpObj->name;
				$counter++;	
			}
		}else{
			$oReturn->error = __('No data available.','aheadzen');
		}
		return $oReturn;
	}
	
	public function groups_join_unjoin_group() {
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';		
		$user_id = $_GET['userid'];
		$groupid = $_GET['groupid'];
		$actionType = $_GET['actionType'];
		if(!$user_id){ $oReturn->error = __('Wrong User id.','aheadzen'); return $oReturn;}
		if(!$groupid){ $oReturn->error = __('Wrong Group id.','aheadzen'); return $oReturn;}
		
		if($actionType=='leave_group'){
			$member = new BP_Groups_Member( $user_id, $groupid );
			do_action( 'groups_remove_member', $groupid, $user_id );
			if ($member->remove()) {
				$oReturn->success->msg = __('Group Left Successfully.','aheadzen');
				$oReturn->success->group_id = $groupid;
				$oReturn->success->user_id = $user_id;
			} else {
				$oReturn->error = __('Group Unjoin Error.','aheadzen');
			}
		}else{
			if ( ! groups_join_group( $groupid, $user_id ) ) {
				$oReturn->error = __('Group Join Error.','aheadzen');
			} else {
				$oReturn->success->msg = __('Group Join Successfully.','aheadzen');
				$oReturn->success->group_id = $groupid;
				$oReturn->success->user_id = $user_id;
			}
		}		
		
		return $oReturn;
	}
	
	public function user_get_groups() {
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';		
		$user_id = $_GET['userid'];
		if(!$user_id){ $oReturn->error = __('Wrong user id.','aheadzen'); return $oReturn;}
		global $wpdb, $table_prefix;
		$res = $wpdb->get_results("select group_id,date_modified from ".$table_prefix."bp_groups_members where user_id=\"$user_id\" and is_confirmed=1 order by group_id asc");
		$counter=0;
		if($res){
			foreach($res as $resObj){
				$oReturn->memberGroups[$counter]->id = $resObj->group_id;
				$oReturn->memberGroups[$counter]->date_modified = $resObj->date_modified;
				$counter++;	
			}
		}else{
			$oReturn->error = __('No data available.','aheadzen');
		}
		return $oReturn;
	}
	
	/**
     * Returns an array with groups matching to the given parameters
     * @param String username: the username you want information from (default => all groups)
     * @param Boolean show_hidden: Show hidden groups to non-admins (default: false)
     * @param String type: active, newest, alphabetical, random, popular, most-forum-topics or most-forum-posts (default active)
     * @param int page: The page to return if limiting per page (default 1)
     * @param int per_page: The number of results to return per page (default 20)
     * @return array groups: array with meta infos
     */
    public function groups_get_groups() {
        //$this->init('groups');
		$this->init('forums');
		$oReturn = new stdClass();

        if ($this->username !== false || username_exists($this->username)) {
            $oUser = get_user_by('login', $this->username);
            $aParams ['user_id'] = $oUser->data->ID;
        }

        $aParams ['show_hidden'] = $this->show_hidden;
        $aParams ['type'] = $this->type;
        $aParams ['page'] = $this->page;
        $aParams ['per_page'] = $this->per_page;
		$aParams ['order'] = 'ASC';
		$aParams ['orderby'] = 'last_activity';
		if($_GET['keyword']){
			$keyword = trim($_GET['keyword']);
			global $wpdb,$table_prefix;
			$groupIDs = $wpdb->get_col("select id from ".$table_prefix."bp_groups where name like \"$keyword%\"");
			$aParams['include'] = 'abc';
			if($groupIDs){
				$aParams['include'] = $groupIDs;
			}
		}
		$aGroups = groups_get_groups($aParams);
		
		if ($aGroups['total'] == "0")
            return $this->error('groups', 0);
		
		$counter = 0;
        foreach ($aGroups['groups'] as $aGroup) {
			$oReturn->groups[$counter]->id = $aGroup->id;
			$oReturn->groups[$counter]->name = $aGroup->name;
            $oReturn->groups[$counter]->description = $aGroup->description;
            $oReturn->groups[$counter]->status = $aGroup->status;
            if ($aGroup->status == "private" && !is_user_logged_in() && !$aGroup->is_member === true)
                continue;
            $oUser = get_user_by('id', $aGroup->creator_id);
			$useravatar_url = bp_core_fetch_avatar(array('object'=>'user','item_id'=>$aGroup->creator_id, 'html'=>false, 'type'=>'full'));
			if($useravatar_url && !strstr($useravatar_url,'http:')){ $useravatar_url = 'http:'.$useravatar_url;}
            $oReturn->groups[$counter]->creator->userid = $aGroup->creator_id;
			$oReturn->groups[$counter]->creator->username = $oUser->data->user_login;
            $oReturn->groups[$counter]->creator->mail = $oUser->data->user_email;
            $oReturn->groups[$counter]->creator->display_name = $oUser->data->display_name;
			$oReturn->groups[$counter]->creator->avatar = $useravatar_url;
            $oReturn->groups[$counter]->slug = $aGroup->slug;
            $oReturn->groups[$counter]->is_forum_enabled = $aGroup->enable_forum == "1" ? true : false;
            $oReturn->groups[$counter]->date_created = $aGroup->date_created;
            $oReturn->groups[$counter]->count_member = $aGroup->total_member_count;
			
			$avatar_url = bp_core_fetch_avatar(array('object'=>'group','item_id'=>$aGroup->id, 'html'=>false, 'type'=>'full'));
			if($avatar_url && !strstr($avatar_url,'http:')){ $avatar_url = 'http:'.$avatar_url;}
			$oReturn->groups[$counter]->avatar = $avatar_url;
			
			$iForumId = groups_get_groupmeta($aGroup->id, 'forum_id');
			if(is_array($iForumId)){
				$iForumId = $iForumId[0];
			}
			if($iForumId){
				$oForum = bp_forums_get_forum((int) $iForumId);
				if($oForum){
					$oReturn->groups[$counter]->forum->id = $oForum->forum_id;
					$oReturn->groups[$counter]->forum->name = $oForum->forum_name;
					$oReturn->groups[$counter]->forum->slug = $oForum->forum_slug;
					$oReturn->groups[$counter]->forum->description = $oForum->forum_desc;
					$oReturn->groups[$counter]->forum->topics_count = (int) $oForum->topics;
					$oReturn->groups[$counter]->forum->post_count = (int) $oForum->posts;
				}
			}
			
			$isGroupAdmin = $isMember = $isBanned = 0;
			if($_GET['currentUser']){
				if($aGroup->creator_id==$_GET['currentUser']){					
					$isGroupAdmin = 1;
				}
				$isMember = groups_is_user_member($_GET['currentUser'],$aGroup->id);
				$isBanned = groups_is_user_banned($_GET['currentUser'],$aGroup->id);
			}
			$oReturn->groups[$counter]->is_admin = $isGroupAdmin;
			$oReturn->groups[$counter]->is_member = $isMember;
			$oReturn->groups[$counter]->is_banned = $isBanned;
			
			
			$counter++;
        }
		
		$oReturn->count = count($aGroups['groups']);
		return $oReturn;
    }

    /**
     * Returns a boolean depending on an existing invite
     * @param String username: the username you want information from (required)
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @param String type: sent to check for sent invites, all to check for all
     * @return boolean is_invited: true if invited, else false
     */
    public function groups_check_user_has_invite_to_group() {
        $this->init('groups');

        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('groups', 1);
        }
        $oUser = get_user_by('login', $this->username);

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('groups', $mGroupName);

        if ($this->type === false || $this->type != "sent" || $this->type != "all")
            $this->type = 'sent';

        $oReturn->is_invited = groups_check_user_has_invite((int) $oUser->data->ID, $this->groupid, $this->type);
        $oReturn->is_invited = is_null($oReturn->is_invited) ? false : true;

        return $oReturn;
    }

    /**
     * Returns a boolean depending on an existing memebership request
     * @param String username: the username you want information from (required)
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return boolean membership_requested: true if requested, else false
     */
    public function groups_check_user_membership_request_to_group() {
        $this->init('groups');

        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('groups', 1);
        }
        $oUser = get_user_by('login', $this->username);

        $mGroupName = $this->get_group_from_params();

        if ($mGroupName !== true)
            return $this->error('groups', $mGroupName);

        $oReturn->membership_requested = groups_check_for_membership_request((int) $oUser->data->ID, $this->groupid);
        $oReturn->membership_requested = is_null($oReturn->membership_requested) ? false : true;

        return $oReturn;
    }

    /**
     * Returns an array containing all admins for the given group
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return array group_admins: array containing the admins
     */
    public function groups_get_group_admins() {
        $this->init('groups');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('groups', $mGroupExists);

        $aGroupAdmins = groups_get_group_admins($this->groupid);
        foreach ($aGroupAdmins as $oGroupAdmin) {
            $oUser = get_user_by('id', $oGroupAdmin->user_id);
            $oReturn->group_admins[(int) $oGroupAdmin->user_id]->username = $oUser->data->user_login;
            $oReturn->group_admins[(int) $oGroupAdmin->user_id]->mail = $oUser->data->user_email;
            $oReturn->group_admins[(int) $oGroupAdmin->user_id]->display_name = $oUser->data->display_name;
        }
        $oReturn->count = count($aGroupAdmins);
        return $oReturn;
    }

    /**
     * Returns an array containing all mods for the given group
     * @params int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @params String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return array group_mods: array containing the mods
     */
    public function groups_get_group_mods() {
        $this->init('groups');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('groups', $mGroupExists);

        $oReturn->group_mods = groups_get_group_mods($this->groupid);
        $aGroupMods = groups_get_group_mods($this->groupid);
        foreach ($aGroupMods as $aGroupMod) {
            $oUser = get_user_by('id', $aGroupMod->user_id);
            $oReturn->group_mods[(int) $aGroupMod->user_id]->username = $oUser->data->user_login;
            $oReturn->group_mods[(int) $aGroupMod->user_id]->mail = $oUser->data->user_email;
            $oReturn->group_mods[(int) $aGroupMod->user_id]->display_name = $oUser->data->display_name;
        }
        return $oReturn;
    }

    /**
     * Returns an array containing all members for the given group
     * @params int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @params String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @params int limit: maximum members displayed
     * @return array group_members: group members with some more info
     */
    public function groups_get_group_members() {
        $this->init('groups');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();
		
		if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('groups', $mGroupExists);
		
		$aMembers = groups_get_group_members($this->groupid, $this->limit);
		
        if ($aMembers === false) {
            $oReturn->group_members = array();
            $oReturn->count = 0;
            return $oReturn;
        }
		$counter=0;
        foreach ($aMembers['members'] as $aMember) {
			if($aMember->user_id){
				$oReturn->group_members[$counter]->id = $aMember->user_id;
				$oReturn->group_members[$counter]->username = $aMember->user_login;
				$oReturn->group_members[$counter]->mail = $aMember->user_email;
				$oReturn->group_members[$counter]->display_name = $aMember->display_name;
				//$oReturn->group_members[$counter]->fullname = $aMember->fullname;
				$oReturn->group_members[$counter]->nicename = $aMember->user_nicename;
				$oReturn->group_members[$counter]->registered = $aMember->user_registered;
				$oReturn->group_members[$counter]->last_activity = $aMember->last_activity;
				$oReturn->group_members[$counter]->friend_count = $aMember->total_friend_count;
				$avatar_url = bp_core_fetch_avatar(array('object'=>'user','item_id'=>$aMember->user_id, 'html'=>false, 'type'=>'full'));
				if($avatar_url && !strstr($avatar_url,'http:')){ $avatar_url = 'http:'.$avatar_url;}
				$oReturn->group_members[$counter]->avatar = $avatar_url;
				
				if(function_exists('bp_follow_total_follow_counts')){
					$oReturn->group_members[$counter]->follow_counts  = bp_follow_total_follow_counts( array( 'user_id' => $aMember->user_id ) );
				}
				$oReturn->group_members[$counter]->is_following = 0;
				if(function_exists('bp_follow_is_following') && $_GET['userid'] && bp_follow_is_following(array('leader_id'=>$aMember->user_id,'follower_id'=>$_GET['userid']))){
					$oReturn->group_members[$counter]->is_following = 1;
				}
				$counter++;
			}
        }
		$oReturn->count = $counter;
		
		//echo '<pre>';print_r($oReturn);
        return $oReturn;
    }

    /**
     * Returns an array containing info about the group forum
     * @param int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
     * @param String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
     * @return array forums: the group forum with metainfo
     */
    public function groupforum_get_forum() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->groupforum_check_forum_existence();

        if ($mForumExists === false)
            return $this->error('base', 0);
        else if (is_int($mForumExists) && $mForumExists !== true)
            return $this->error('forums', $mForumExists);

        $oForum = bp_forums_get_forum((int) $this->forumid);

        $oReturn->forums[(int) $oForum->forum_id]->name = $oForum->forum_name;
        $oReturn->forums[(int) $oForum->forum_id]->slug = $oForum->forum_slug;
        $oReturn->forums[(int) $oForum->forum_id]->description = $oForum->forum_desc;
        $oReturn->forums[(int) $oForum->forum_id]->topics_count = (int) $oForum->topics;
        $oReturn->forums[(int) $oForum->forum_id]->post_count = (int) $oForum->posts;
        return $oReturn;
    }

    /**
     * Returns an array containing info about the group forum
     * @param int groupid: the groupid you are searching for (if not set, groupslug is searched; groupid or groupslug required)
     * @param String groupslug: the slug to search for (just used if groupid is not set; groupid or groupslug required)
     * @return array forums: the group forum for the group
     */
    public function groupforum_get_forum_by_group() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mGroupExists = $this->get_group_from_params();

        if ($mGroupExists === false)
            return $this->error('base', 0);
        else if (is_int($mGroupExists) && $mGroupExists !== true)
            return $this->error('forums', $mGroupExists);

        $oGroup = groups_get_group(array('group_id' => $this->groupid));
        if ($oGroup->enable_forum == "0")
            return $this->error('forums', 0);
        $iForumId = groups_get_groupmeta($oGroup->id, 'forum_id');
        if ($iForumId == "0")
            return $this->error('forums', 1);
        $oForum = bp_forums_get_forum((int) $iForumId);

        $oReturn->forums[(int) $oForum->forum_id]->name = $oForum->forum_name;
        $oReturn->forums[(int) $oForum->forum_id]->slug = $oForum->forum_slug;
        $oReturn->forums[(int) $oForum->forum_id]->description = $oForum->forum_desc;
        $oReturn->forums[(int) $oForum->forum_id]->topics_count = (int) $oForum->topics;
        $oReturn->forums[(int) $oForum->forum_id]->post_count = (int) $oForum->posts;
        return $oReturn;
    }

    /**
     * Returns an array containing the topics from a group's forum
     * @param int forumid: the forumid you are searching for (if not set, forumid is searched; forumid or forumslug required)
     * @param String forumslug: the forumslug to search for (just used if forumid is not set; forumid or forumslug required)
     * @param int page: the page number you want to display (default 1)
     * @param int per_page: the number of results you want per page (default 15)
     * @param String type: newest, popular, unreplied, tag (default newest)
     * @param String tagname: just used if type = tag
     * @param boolean detailed: true for detailed view (default false)
     * @return array topics: all the group forum topics found
     */
    public function groupforum_get_forum_topics() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->groupforum_check_forum_existence();

        if ($mForumExists === false)
            return $this->error('base', 0);
        else if (is_int($mForumExists) && $mForumExists !== true)
            return $this->error('forums', $mForumExists);

        $aConfig = array();
        $aConfig['type'] = $this->type;
        $aConfig['filter'] = $this->type == 'tag' ? $this->tagname : false;
        $aConfig['forum_id'] = $this->forumid;
        $aConfig['page'] = $this->page;
        $aConfig['per_page'] = $this->per_page;

        $aTopics = bp_forums_get_forum_topics($aConfig);
        if (is_null($aTopics))
            $this->error('forums', 7);
        foreach ($aTopics as $aTopic) {
            $oReturn->topics[(int) $aTopic->topic_id]->title = $aTopic->topic_title;
            $oReturn->topics[(int) $aTopic->topic_id]->slug = $aTopic->topic_slug;
            $oUser = get_user_by('id', $aTopic->topic_poster);
            $oReturn->topics[(int) $aTopic->topic_id]->poster->ID = $oUser->data->ID;
			$oReturn->topics[(int) $aTopic->topic_id]->poster->username = $oUser->data->user_login;
            $oReturn->topics[(int) $aTopic->topic_id]->poster->mail = $oUser->data->user_email;
            $oReturn->topics[(int) $aTopic->topic_id]->poster->display_name = $oUser->data->display_name;
            $oReturn->topics[(int) $aTopic->topic_id]->post_count = (int) $aTopic->topic_posts;
            
			$user = new BP_Core_User($oTopic->topic_last_poster);
			if($user && $user->avatar){
				if($user->avatar_thumb){
					preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
					$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
					if($avatar_thumb && !strstr($avatar_thumb,'http:')){ $avatar_thumb = 'http:'.$avatar_thumb;}
					$oReturn->topics[(int) $aTopic->topic_id]->poster->avatar = $avatar_thumb;
				}
			}
			if ($this->detailed === true) {
                $oTopic = bp_forums_get_topic_details($aTopic->topic_id);
				
				$oUser = get_user_by('id', $oTopic->topic_last_poster);
                $oReturn->topics[(int) $aTopic->topic_id]->last_poster->ID = $oTopic->topic_last_poster;
				$oReturn->topics[(int) $aTopic->topic_id]->last_poster->username = $oUser->data->user_login;
                $oReturn->topics[(int) $aTopic->topic_id]->last_poster->mail = $oUser->data->user_email;
                $oReturn->topics[(int) $aTopic->topic_id]->last_poster->display_name = $oUser->data->display_name;
                $oReturn->topics[(int) $aTopic->topic_id]->start_time = $oTopic->topic_start_time;
                $oReturn->topics[(int) $aTopic->topic_id]->forum_id = (int) $oTopic->forum_id;
                $oReturn->topics[(int) $aTopic->topic_id]->topic_status = $oTopic->topic_status;
                $oReturn->topics[(int) $aTopic->topic_id]->is_open = (int) $oTopic->topic_open === 1 ? true : false;
                $oReturn->topics[(int) $aTopic->topic_id]->is_sticky = (int) $oTopic->topic_sticky === 1 ? true : false;
				
				$user = new BP_Core_User($oTopic->topic_last_poster);
				if($user && $user->avatar){
					if($user->avatar_thumb){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
						$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_thumb && !strstr($avatar_thumb,'http:')){ $avatar_thumb = 'http:'.$avatar_thumb;}
						$oReturn->topics[(int) $aTopic->topic_id]->last_poster->avatar = $avatar_thumb;
					}
				}
            }
        }
        $oReturn->count = count($aTopics);
        return $oReturn;
    }

    /**
     * Returns an array containing the posts from a group's forum
     * @param int topicid: the topicid you are searching for (if not set, topicslug is searched; topicid or topicslug required)
     * @param String topicslug: the slug to search for (just used if topicid is not set; topicid or topicslugs required)
     * @param int page: the page number you want to display (default 1)
     * @param int per_page: the number of results you want per page (default 15)
     * @param String order: desc for descending or asc for ascending (default asc)
     * @return array posts: all the group forum posts found
     */
    public function groupforum_get_topic_posts() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mTopicExists = $this->groupforum_check_topic_existence();
        if ($mTopicExists === false)
            return $this->error('base', 0);
        else if (is_int($mTopicExists) && $mTopicExists !== true)
            return $this->error('forums', $mTopicExists);

        $aConfig = array();
        $aConfig['topic_id'] = $this->topicid;
        $aConfig['page'] = $this->page;
        $aConfig['per_page'] = $this->per_page;
        $aConfig['order'] = $this->order;
        $aPosts = bp_forums_get_topic_posts($aConfig);

        foreach ($aPosts as $oPost) {
            $oReturn->posts[(int) $oPost->post_id]->topicid = (int) $oPost->topic_id;
            $oUser = get_user_by('id', (int) $oPost->poster_id);
            $oReturn->posts[(int) $oPost->post_id]->poster->poster_id = $oPost->poster_id;
			$oReturn->posts[(int) $oPost->post_id]->poster->username = $oUser->data->user_login;
            $oReturn->posts[(int) $oPost->post_id]->poster->mail = $oUser->data->user_email;
            $oReturn->posts[(int) $oPost->post_id]->poster->display_name = $oUser->data->display_name;
            $oReturn->posts[(int) $oPost->post_id]->post_text = $oPost->post_text;
            $oReturn->posts[(int) $oPost->post_id]->post_time = $oPost->post_time;
            $oReturn->posts[(int) $oPost->post_id]->post_position = (int) $oPost->post_position;
        }
        $oReturn->count = count($aPosts);

        return $oReturn;
    }

    /**
     * Returns an array containing info about the sitewide forum
     * @param int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
     * @param String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
     * @return array forums: sitewide forum with some infos
     */
    public function sitewideforum_get_forum() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->sitewideforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forums', $mForumExists);
        foreach ($this->forumid as $iId) {
            $oForum = bbp_get_forum((int) $iId);
            $oReturn->forums[$iId]->title = $oForum->post_title;
            $oReturn->forums[$iId]->name = $oForum->post_name;
            $oUser = get_user_by('id', $oForum->post_author);
            $oReturn->forums[$iId]->author[$oForum->post_author]->username = $oUser->data->user_login;
            $oReturn->forums[$iId]->author[$oForum->post_author]->mail = $oUser->data->user_email;
            $oReturn->forums[$iId]->author[$oForum->post_author]->display_name = $oUser->data->display_name;
            $oReturn->forums[$iId]->date = $oForum->post_date;
            $oReturn->forums[$iId]->last_change = $oForum->post_modified;
            $oReturn->forums[$iId]->status = $oForum->post_status;
            $oReturn->forums[$iId]->name = $oForum->post_name;
            $iTopicCount = bbp_get_forum_topic_count((int) $this->forumid);
            $oReturn->forums[$iId]->topics_count = is_null($iTopicCount) ? 0 : (int) $iTopicCount;
            $iPostCount = bbp_get_forum_post_count((int) $this->forumid);
            $oReturn->forums[$iId]->post_count = is_null($iPostCount) ? 0 : (int) $iPostCount;
        }

        return $oReturn;
    }

    /**
     * Returns an array containing all sitewide forums
     * @params int parentid: all children of the given id (default 0 = all)
     * @return array forums: all sitewide forums
     */
    public function sitewideforum_get_all_forums() {
        $this->init('forums');

        $oReturn = new stdClass();
        global $wpdb;
        $sParentQuery = $this->parentid === false ? "" : " AND post_parent=" . (int) $this->parentid;
        $aForums = $wpdb->get_results($wpdb->prepare(
                        "SELECT ID, post_parent, post_author, post_title, post_date, post_modified
                 FROM   $wpdb->posts
                 WHERE  post_type='forum'" . $sParentQuery
                ));

        if (empty($aForums))
            return $this->error('forums', 9);

        foreach ($aForums as $aForum) {
            $iId = (int) $aForum->ID;
            $oUser = get_user_by('id', (int) $aForum->post_author);
            $oReturn->forums[$iId]->author[(int) $aForum->post_author]->username = $oUser->data->user_login;
            $oReturn->forums[$iId]->author[(int) $aForum->post_author]->mail = $oUser->data->user_email;
            $oReturn->forums[$iId]->author[(int) $aForum->post_author]->display_name = $oUser->data->display_name;
            $oReturn->forums[$iId]->date = $aForum->post_date;
            $oReturn->forums[$iId]->last_changes = $aForum->post_modified;
            $oReturn->forums[$iId]->title = $aForum->post_title;
            $oReturn->forums[$iId]->parent = (int) $aForum->post_parent;
        }
        $oReturn->count = count($aForums);
        return $oReturn;
    }

    /**
     * Returns an array containing all topics of a sitewide forum
     * @param int forumid: the forumid you are searching for (if not set, forumslug is searched; forumid or forumslug required)
     * @param String forumslug: the slug to search for (just used if forumid is not set; forumid or forumslug required)
     * @param boolean display_content: set this to true if you want the content to be displayed too (default false)
     * @return array forums->topics: array of sitewide forums with the topics in it
     */
    public function sitewideforum_get_forum_topics() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->sitewideforum_check_forum_existence();

        if ($mForumExists !== true)
            return $this->error('forums', $mForumExists);
        global $wpdb;
        foreach ($this->forumid as $iId) {
            $aTopics = $wpdb->get_results($wpdb->prepare(
                            "SELECT ID, post_parent, post_author, post_title, post_date, post_modified, post_content
                     FROM   $wpdb->posts
                     WHERE  post_type='topic'
                     AND post_parent='" . $iId . "'"
                    ));
            if (empty($aTopics)) {
                $oReturn->forums[(int) $iId]->topics = "";
                continue;
            }
            foreach ($aTopics as $aTopic) {
                $oUser = get_user_by('id', (int) $aTopic->post_author);
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->author[(int) $aTopic->post_author]->username = $oUser->data->user_login;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->author[(int) $aTopic->post_author]->mail = $oUser->data->user_email;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->author[(int) $aTopic->post_author]->display_name = $oUser->data->display_name;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->date = $aTopic->post_date;
                if ($this->display_content !== false)
                    $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->content = $aTopic->post_content;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->last_changes = $aTopic->post_modified;
                $oReturn->forums[(int) $iId]->topics[(int) $aTopic->ID]->title = $aTopic->post_title;
            }
            $oReturn->forums[(int) $iId]->count = count($aTopics);
        }
        return $oReturn;
    }

    /**
     * Returns an array containing all replies to a topic from a sitewide forum
     * @param int topicid: the topicid you are searching for (if not set, topicslug is searched; topicid or topicsslug required)
     * @param String topicslug: the slug to search for (just used if topicid is not set; topicid or topicslug required)
     * @param boolean display_content: set this to true if you want the content to be displayed too (default false)
     * @return array topics->replies: an array containing the replies
     */
    public function sitewideforum_get_topic_replies() {
        $this->init('forums');

        $oReturn = new stdClass();

        $mForumExists = $this->sitewideforum_check_topic_existence();

        if ($mForumExists !== true)
            return $this->error('forums', $mForumExists);
        foreach ($this->topicid as $iId) {
            global $wpdb;
            $aReplies = $wpdb->get_results($wpdb->prepare(
                            "SELECT ID, post_parent, post_author, post_title, post_date, post_modified, post_content
                     FROM   $wpdb->posts
                     WHERE  post_type='reply'
                     AND post_parent='" . $iId . "'"
                    ));

            if (empty($aReplies)) {
                $oReturn->topics[$iId]->replies = "";
                $oReturn->topics[$iId]->count = 0;
                continue;
            }
            foreach ($aReplies as $oReply) {
                $oUser = get_user_by('id', (int) $oReply->post_author);
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->author[(int) $oReply->post_author]->username = $oUser->data->user_login;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->author[(int) $oReply->post_author]->mail = $oUser->data->user_email;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->author[(int) $oReply->post_author]->display_name = $oUser->data->display_name;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->date = $oReply->post_date;
                if ($this->display_content !== false)
                    $oReturn->topics[$iId]->replies[(int) $oReply->ID]->content = $oReply->post_content;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->last_changes = $oReply->post_modified;
                $oReturn->topics[$iId]->replies[(int) $oReply->ID]->title = $oReply->post_title;
            }
            $oReturn->topics[$iId]->count = count($aReplies);
        }

        return $oReturn;
    }

    /**
     * Returns the settings for the current user
     * @params none no parameters
     * @return object settings: an object full of the settings
     */
    public function settings_get_settings() {
        $this->init('settings');
        $oReturn = new stdClass();

        if ($this->username === false || !username_exists($this->username)) {
            return $this->error('settings', 0);
        }

        $oUser = get_user_by('login', $this->username);

        if (!is_user_logged_in() || get_current_user_id() != $oUser->data->ID)
            return $this->error('base', 0);

        $oReturn->user->mail = $oUser->data->user_email;

        $sNewMention = bp_get_user_meta($oUser->data->ID, 'notification_activity_new_mention', true);
        $sNewReply = bp_get_user_meta($oUser->data->ID, 'notification_activity_new_reply', true);
        $sSendRequests = bp_get_user_meta($oUser->data->ID, 'notification_friends_friendship_request', true);
        $sAcceptRequests = bp_get_user_meta($oUser->data->ID, 'notification_friends_friendship_accepted', true);
        $sGroupInvite = bp_get_user_meta($oUser->data->ID, 'notification_groups_invite', true);
        $sGroupUpdate = bp_get_user_meta($oUser->data->ID, 'notification_groups_group_updated', true);
        $sGroupPromo = bp_get_user_meta($oUser->data->ID, 'notification_groups_admin_promotion', true);
        $sGroupRequest = bp_get_user_meta($oUser->data->ID, 'notification_groups_membership_request', true);
        $sNewMessages = bp_get_user_meta($oUser->data->ID, 'notification_messages_new_message', true);
        $sNewNotices = bp_get_user_meta($oUser->data->ID, 'notification_messages_new_notice', true);

        $oReturn->settings->new_mention = $sNewMention == 'yes' ? true : false;
        $oReturn->settings->new_reply = $sNewReply == 'yes' ? true : false;
        $oReturn->settings->send_requests = $sSendRequests == 'yes' ? true : false;
        $oReturn->settings->accept_requests = $sAcceptRequests == 'yes' ? true : false;
        $oReturn->settings->group_invite = $sGroupInvite == 'yes' ? true : false;
        $oReturn->settings->group_update = $sGroupUpdate == 'yes' ? true : false;
        $oReturn->settings->group_promo = $sGroupPromo == 'yes' ? true : false;
        $oReturn->settings->group_request = $sGroupRequest == 'yes' ? true : false;
        $oReturn->settings->new_message = $sNewMessages == 'yes' ? true : false;
        $oReturn->settings->new_notice = $sNewNotices == 'yes' ? true : false;

        return $oReturn;
    }
	
	/************************************************
	Follwers
	************************************************/
	 public function user_followers_users() {		
		
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if(!$_GET['userid']){$oReturn->error = __('Wrong User ID.','aheadzen'); return $oReturn;}
		
		$args = array('user_id' => $_GET['userid']);
		$followers = bp_follow_get_followers($args);
		if($_GET['getdata']=='ids'){
			$oReturn->followers = $followers;
			return  $oReturn;
		}else if($followers){
			$counter=0;
			for($f=0;$f<count($followers);$f++){
				$user = new BP_Core_User($followers[$f]);				
				if($user){
					$username = $avatar_big = $avatar_thumb = '';
					if($user->user_url){
						$username = str_replace('/','',str_replace(site_url('/members/'),'',$user->user_url));
					}
					if($user->avatar){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar, $user_avatar_result);
						$avatar_big = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_big && !strstr($avatar_big,'http:')){ $avatar_big = 'http:'.$avatar_big;}
					}
					if($user->avatar_thumb){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
						$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_thumb && !strstr($avatar_thumb,'http:')){ $avatar_thumb = 'http:'.$avatar_thumb;}
					}					
					$oReturn->members[$counter]->id 		= $user->id;
					$oReturn->members[$counter]->username 	= $username;
					$oReturn->members[$counter]->fullname 	= $user->fullname;
					$oReturn->members[$counter]->email 		= $user->email;
					$oReturn->members[$counter]->last_active= $user->last_active;
					$oReturn->members[$counter]->avatar_thumb = $avatar_thumb;
					
					$profile_data = $user->profile_data;
					if($profile_data){
						foreach($profile_data as $sFieldName => $val){
							if(is_array($val)){
								$oReturn->members[$counter]->$sFieldName = $val['field_data'];
							}
						}
					}
					if(function_exists('bp_follow_total_follow_counts')){
						$oReturn->members[$counter]->follow_counts  = bp_follow_total_follow_counts( array( 'user_id' => $user->id ) );
					}
					$oReturn->members[$counter]->is_following = 0;
					if(function_exists('bp_follow_is_following') && bp_follow_is_following(array('leader_id'=>$user->id,'follower_id'=>$_GET['userid']))){
						$oReturn->members[$counter]->is_following = 1;
					}
					$counter++;
				}
			}
		}
		
		return  $oReturn;
	}
	
	/************************************************
	Follwings
	************************************************/
	 public function user_followings_users() {		
		
		header("Access-Control-Allow-Origin: *");
		$oReturn = new stdClass();
		$oReturn->success = '';
		$oReturn->error = '';
		if(!$_GET['userid']){$oReturn->error = __('Wrong User ID.','aheadzen'); return $oReturn;}
		$args = array('user_id' => $_GET['userid']);
		$followings = bp_follow_get_following($args);
		if($_GET['getdata']=='ids'){
			$oReturn->followings = $followings;
			return  $oReturn;
		}else if($followings){
			$counter=0;
			for($f=0;$f<count($followings);$f++){
				$user = new BP_Core_User($followings[$f]);				
				if($user){
					$username = $avatar_big = $avatar_thumb = '';
					if($user->user_url){
						$username = str_replace('/','',str_replace(site_url('/members/'),'',$user->user_url));
					}
					if($user->avatar){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar, $user_avatar_result);
						$avatar_big = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_big && !strstr($avatar_big,'http:')){ $avatar_big = 'http:'.$avatar_big;}
					}
					if($user->avatar_thumb){
						preg_match_all('/(src)=("[^"]*")/i',$user->avatar_thumb, $user_avatar_result);
						$avatar_thumb = str_replace('"','',$user_avatar_result[2][0]);
						if($avatar_thumb && !strstr($avatar_thumb,'http:')){ $avatar_thumb = 'http:'.$avatar_thumb;}
					}					
					$oReturn->members[$counter]->id 		= $user->id;
					$oReturn->members[$counter]->username 	= $username;
					$oReturn->members[$counter]->fullname 	= $user->fullname;
					$oReturn->members[$counter]->email 		= $user->email;
					$oReturn->members[$counter]->last_active= $user->last_active;
					$oReturn->members[$counter]->avatar_thumb = $avatar_thumb;
					
					$profile_data = $user->profile_data;
					if($profile_data){
						foreach($profile_data as $sFieldName => $val){
							if(is_array($val)){
								$oReturn->members[$counter]->$sFieldName = $val['field_data'];
							}
						}
					}
					if(function_exists('bp_follow_total_follow_counts')){
						$oReturn->members[$counter]->follow_counts  = bp_follow_total_follow_counts( array( 'user_id' => $user->id ) );
					}
					$oReturn->members[$counter]->is_following = 0;
					if(function_exists('bp_follow_is_following') && bp_follow_is_following(array('leader_id'=>$user->id,'follower_id'=>$_GET['userid']))){
						$oReturn->members[$counter]->is_following = 1;
					}
					$counter++;
				}
			}
		}
		
		return  $oReturn;
	}
	
    public function __call($sName, $aArguments) {
        if (class_exists("BUDDYPRESS_JSON_API_FUNCTION") &&
                method_exists(BUDDYPRESS_JSON_API_FUNCTION, $sName) &&
                is_callable("BUDDYPRESS_JSON_API_FUNCTION::" . $sName)) {
            try {
                return call_user_func_array("BUDDYPRESS_JSON_API_FUNCTION::" . $sName, $aArguments);
            } catch (Exception $e) {
                $oReturn = new stdClass();
                $oReturn->status = "error";
                $oReturn->msg = $e->getMessage();
                die(json_encode($oReturn));
            }
        }
        else
            return NULL;
    }

    public function __get($sName) {
        return isset(BUDDYPRESS_JSON_API_FUNCTION::$sVars[$sName]) ? BUDDYPRESS_JSON_API_FUNCTION::$sVars[$sName] : NULL;
    }

}