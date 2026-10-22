<?php

include_once ( 'kernel/common/template.php' );
include_once( 'lib/ezdb/classes/ezdb.php' );

$Module = & $Params['Module'];
$http = eZHTTPTool::instance();
$namedParameters = $Module->NamedParameters;
$view_parameters = $Params['UserParameters'];
$tpl = eZTemplate::factory();
$db = eZDB::instance();
$current_user = eZUser::currentUser();
$current_user_id = $current_user->ContentObjectID;
$xrowForumINI = eZINI::instance( 'xrowforum.ini' );
$what = $namedParameters['type'];
$where = in_array( $namedParameters['where'], array( 'inbox', 'outbox' ) ) ? $namedParameters['where'] : false;

if($what == "remove")
{
	if ($http->hasPostVariable( 'DeleteIDArray' ))
	{
		if (!$where)
		{
			if ($http->hasPostVariable( 'where' ))
			{
				$where = ( $http->PostVariable( 'where' ) == 'outbox' ) ? 'outbox' : 'inbox';
			}
			else
			{
				$where = "inbox";
			}
		}
		$selected_messages = $http->PostVariable( 'DeleteIDArray' );
		if(count($selected_messages) >= 0)
		{
			foreach( $selected_messages as $sel_msg)
			{
				$db->begin();
				$sel_msg = (int)$sel_msg;
				$db->arrayQuery("DELETE FROM xrowforum_pm_messages where msg_id = $sel_msg and owner_id = $current_user_id;");
				$db->commit();
				$Module->redirectTo( '/pm/' . $where );
			}
		}
	}
	else
	{
		$msg_id = (int)$namedParameters['messageID'];
		if (!$msg_id)
		{
			return $Module->handleError( 1, 'kernel' );
		}
		$msg_found = $db->arrayQuery("SELECT * FROM xrowforum_pm_messages where msg_id = $msg_id and owner_id = $current_user_id;");
		if(count($msg_found) == 1)
		{
			if (!$where)
			{
				if ($http->hasPostVariable( 'where' ))
				{
					$where = ( $http->PostVariable( 'where' ) == 'outbox' ) ? 'outbox' : 'inbox';
				}
				else
				{
					$where = "inbox";
				}
			}
			$db->begin();
			$db->arrayQuery("DELETE FROM xrowforum_pm_messages where msg_id = $msg_id and owner_id = $current_user_id;");
			$db->commit();
			$Module->redirectTo( '/pm/' . $where );
		}
		else
		{
			return $Module->handleError( 1, 'kernel' );
		}
	}
}
else
{
	return $Module->handleError( 1, 'kernel' );
}
    
?>