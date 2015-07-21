=== BuddyPress Json API ===
Contributors: aheadzen
Tags: buddypress, json, api, profile picture, profile update
Requires at least : 4.0.0
Tested up to: 4.2.2
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


JSON API additional features to be used with Buddypress in addition of profile and profile photo update.


==Description==

BuddyPress Json API is a plugin to supports the JSON API Plugin with a new Controller to set & get information from BuddyPress.

The plugin included all features from the plugin "JSON API for Buddypress" in addition of manage profile features also.

So you can get buddypress data via API same way set buddypress user profile via API.

For user profile plugin has added edit profile photo with edit basic profile and additional fields added for users by buddypress settings.


==Installation==
First you have to install the [JSON API for WordPress Plugin](http://wordpress.org/extend/plugins/json-api/installation/).

To install BuddyPress Json API just follow these steps:

* upload the plugin folder to your WordPress plugin folder (/wp-content/plugins)
* activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer
* activate the controller through the JSON API menu found in the WordPress admin center (Settings -> JSON API)


== Screenshots ==
1. Plugin Activation
2. JSON API settings link wp-admin
3. BuddyPress Json API Settings



==Frequently Asked Questions==

1) List of api include in the plugin?

==> The plugin included BuddypressRead Controller which includes below list of components:

	a) profile_upload_photo
	
	b) profile_set_profile
	
	c) activity_get_activities
	
	d) profile_get_profile
	
	e) messages_get_messages
	
	f) notifications_get_notifications
	
	g) friends_get_friends
	
	h) friends_get_friendship_request
	
	i) friends_get_friendship_status
	
	j) groups_get_groups
	
	k) groups_check_user_has_invite_to_group
	
	l) groups_check_user_membership_request_to_group
	
	m) groups_get_group_admins
	
	n) groups_get_group_mods
	
	o) groups_get_group_members
	
	p) groupforum_get_forum
	
	q) groupforum_get_forum_by_group
	
	r) groupforum_get_forum_topics
	
	s) groupforum_get_topic_posts
	
	t) sitewideforum_get_forum
	
	u) sitewideforum_get_all_forums
	
	v) sitewideforum_get_forum_topics
	
	w) sitewideforum_get_topic_replies
	
	x) settings_get_settings
	

	
2) How to get more detail about usage of the plugin?

==> For a full code documentation go to the [GitHub code documentation](http://tweichart.github.com/JSON-API-for-BuddyPress/doc/index.html)



3) How to user "profile_upload_photo"

==> All Data should be send by POST method.

The required parameters are below with example:

"clicked_pic" -- can be either 'profile_pic' OR 'cover_pic'

				where profile_pic is buddypress profile picture
				
				and cover_pic is big image to display as cover image like in facebook back bigger image. The cover image is stores in user meta table with variable named "bbp_cover_pic".
				
				so in wordpress you can retrieve like -- get_user_meta( $userid, 'bbp_cover_pic',true);
				
				while you get profile you will get both images under "photo" group in which "photo->avatar" is cover image and rest of all are profile images.

				
"user_id"	-- user id should be profiler user is whose detail are going to change.

picture_code  -- is direct encoded image code or base64_encode image code got from android phone.

-- Profile picture update activity also added. 


4) How to user "profile_set_profile"

==> All Data should be send by POST method.

"userid" -- user id should be profiler user is whose detail are going to change.


"data" -- data are the JSON value of buddypress fields and value.

The filed name should be like thefieldid_1, thefieldid_2,thefieldid_3,thefieldid_4.........

where "thefieldid_" == is prefix variable and 1,2,3.... are the field id to store in buddypress database.

Get field id while you add new field from wp-admin > Users > Profile Fields > you should see the form input elements source.

It will display  all input elements id and name like:"field_1", "field_2", "field_3"....

example : 

$_POST['data']='{"1":"Test UserName","5":"About Content :: Lorem Ipsum is simply dummy text of the \n","2":"Male","3":"Native American","4":"Average","21":"Fit","32":"Kosher","39":"Sometimes","43":"Sometimes","47":"English","6":"USA","7":"New York"}';


api url : http://siteurl.com/api/buddypressread/profile_set_profile/



-- Profile update activity also added. 



5) Additional features for "profile_set_profile"

==>Added 'profile_pic' OR 'cover_pic' added under "photo" group in which "photo->avatar" is cover image and rest of all are profile images (big,thumb and small).

where profile_pic is buddypress profile picture

and cover_pic is big image to display as cover image like in facebook back bigger image. The cover image is stores in user meta table with variable named "bbp_cover_pic".

so in wordpress you can retrieve like -- get_user_meta( $userid, 'bbp_cover_pic',true);



6) How to user "activity_add_edit"

==> All Data should be send by POST method.

"userid" -- user id should be profiler user is whose detail are going to change.

"content" -- Your activity contnet to be added.

-->If you want to edit activity pass the activity id as per below variable::

"activityid" -- Activity Id.



7) How to user "activity_delete"

==> All Data should be send by POST method.

"userid" -- user id should be profiler user is whose detail are going to change.

"activityid" -- Activity Id.






==Changelog==

=1.0.0=

* Fresh Public setup

=1.0.1=

* Change in activity display api


=1.0.2=

* "activity_add_edit" to activity add/edit added
* "activity_delete" to delete activity


=1.0.3=

* Activity comment add and edit feature added.
* Activity  delete reply added.

=1.0.4=

* Activity comment as child activity added to display comments as nested comment for "activity_get_activities".

=1.0.5=

* For "activity_get_activities" you have to pass user id but now by username it will work.
* "profile_get_profile" api will return user id in case you try to get user detail by username.


=1.0.6=

* Edit activity comment feature added.


=1.0.7=

* Added New API function for mentions :: "activity_get_mentions"
	--send "username" in POST method.
	
=1.0.8=

* Mentions empty message display - problem solved.

=1.0.9=

* Activity voting plugin feature added so user can now display total votes, up & down link for voting


=1.0.10=

* activity & mention return json indexed by activity id & mention id respectively which was not not shorted as per order of json response - Problem solved.


=1.0.11=

* activity & mention pagination feature added



=1.0.12=

* mention view activity and add/edit feature added


=1.0.13=

* activity comment extra display removed, profile & profile photo change activity content changed.


=1.0.14=

* Member listing api added with name "members_get_members"


=1.0.15=

* Member api "members_get_members" searching members method updated.


=1.0.16=

* Member api "members_get_members" searching members method updated.


=1.0.17 =

* Member api "members_get_members" searching member update.

=1.0.18 =

* Member api "members_get_members" searching member update.


=1.0.19 =

* Member api "members_get_members" searching member update.


=1.0.20 =

* Activity listing -- child activity avatar was wrong - SOLVED.

=1.0.21 =

* Activity listing -- voting details added.

=1.0.22 =

* Activity listing -- voting total up & down default added to zero.